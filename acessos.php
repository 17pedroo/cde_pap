<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require_login();

$uid = (int)$_SESSION["user_id"];
$date_from_input = trim((string)($_GET['date_from'] ?? date('Y-m-01')));
$date_to_input = trim((string)($_GET['date_to'] ?? date('Y-m-d')));
$action_filter = trim((string)($_GET['action'] ?? ''));
$export = trim((string)($_GET['export'] ?? ''));

function normalize_access_date(string $value): ?string {
  if ($value === '') {
    return null;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  if (!$date || $date->format('Y-m-d') !== $value) {
    return null;
  }

  return $value;
}

$date_from = normalize_access_date($date_from_input);
$date_to = normalize_access_date($date_to_input);
$allowed_actions = ['IN', 'OUT'];

if (!in_array($action_filter, $allowed_actions, true)) {
  $action_filter = '';
}

$conditions = ['user_id = ?'];
$params = [$uid];

if ($date_from !== null) {
  $conditions[] = 'scanned_at >= ?';
  $params[] = $date_from . ' 00:00:00';
}

if ($date_to !== null) {
  $conditions[] = 'scanned_at <= ?';
  $params[] = $date_to . ' 23:59:59';
}

if ($action_filter !== '') {
  $conditions[] = 'action = ?';
  $params[] = $action_filter;
}

$stmt = $pdo->prepare(
  "SELECT action, scanned_at
   FROM access_logs
   WHERE " . implode(' AND ', $conditions) . "
   ORDER BY scanned_at DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$entry_count = 0;
$exit_count = 0;

foreach ($rows as $row) {
  if ($row['action'] === 'IN') {
    $entry_count++;
  } else {
    $exit_count++;
  }
}

if ($export === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="acessos.csv"');

  echo "\xEF\xBB\xBF";
  $output = fopen('php://output', 'w');
  fputcsv($output, ['Data', 'Acao'], ';');

  foreach ($rows as $row) {
    fputcsv($output, [
      date('d/m/Y H:i:s', strtotime($row['scanned_at'])),
      $row['action'] === 'IN' ? 'Entrada' : 'Saida'
    ], ';');
  }

  fclose($output);
  exit;
}

$export_params = $_GET;
$export_params['export'] = 'csv';
$export_url = '?' . http_build_query($export_params);

page_header("Acessos");
?>
<div class="row g-3">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
          <div>
            <h5 class="card-title mb-1">Filtrar histórico de acessos</h5>
            <div class="text-muted small">Refine por período e ação para encontrar entradas e saídas específicas.</div>
          </div>
          <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($export_url) ?>">Exportar CSV</a>
        </div>

        <form method="get" class="row g-3 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label">De</label>
            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from_input) ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Até</label>
            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to_input) ?>">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Ação</label>
            <select name="action" class="form-select">
              <option value="">Todas</option>
              <option value="IN" <?= $action_filter === 'IN' ? 'selected' : '' ?>>Entradas</option>
              <option value="OUT" <?= $action_filter === 'OUT' ? 'selected' : '' ?>>Saídas</option>
            </select>
          </div>
          <div class="col-12 d-flex gap-2 flex-wrap">
            <button class="btn btn-primary">Aplicar filtros</button>
            <a class="btn btn-outline-secondary" href="acessos.php">Limpar</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Registos no filtro</div>
        <div class="display-6 mb-1"><?= count($rows) ?></div>
        <div class="text-muted small">Total de leituras encontradas.</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Entradas</div>
        <div class="display-6 mb-1 text-success"><?= $entry_count ?></div>
        <div class="text-muted small">Registos de entrada no período.</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Saídas</div>
        <div class="display-6 mb-1 text-danger"><?= $exit_count ?></div>
        <div class="text-muted small">Registos de saída no período.</div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Histórico de entradas e saídas</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= htmlspecialchars(date("d/m/Y H:i:s", strtotime($row["scanned_at"]))) ?></td>
                <td>
                  <?php if ($row["action"] === "IN"): ?>
                    <span class="badge text-bg-success">Entrada</span>
                  <?php else: ?>
                    <span class="badge text-bg-danger">Saída</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="2" class="text-muted">Sem registos para os filtros escolhidos.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php page_footer(); ?>