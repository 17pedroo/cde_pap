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

function translateTicketType(string $type): string {
  return match ($type) {
    'almoco' => 'Almoço',
    default => ucfirst($type),
  };
}

function translateTicketStatus(string $status): string {
  return match ($status) {
    'active' => 'Ativo',
    'scanned' => 'Lido',
    'cancelled' => 'Cancelado',
    default => ucfirst($status),
  };
}

$transaction_count = count($transactions);
$access_count = count($accessRows);
$ticket_count = count($canteenTickets);
$latest_transaction = $transactions[0] ?? null;
$latest_access = $accessRows[0] ?? null;
$latest_ticket = $canteenTickets[0] ?? null;

page_header('Encarregado - Saldo e Movimentos');
?>
<div class="hero-banner">
  <div>
    <span class="hero-label"><i class="bi bi-people-fill"></i>Area do encarregado</span>
    <h2>Gestao do aluno associado</h2>
    <p><?= $selected_student ? 'Acompanhe saldo, movimentos, acessos e cantina de ' . htmlspecialchars($selected_student['name']) . '.' : 'Consulte saldo, movimentos e carregamentos do aluno associado.' ?></p>
  </div>
  <div class="hero-actions">
    <?php if ($selected_student): ?>
      <a class="btn btn-primary" href="#topup-form">Carregar saldo</a>
    <?php endif; ?>
    <a class="btn btn-outline-light" href="guardian_dashboard.php<?= $selected_student_id ? '?student_id=' . $selected_student_id : '' ?>">Atualizar</a>
  </div>
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
      <div class="card shadow-sm metric-card">
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
      <div class="card shadow-sm mt-3 metric-card" id="topup-form">
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
      <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
          <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
              <div class="text-muted">Movimentos</div>
              <div class="display-6 mb-1"><?= $transaction_count ?></div>
              <div class="text-muted small">
                <?php if ($latest_transaction): ?>
                  Último: <?= htmlspecialchars(translateTransactionType($latest_transaction['type'])) ?> · <?= htmlspecialchars(date('d/m H:i', strtotime($latest_transaction['created_at']))) ?>
                <?php else: ?>
                  Sem movimentos registados.
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
              <div class="text-muted">Entradas e saídas</div>
              <div class="display-6 mb-1"><?= $access_count ?></div>
              <div class="text-muted small">
                <?php if ($latest_access): ?>
                  Último: <?= $latest_access['action'] === 'IN' ? 'Entrada' : 'Saída' ?> · <?= htmlspecialchars(date('d/m H:i', strtotime($latest_access['scanned_at']))) ?>
                <?php else: ?>
                  Sem acessos registados.
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="card shadow-sm metric-card h-100">
            <div class="card-body">
              <div class="text-muted">Cantina</div>
              <div class="display-6 mb-1"><?= $ticket_count ?></div>
              <div class="text-muted small">
                <?php if ($latest_ticket): ?>
                  Último: <?= htmlspecialchars(translateTicketStatus($latest_ticket['status'])) ?> · <?= htmlspecialchars(date('d/m H:i', strtotime($latest_ticket['reserved_at']))) ?>
                <?php else: ?>
                  Sem senhas registadas.
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
              <h5 class="card-title mb-1">Acompanhamento do aluno</h5>
              <div class="text-muted small">Os registos ficam agora separados por tema para reduzir scroll e facilitar a leitura no telemóvel.</div>
            </div>
            <span class="badge text-bg-secondary"><?= htmlspecialchars($selected_student['name']) ?></span>
          </div>

          <ul class="nav nav-pills gap-2 mb-3" id="guardianTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="guardian-movements-tab" data-bs-toggle="pill" data-bs-target="#guardian-movements" type="button" role="tab" aria-controls="guardian-movements" aria-selected="true">
                Movimentos <span class="badge text-bg-light ms-2"><?= $transaction_count ?></span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="guardian-access-tab" data-bs-toggle="pill" data-bs-target="#guardian-access" type="button" role="tab" aria-controls="guardian-access" aria-selected="false">
                Acessos <span class="badge text-bg-light ms-2"><?= $access_count ?></span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="guardian-canteen-tab" data-bs-toggle="pill" data-bs-target="#guardian-canteen" type="button" role="tab" aria-controls="guardian-canteen" aria-selected="false">
                Cantina <span class="badge text-bg-light ms-2"><?= $ticket_count ?></span>
              </button>
            </li>
          </ul>

          <div class="tab-content" id="guardianTabsContent">
            <div class="tab-pane fade show active" id="guardian-movements" role="tabpanel" aria-labelledby="guardian-movements-tab" tabindex="0">
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

            <div class="tab-pane fade" id="guardian-access" role="tabpanel" aria-labelledby="guardian-access-tab" tabindex="0">
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

            <div class="tab-pane fade" id="guardian-canteen" role="tabpanel" aria-labelledby="guardian-canteen-tab" tabindex="0">
              <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Data</th>
                      <th>Tipo</th>
                      <th>Estado</th>
                      <th class="text-end">Staff</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($canteenTickets as $ticket): ?>
                      <tr>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticket['reserved_at']))) ?></td>
                        <td><?= htmlspecialchars(translateTicketType($ticket['ticket_type'])) ?></td>
                        <td><?= htmlspecialchars(translateTicketStatus($ticket['status'])) ?></td>
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
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php page_footer();
