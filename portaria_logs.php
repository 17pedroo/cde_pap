<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require_staff();

$date_from_input = trim((string)($_GET['date_from'] ?? date('Y-m-d', strtotime('-6 days'))));
$date_to_input = trim((string)($_GET['date_to'] ?? date('Y-m-d')));
$action_filter = trim((string)($_GET['action'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$export = trim((string)($_GET['export'] ?? ''));

function normalize_portaria_date(string $value): ?string {
  if ($value === '') {
    return null;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  if (!$date || $date->format('Y-m-d') !== $value) {
    return null;
  }

  return $value;
}

$date_from = normalize_portaria_date($date_from_input);
$date_to = normalize_portaria_date($date_to_input);
$allowed_actions = ['IN', 'OUT'];

if (!in_array($action_filter, $allowed_actions, true)) {
  $action_filter = '';
}

$conditions = ['1=1'];
$params = [];

if ($date_from !== null) {
  $conditions[] = 'al.scanned_at >= ?';
  $params[] = $date_from . ' 00:00:00';
}

if ($date_to !== null) {
  $conditions[] = 'al.scanned_at <= ?';
  $params[] = $date_to . ' 23:59:59';
}

if ($action_filter !== '') {
  $conditions[] = 'al.action = ?';
  $params[] = $action_filter;
}

if ($search !== '') {
  $conditions[] = '(u.name LIKE ? OR u.student_number LIKE ?)';
  $search_like = '%' . $search . '%';
  $params[] = $search_like;
  $params[] = $search_like;
}

$stmt = $pdo->prepare(
  "SELECT al.scanned_at, al.action, u.name, u.student_number
   FROM access_logs al
   JOIN users u ON u.id = al.user_id
   WHERE " . implode(' AND ', $conditions) . "
   ORDER BY al.scanned_at DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$entry_count = 0;
$exit_count = 0;
$student_numbers = [];

foreach ($rows as $row) {
  if ($row['action'] === 'IN') {
    $entry_count++;
  } else {
    $exit_count++;
  }

  if (!empty($row['student_number'])) {
    $student_numbers[$row['student_number']] = true;
  }
}

if ($export === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="leituras-portaria.csv"');

  echo "\xEF\xBB\xBF";
  $output = fopen('php://output', 'w');
  fputcsv($output, ['Data/Hora', 'Aluno', 'Numero', 'Acao'], ';');

  foreach ($rows as $row) {
    fputcsv($output, [
      date('d/m/Y H:i:s', strtotime($row['scanned_at'])),
      $row['name'],
      $row['student_number'] ?: '-',
      $row['action'] === 'IN' ? 'Entrada' : 'Saida'
    ], ';');
  }

  fclose($output);
  exit;
}

$export_params = $_GET;
$export_params['export'] = 'csv';
$export_url = '?' . http_build_query($export_params);

page_header("Leituras (Portaria)");
?>
<div class="row g-3">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
          <div>
            <h5 class="card-title mb-1">Filtrar leituras de portaria</h5>
            <div class="text-muted small">Pesquise por aluno, número ou período e exporte a lista filtrada.</div>
          </div>
          <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($export_url) ?>">Exportar CSV</a>
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
          <div class="col-12 col-md-2">
            <label class="form-label">Ação</label>
            <select name="action" class="form-select">
              <option value="">Todas</option>
              <option value="IN" <?= $action_filter === 'IN' ? 'selected' : '' ?>>Entradas</option>
              <option value="OUT" <?= $action_filter === 'OUT' ? 'selected' : '' ?>>Saídas</option>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Aluno ou número</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Ex: Ana ou 12345">
          </div>
          <div class="col-12 d-flex gap-2 flex-wrap">
            <button class="btn btn-primary">Aplicar filtros</button>
            <a class="btn btn-outline-secondary" href="portaria_logs.php">Limpar</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Leituras no filtro</div>
        <div class="display-6 mb-1"><?= count($rows) ?></div>
        <div class="text-muted small">Total de registos encontrados.</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Entradas</div>
        <div class="display-6 mb-1 text-success"><?= $entry_count ?></div>
        <div class="text-muted small">Leituras marcadas como IN.</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Saídas</div>
        <div class="display-6 mb-1 text-danger"><?= $exit_count ?></div>
        <div class="text-muted small">Leituras marcadas como OUT.</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Alunos distintos</div>
        <div class="display-6 mb-1"><?= count($student_numbers) ?></div>
        <div class="text-muted small">Alunos abrangidos pelo filtro.</div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Leituras encontradas</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Data/Hora</th>
                <th>Aluno</th>
                <th>Nº</th>
                <th class="text-end">Ação</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= htmlspecialchars(date("d/m/Y H:i:s", strtotime($row["scanned_at"]))) ?></td>
                <td><?= htmlspecialchars($row["name"]) ?></td>
                <td><?= htmlspecialchars($row["student_number"] ?: "-") ?></td>
                <td class="text-end">
                  <?php if ($row["action"] === "IN"): ?>
                    <span class="badge text-bg-success">Entrada</span>
                  <?php else: ?>
                    <span class="badge text-bg-danger">Saída</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="4" class="text-muted">Ainda sem leituras para os filtros escolhidos.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php page_footer(); ?>