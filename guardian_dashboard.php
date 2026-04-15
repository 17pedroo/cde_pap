<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require_guardian();

$guardian_id = (int)$_SESSION["user_id"];
$error = null;
$success = null;
$selected_student_id = (int)($_REQUEST["student_id"] ?? 0);
$amount = '';
$description = '';

$stmt = $pdo->prepare(
  "SELECT u.id, u.student_number, u.name, COALESCE(w.balance_cents, 0) AS balance_cents " .
  "FROM guardian_students gs " .
  "JOIN users u ON u.id = gs.student_id " .
  "LEFT JOIN wallets w ON w.user_id = u.id " .
  "WHERE gs.guardian_id = ? AND u.role = 'student' " .
  "ORDER BY u.name"
);
$stmt->execute([$guardian_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$student_count = count($students);

if (!$selected_student_id && $student_count > 0) {
  $selected_student_id = (int)$students[0]["id"];
}

$selected_student = null;
foreach ($students as $student) {
  if ((int)$student["id"] === $selected_student_id) {
    $selected_student = $student;
    break;
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $selected_student_id = (int)($_POST["student_id"] ?? $selected_student_id);
  $amount = trim($_POST["amount"] ?? '');
  $description = trim($_POST["description"] ?? '');

  foreach ($students as $student) {
    if ((int)$student["id"] === $selected_student_id) {
      $selected_student = $student;
      break;
    }
  }

  if (!$selected_student) {
    $error = 'Aluno selecionado não encontrado.';
  } else {
    $raw = str_replace([','], ['.'], $amount);
    if ($raw === '' || !is_numeric($raw) || (float)$raw <= 0) {
      $error = 'Introduza um valor válido para o carregamento.';
    } else {
      $amount_cents = (int)round((float)$raw * 100);
      $description = $description ?: 'Carregamento efetuado pelo encarregado';

      $stmt = $pdo->prepare(
        "INSERT INTO wallets(user_id, balance_cents) VALUES (?, ?) " .
        "ON DUPLICATE KEY UPDATE balance_cents = balance_cents + VALUES(balance_cents)"
      );
      $stmt->execute([$selected_student_id, $amount_cents]);

      $stmt = $pdo->prepare(
        "INSERT INTO wallet_transactions(user_id, type, amount_cents, description) " .
        "VALUES (?, 'topup', ?, ?)"
      );
      $stmt->execute([$selected_student_id, $amount_cents, $description]);

      $success = 'Carregamento registado com sucesso.';
      $selected_student['balance_cents'] += $amount_cents;
    }
  }
}

if ($selected_student) {
  $stmt = $pdo->prepare(
    "SELECT type, amount_cents, description, created_at
     FROM wallet_transactions
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 50"
  );
  $stmt->execute([$selected_student_id]);
  $transactions = $stmt->fetchAll();

  $stmt = $pdo->prepare(
    "SELECT action, scanned_at
     FROM access_logs
     WHERE user_id = ?
     ORDER BY scanned_at DESC
     LIMIT 20"
  );
  $stmt->execute([$selected_student_id]);
  $accessRows = $stmt->fetchAll();

  $stmt = $pdo->prepare(
    "SELECT ct.ticket_type, ct.status, ct.notes, ct.reserved_at, u.name AS staff_name
     FROM canteen_tickets ct
     LEFT JOIN users u ON u.id = ct.scanned_by_user_id
     WHERE ct.student_id = ?
     ORDER BY ct.reserved_at DESC
     LIMIT 20"
  );
  $stmt->execute([$selected_student_id]);
  $canteenTickets = $stmt->fetchAll();
} else {
  $transactions = [];
  $accessRows = [];
  $canteenTickets = [];
}

function eur($cents) {
  return number_format($cents / 100, 2, ',', '.') . ' €';
}

function translateTransactionType(string $type): string {
  return match ($type) {
    'topup' => 'Carregamento',
    'purchase' => 'Compra',
    'adjustment' => 'Ajuste',
    default => ucfirst($type),
  };
}

page_header('Encarregado - Saldo e Movimentos');
?>
<div class="mb-3">
  <h4 class="mb-0">Área do Encarregado</h4>
  <div class="text-muted">Consultar saldo, movimentos e carregar o aluno associado.</div>
</div>

<?php if (empty($students)): ?>
  <div class="alert alert-warning">Não há alunos associados a esta conta. Contacte a escola.</div>
<?php else: ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>


  <div class="row g-3">
    <div class="col-12 col-xl-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <form method="get" class="mb-3">
            <label class="form-label">Aluno selecionado</label>
            <select class="form-select" name="student_id" onchange="this.form.submit()">
              <?php foreach ($students as $student): ?>
                <option value="<?= $student['id'] ?>" <?= ((int)$student['id'] === $selected_student_id) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($student['name'] . ' - ' . $student['student_number']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
          <div class="small text-muted mb-3"><?= $student_count ?> aluno(s) associado(s)</div>

          <?php if ($selected_student): ?>
            <div class="text-muted">Saldo do aluno</div>
            <div class="display-6 mb-2"><?= eur($selected_student['balance_cents']) ?></div>
            <div class="text-muted small">Número: <?= htmlspecialchars($selected_student['student_number']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($selected_student): ?>
      <div class="card shadow-sm mt-3">
        <div class="card-body">
          <h5 class="card-title mb-3">Carregar saldo</h5>
          <form method="post">
            <input type="hidden" name="student_id" value="<?= $selected_student_id ?>">
            <div class="mb-3">
              <label class="form-label">Valor (€)</label>
              <input type="text" name="amount" class="form-control" value="<?= htmlspecialchars($amount) ?>" placeholder="Ex: 10.50" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Descrição</label>
              <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($description) ?>" placeholder="Opcional">
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">Registar carregamento</button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-12 col-xl-8">
      <?php if ($selected_student): ?>
      <div class="card shadow-sm mb-3">
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
                <?php foreach ($transactions as $t): ?>
                  <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($t['created_at']))) ?></td>
                    <td><?= htmlspecialchars(translateTransactionType($t['type'])) ?></td>
                    <td><?= htmlspecialchars($t['description'] ?: '-') ?></td>
                    <td class="text-end <?= ((int)$t['amount_cents'] < 0) ? 'text-danger' : 'text-success' ?>">
                      <?= eur((int)$t['amount_cents']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$transactions): ?>
                  <tr><td colspan="4" class="text-muted">Sem movimentos.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Entradas e saídas</h5>
          <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
              <thead>
                <tr>
                  <th>Data</th>
                  <th class="text-end">Ação</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($accessRows as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['scanned_at']))) ?></td>
                    <td class="text-end">
                      <?php if ($row['action'] === 'IN'): ?>
                        <span class="badge text-bg-success">Entrada</span>
                      <?php else: ?>
                        <span class="badge text-bg-danger">Saída</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$accessRows): ?>
                  <tr><td colspan="2" class="text-muted">Sem registos de entradas/saídas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <h5 class="card-title mb-3">Senhas de Cantina</h5>
          <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
              <thead>
                <tr>
                  <th>Data</th>
                  <th>Tipo</th>
                  <th>Status</th>
                  <th class="text-end">Staff</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($canteenTickets as $ticket): ?>
                  <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['reserved_at']))) ?></td>
                    <td><?= htmlspecialchars($ticket['ticket_type']) ?></td>
                    <td><?= htmlspecialchars($ticket['status']) ?></td>
                    <td class="text-end"><?= htmlspecialchars($ticket['staff_name'] ?: '-') ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$canteenTickets): ?>
                  <tr><td colspan="4" class="text-muted">Sem senhas de cantina registadas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php page_footer();
