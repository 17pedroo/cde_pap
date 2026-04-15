<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";

require_staff();

function eur(int $cents): string {
    return number_format($cents / 100, 2, ',', '.') . ' €';
}

$dailySales = $pdo->prepare(
    "SELECT COUNT(*) AS count, COALESCE(-SUM(amount_cents), 0) AS total_cents " .
    "FROM wallet_transactions WHERE type='purchase' AND description LIKE 'Compra bar:%' AND created_at >= CURDATE()"
);
$dailySales->execute();
$dailySales = $dailySales->fetch(PDO::FETCH_ASSOC);

$weeklySales = $pdo->prepare(
    "SELECT COUNT(*) AS count, COALESCE(-SUM(amount_cents), 0) AS total_cents " .
    "FROM wallet_transactions WHERE type='purchase' AND description LIKE 'Compra bar:%' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
);
$weeklySales->execute();
$weeklySales = $weeklySales->fetch(PDO::FETCH_ASSOC);

$ticketSummary = $pdo->prepare(
    "SELECT COUNT(*) AS total, SUM(ticket_type = 'almoco') AS almoco, " .
    "SUM(status = 'active') AS active, SUM(status = 'scanned') AS scanned, SUM(status = 'cancelled') AS cancelled " .
    "FROM canteen_tickets WHERE reserved_at >= CURDATE()"
);
$ticketSummary->execute();
$ticketSummary = $ticketSummary->fetch(PDO::FETCH_ASSOC);

$purchaseRows = $pdo->query(
    "SELECT wt.created_at, wt.amount_cents, wt.description, u.name, u.student_number " .
    "FROM wallet_transactions wt " .
    "JOIN users u ON u.id = wt.user_id " .
    "WHERE wt.type='purchase' AND wt.description LIKE 'Compra bar:%' " .
    "ORDER BY wt.created_at DESC LIMIT 50"
)->fetchAll(PDO::FETCH_ASSOC);

$ticketRows = $pdo->query(
    "SELECT ct.reserved_at, ct.ticket_type, ct.status, ct.notes, u.name, u.student_number " .
    "FROM canteen_tickets ct " .
    "JOIN users u ON u.id = ct.student_id " .
    "ORDER BY ct.reserved_at DESC LIMIT 50"
)->fetchAll(PDO::FETCH_ASSOC);

page_header("Relatórios");
?>

<div class="mb-3">
  <h4 class="mb-0">Relatórios</h4>
  <div class="text-muted">Visão geral de vendas do bar e tickets de cantina.</div>
</div>

<div class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted">Vendas bar hoje</div>
        <div class="display-6 mb-1"><?= eur((int)$dailySales['total_cents']) ?></div>
        <div class="text-muted"><?= (int)$dailySales['count'] ?> transacções</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted">Vendas bar últimos 7 dias</div>
        <div class="display-6 mb-1"><?= eur((int)$weeklySales['total_cents']) ?></div>
        <div class="text-muted"><?= (int)$weeklySales['count'] ?> transacções</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted">Tickets de cantina hoje</div>
        <div class="display-6 mb-1"><?= (int)$ticketSummary['total'] ?></div>
        <div class="text-muted">Almoço: <?= (int)$ticketSummary['almoco'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted">Estado dos tickets</div>
        <div class="fw-semibold mb-1">Ativos: <?= (int)$ticketSummary['active'] ?></div>
        <div class="text-muted">Lidos: <?= (int)$ticketSummary['scanned'] ?> | Cancelados: <?= (int)$ticketSummary['cancelled'] ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Últimas compras do bar</h5>
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
              <?php foreach ($purchaseRows as $row): ?>
                <tr>
                  <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td><?= htmlspecialchars($row['description']) ?></td>
                  <td class="text-end text-danger"><?= eur((int)$row['amount_cents']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$purchaseRows): ?>
                <tr><td colspan="4" class="text-muted">Sem compras registadas.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Últimos tickets de cantina</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th>Aluno</th>
                <th>Tipo</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ticketRows as $row): ?>
                <tr>
                  <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['reserved_at']))) ?></td>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td><?= htmlspecialchars($row['ticket_type']) ?></td>
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
              <?php if (!$ticketRows): ?>
                <tr><td colspan="4" class="text-muted">Sem tickets registados.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php page_footer(); ?>
