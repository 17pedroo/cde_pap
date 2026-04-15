<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require_login();

$uid = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT balance_cents FROM wallets WHERE user_id=?");
$stmt->execute([$uid]);
$balance = (int)($stmt->fetchColumn() ?? 0);

$stmt = $pdo->prepare("
  SELECT type, amount_cents, description, created_at
  FROM wallet_transactions
  WHERE user_id=?
  ORDER BY created_at DESC
  LIMIT 50
");
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();

function eur($cents) {
  return number_format($cents/100, 2, ',', '.') . " €";
}

function translateTransactionType(string $type): string {
  return match ($type) {
    'topup' => 'Carregamento',
    'purchase' => 'Compra',
    'adjustment' => 'Ajuste',
    default => ucfirst($type),
  };
}

page_header("Movimentos");
?>
<div class="row g-3">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted">Saldo atual</div>
        <div class="display-6 mb-0"><?= eur($balance) ?></div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Últimos movimentos</h5>
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
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($r["created_at"]))) ?></td>
                <td><?= htmlspecialchars(translateTransactionType($r["type"])) ?></td>
                <td><?= htmlspecialchars($r["description"] ?? "-") ?></td>
                <td class="text-end <?= ((int)$r["amount_cents"] < 0) ? "text-danger" : "text-success" ?>">
                  <?= eur((int)$r["amount_cents"]) ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="4" class="text-muted">Sem movimentos.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php page_footer(); ?>