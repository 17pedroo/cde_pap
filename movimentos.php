<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require_login();

$uid = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT balance_cents FROM wallets WHERE user_id=?");
$stmt->execute([$uid]);
$balance = (int)($stmt->fetchColumn() ?? 0);

$date_from_input = trim((string)($_GET["date_from"] ?? date("Y-m-01")));
$date_to_input = trim((string)($_GET["date_to"] ?? date("Y-m-d")));
$type_filter = trim((string)($_GET["type"] ?? ""));
$export = trim((string)($_GET["export"] ?? ""));

function normalize_date_input(string $value): ?string {
  if ($value === "") {
    return null;
  }

  $date = DateTime::createFromFormat("Y-m-d", $value);
  if (!$date || $date->format("Y-m-d") !== $value) {
    return null;
  }

  return $value;
}

function eur($cents) {
  return number_format($cents / 100, 2, ',', '.') . " €";
}

function translateTransactionType(string $type): string {
  return match ($type) {
    'topup' => 'Carregamento',
    'purchase' => 'Compra',
    'adjustment' => 'Ajuste',
    default => ucfirst($type),
  };
}

$date_from = normalize_date_input($date_from_input);
$date_to = normalize_date_input($date_to_input);
$allowed_types = ['topup', 'purchase', 'adjustment'];

if (!in_array($type_filter, $allowed_types, true)) {
  $type_filter = '';
}

$conditions = ["user_id = ?"];
$params = [$uid];

if ($date_from !== null) {
  $conditions[] = "created_at >= ?";
  $params[] = $date_from . " 00:00:00";
}

if ($date_to !== null) {
  $conditions[] = "created_at <= ?";
  $params[] = $date_to . " 23:59:59";
}

if ($type_filter !== '') {
  $conditions[] = "type = ?";
  $params[] = $type_filter;
}

$stmt = $pdo->prepare(
  "SELECT type, amount_cents, description, created_at
   FROM wallet_transactions
   WHERE " . implode(" AND ", $conditions) . "
   ORDER BY created_at DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$movement_count = count($rows);
$credit_cents = 0;
$debit_cents = 0;

foreach ($rows as $row) {
  $amount_cents = (int)$row['amount_cents'];
  if ($amount_cents >= 0) {
    $credit_cents += $amount_cents;
  } else {
    $debit_cents += abs($amount_cents);
  }
}

if ($export === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="movimentos.csv"');

  echo "\xEF\xBB\xBF";
  $output = fopen('php://output', 'w');
  fputcsv($output, ['Data', 'Tipo', 'Descricao', 'Valor'], ';');

  foreach ($rows as $row) {
    fputcsv($output, [
      date('d/m/Y H:i', strtotime($row['created_at'])),
      translateTransactionType($row['type']),
      $row['description'] ?: '-',
      number_format(((int)$row['amount_cents']) / 100, 2, ',', '.')
    ], ';');
  }

  fclose($output);
  exit;
}

$export_params = $_GET;
$export_params['export'] = 'csv';
$export_url = '?' . http_build_query($export_params);

page_header("Movimentos");
?>
<div class="row g-3">
  <div class="col-12 col-xl-8">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
          <div>
            <h5 class="card-title mb-1">Filtrar movimentos</h5>
            <div class="text-muted small">Escolha o período e o tipo para localizar movimentos específicos ou exportar o resultado.</div>
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
            <label class="form-label">Tipo</label>
            <select name="type" class="form-select">
              <option value="">Todos</option>
              <option value="topup" <?= $type_filter === 'topup' ? 'selected' : '' ?>>Carregamentos</option>
              <option value="purchase" <?= $type_filter === 'purchase' ? 'selected' : '' ?>>Compras</option>
              <option value="adjustment" <?= $type_filter === 'adjustment' ? 'selected' : '' ?>>Ajustes</option>
            </select>
          </div>
          <div class="col-12 d-flex gap-2 flex-wrap">
            <button class="btn btn-primary">Aplicar filtros</button>
            <a class="btn btn-outline-secondary" href="movimentos.php">Limpar</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-4">
    <div class="card shadow-sm h-100 metric-card">
      <div class="card-body">
        <div class="text-muted">Saldo atual</div>
        <div class="display-6 mb-1"><?= eur($balance) ?></div>
        <div class="text-muted small">Valor disponível no momento.</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Movimentos no filtro</div>
        <div class="display-6 mb-1"><?= $movement_count ?></div>
        <div class="text-muted small">Total de registos encontrados.</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Entradas de saldo</div>
        <div class="display-6 mb-1 text-success"><?= eur($credit_cents) ?></div>
        <div class="text-muted small">Carregamentos e ajustes positivos.</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Saídas de saldo</div>
        <div class="display-6 mb-1 text-danger"><?= eur($debit_cents) ?></div>
        <div class="text-muted small">Compras e ajustes negativos.</div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Movimentos encontrados</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Descrição</th>
                <th class="text-end">Valor</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($row["created_at"]))) ?></td>
                <td><?= htmlspecialchars(translateTransactionType($row["type"])) ?></td>
                <td><?= htmlspecialchars($row["description"] ?: "-") ?></td>
                <td class="text-end <?= ((int)$row["amount_cents"] < 0) ? "text-danger" : "text-success" ?>">
                  <?= eur((int)$row["amount_cents"]) ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="4" class="text-muted">Sem movimentos para os filtros escolhidos.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php page_footer(); ?>