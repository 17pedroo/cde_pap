<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require_login();

$uid = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("
  SELECT action, scanned_at
  FROM access_logs
  WHERE user_id=?
  ORDER BY scanned_at DESC
  LIMIT 100
");
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();

page_header("Acessos");
?>
<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="card-title mb-3">Histórico de Entradas/Saídas</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead><tr><th>Data</th><th>Ação</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars(date("d/m/Y H:i:s", strtotime($r["scanned_at"]))) ?></td>
            <td>
              <?php if ($r["action"] === "IN"): ?>
                <span class="badge text-bg-success">Entrada</span>
              <?php else: ?>
                <span class="badge text-bg-danger">Saída</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="2" class="text-muted">Sem registos.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php page_footer(); ?>