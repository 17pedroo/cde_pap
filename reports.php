<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";

require_staff();

$date_from_input = trim((string)($_GET['date_from'] ?? date('Y-m-d', strtotime('-6 days'))));
$date_to_input = trim((string)($_GET['date_to'] ?? date('Y-m-d')));
$student_query = trim((string)($_GET['student_query'] ?? ''));
$export = trim((string)($_GET['export'] ?? ''));

function normalize_report_date(string $value): ?string {
  if ($value === '') {
    return null;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  if (!$date || $date->format('Y-m-d') !== $value) {
    return null;
  }

  return $value;
}

function eur(int $cents): string {
  return number_format($cents / 100, 2, ',', '.') . ' €';
}

function extract_product_name(string $description): string {
  $prefix = 'Compra bar: ';
  if (str_starts_with($description, $prefix)) {
    return trim(substr($description, strlen($prefix)));
  }

  return $description;
}

function translate_ticket_type(string $type): string {
  return match ($type) {
    'almoco' => 'Almoço',
    default => ucfirst($type),
  };
}

function translate_ticket_status(string $status): string {
  return match ($status) {
    'active' => 'Ativo',
    'scanned' => 'Lido',
    'cancelled' => 'Cancelado',
    default => ucfirst($status),
  };
}

$date_from = normalize_report_date($date_from_input);
$date_to = normalize_report_date($date_to_input);

$purchase_conditions = ["wt.type = 'purchase'", "wt.description LIKE 'Compra bar:%'"];
$purchase_params = [];

if ($date_from !== null) {
  $purchase_conditions[] = 'wt.created_at >= ?';
  $purchase_params[] = $date_from . ' 00:00:00';
}

if ($date_to !== null) {
  $purchase_conditions[] = 'wt.created_at <= ?';
  $purchase_params[] = $date_to . ' 23:59:59';
}

if ($student_query !== '') {
  $purchase_conditions[] = '(u.name LIKE ? OR u.student_number LIKE ?)';
  $student_like = '%' . $student_query . '%';
  $purchase_params[] = $student_like;
  $purchase_params[] = $student_like;
}

$purchase_stmt = $pdo->prepare(
  "SELECT wt.created_at, wt.amount_cents, wt.description, u.name, u.student_number
   FROM wallet_transactions wt
   JOIN users u ON u.id = wt.user_id
   WHERE " . implode(' AND ', $purchase_conditions) . "
   ORDER BY wt.created_at DESC"
);
$purchase_stmt->execute($purchase_params);
$purchase_rows = $purchase_stmt->fetchAll(PDO::FETCH_ASSOC);

$ticket_conditions = ['1=1'];
$ticket_params = [];

if ($date_from !== null) {
  $ticket_conditions[] = 'ct.reserved_at >= ?';
  $ticket_params[] = $date_from . ' 00:00:00';
}

if ($date_to !== null) {
  $ticket_conditions[] = 'ct.reserved_at <= ?';
  $ticket_params[] = $date_to . ' 23:59:59';
}

if ($student_query !== '') {
  $ticket_conditions[] = '(u.name LIKE ? OR u.student_number LIKE ?)';
  $student_like = '%' . $student_query . '%';
  $ticket_params[] = $student_like;
  $ticket_params[] = $student_like;
}

$ticket_stmt = $pdo->prepare(
  "SELECT ct.reserved_at, ct.ticket_type, ct.status, ct.notes, u.name, u.student_number
   FROM canteen_tickets ct
   JOIN users u ON u.id = ct.student_id
   WHERE " . implode(' AND ', $ticket_conditions) . "
   ORDER BY ct.reserved_at DESC"
);
$ticket_stmt->execute($ticket_params);
$ticket_rows = $ticket_stmt->fetchAll(PDO::FETCH_ASSOC);

$bar_total_cents = 0;
$ticket_status_summary = ['active' => 0, 'scanned' => 0, 'cancelled' => 0];
$students_covered = [];

foreach ($purchase_rows as $row) {
  $bar_total_cents += abs((int)$row['amount_cents']);
  if (!empty($row['student_number'])) {
    $students_covered[$row['student_number']] = true;
  }
}

foreach ($ticket_rows as $row) {
  $status = $row['status'];
  if (isset($ticket_status_summary[$status])) {
    $ticket_status_summary[$status]++;
  }

  if (!empty($row['student_number'])) {
    $students_covered[$row['student_number']] = true;
  }
}

if ($export === 'bar_csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="relatorio-bar.csv"');

  echo "\xEF\xBB\xBF";
  $output = fopen('php://output', 'w');
  fputcsv($output, ['Data', 'Aluno', 'Numero', 'Produto', 'Valor'], ';');

  foreach ($purchase_rows as $row) {
    fputcsv($output, [
      date('d/m/Y H:i', strtotime($row['created_at'])),
      $row['name'],
      $row['student_number'] ?: '-',
      extract_product_name($row['description']),
      number_format(abs((int)$row['amount_cents']) / 100, 2, ',', '.')
    ], ';');
  }

  fclose($output);
  exit;
}

if ($export === 'tickets_csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="relatorio-cantina.csv"');

  echo "\xEF\xBB\xBF";
  $output = fopen('php://output', 'w');
  fputcsv($output, ['Data', 'Aluno', 'Numero', 'Tipo', 'Estado', 'Notas'], ';');

  foreach ($ticket_rows as $row) {
    fputcsv($output, [
      date('d/m/Y H:i', strtotime($row['reserved_at'])),
      $row['name'],
      $row['student_number'] ?: '-',
      translate_ticket_type($row['ticket_type']),
      translate_ticket_status($row['status']),
      $row['notes'] ?: '-'
    ], ';');
  }

  fclose($output);
  exit;
}

$bar_export_params = $_GET;
$bar_export_params['export'] = 'bar_csv';
$bar_export_url = '?' . http_build_query($bar_export_params);

$ticket_export_params = $_GET;
$ticket_export_params['export'] = 'tickets_csv';
$ticket_export_url = '?' . http_build_query($ticket_export_params);

page_header("Relatórios");
?>

<div class="mb-3">
  <h4 class="mb-0">Relatórios</h4>
  <div class="text-muted">Resumo filtrável de vendas do bar e utilização da cantina.</div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
      <div>
        <h5 class="card-title mb-1">Filtrar período</h5>
        <div class="text-muted small">Ajuste o intervalo e pesquise por aluno para obter um relatório mais útil e exportável.</div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($bar_export_url) ?>">Exportar bar</a>
        <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($ticket_export_url) ?>">Exportar cantina</a>
      </div>
    </div>

    <form method="get" class="row g-3 align-items-end">
      <div class="col-12 col-md-3">
        <label class="form-label">De</label>
        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from_input) ?>">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Até</label>
        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to_input) ?>">
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Aluno ou número</label>
        <input type="text" name="student_query" class="form-control" value="<?= htmlspecialchars($student_query) ?>" placeholder="Ex: Beatriz ou 10001">
      </div>
      <div class="col-12 col-md-2 d-grid">
        <button class="btn btn-primary">Aplicar</button>
      </div>
      <div class="col-12">
        <a class="btn btn-outline-secondary" href="reports.php">Limpar filtros</a>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Vendas do bar no filtro</div>
        <div class="display-6 mb-1"><?= eur($bar_total_cents) ?></div>
        <div class="text-muted"><?= count($purchase_rows) ?> compra(s) registada(s)</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Tickets de cantina</div>
        <div class="display-6 mb-1"><?= count($ticket_rows) ?></div>
        <div class="text-muted">Ativos: <?= $ticket_status_summary['active'] ?> | Lidos: <?= $ticket_status_summary['scanned'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Cancelamentos</div>
        <div class="display-6 mb-1 text-danger"><?= $ticket_status_summary['cancelled'] ?></div>
        <div class="text-muted">Tickets cancelados no filtro.</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Alunos abrangidos</div>
        <div class="display-6 mb-1"><?= count($students_covered) ?></div>
        <div class="text-muted">Alunos com atividade no filtro.</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title mb-3">Compras do bar</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th>Aluno</th>
                <th>Produto</th>
                <th class="text-end">Valor</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($purchase_rows as $row): ?>
                <tr>
                  <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td>
                  <td>
                    <div><?= htmlspecialchars($row['name']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($row['student_number'] ?: '-') ?></div>
                  </td>
                  <td><?= htmlspecialchars(extract_product_name($row['description'])) ?></td>
                  <td class="text-end text-danger"><?= eur(abs((int)$row['amount_cents'])) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$purchase_rows): ?>
                <tr><td colspan="4" class="text-muted">Sem compras do bar para os filtros escolhidos.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h5 class="card-title mb-3">Tickets de cantina</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th>Aluno</th>
                <th>Tipo</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ticket_rows as $row): ?>
                <tr>
                  <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['reserved_at']))) ?></td>
                  <td>
                    <div><?= htmlspecialchars($row['name']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($row['student_number'] ?: '-') ?></div>
                  </td>
                  <td><?= htmlspecialchars(translate_ticket_type($row['ticket_type'])) ?></td>
                  <td>
                    <?php if ($row['status'] === 'active'): ?>
                      <span class="badge text-bg-secondary">Ativo</span>
                    <?php elseif ($row['status'] === 'scanned'): ?>
                      <span class="badge text-bg-success">Lido</span>
                    <?php else: ?>
                      <span class="badge text-bg-danger">Cancelado</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$ticket_rows): ?>
                <tr><td colspan="4" class="text-muted">Sem tickets de cantina para os filtros escolhidos.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php page_footer(); ?>
