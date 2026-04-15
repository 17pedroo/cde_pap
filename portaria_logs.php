<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require_staff();

$stmt = $pdo->query("
  SELECT al.scanned_at, al.action, u.name, u.student_number
  FROM access_logs al
  JOIN users u ON u.id = al.user_id
  ORDER BY al.scanned_at DESC
  LIMIT 50
");
$rows = $stmt->fetchAll();

page_header("Leituras (Portaria)");
?>
<div class="card shadow-sm">
  <div class="card-body">
    <h5 class="card-title mb-3">Últimas leituras</h5>
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
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars(date("d/m/Y H:i:s", strtotime($r["scanned_at"]))) ?></td>
            <td><?= htmlspecialchars($r["name"]) ?></td>
            <td><?= htmlspecialchars($r["student_number"] ?? "-") ?></td>
            <td class="text-end">
              <?php if ($r["action"] === "IN"): ?>
                <span class="badge text-bg-success">Entrada</span>
              <?php else: ?>
                <span class="badge text-bg-danger">Saída</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="4" class="text-muted">Ainda sem leituras.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php page_footer(); ?>