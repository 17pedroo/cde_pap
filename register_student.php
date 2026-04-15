<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require __DIR__ . "/includes/qr.php";
require_staff();

$errors = [];
$success = null;
$student_number = '';
$name = '';

$students = [];
$stmt = $pdo->prepare('SELECT u.id, u.student_number, u.name, w.balance_cents FROM users u LEFT JOIN wallets w ON u.id = w.user_id WHERE u.role = ? ORDER BY u.student_number');
$stmt->execute(['student']);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['delete_student_id'])) {
  $id = (int)$_POST['delete_student_id'];
  $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = ?');
  $stmt->execute([$id, 'student']);
  if ($stmt->fetch()) {
    $stmt = $pdo->prepare('DELETE FROM guardian_students WHERE student_id = ?');
    $stmt->execute([$id]);
    $stmt = $pdo->prepare('DELETE FROM wallet_transactions WHERE user_id = ?');
    $stmt->execute([$id]);
    $stmt = $pdo->prepare('DELETE FROM access_logs WHERE user_id = ?');
    $stmt->execute([$id]);
    $stmt = $pdo->prepare('DELETE FROM canteen_tickets WHERE student_id = ?');
    $stmt->execute([$id]);
    $stmt = $pdo->prepare('DELETE FROM wallets WHERE user_id = ?');
    $stmt->execute([$id]);
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $success = 'Aluno removido com sucesso.';
    // Refresh students list
    $stmt = $pdo->prepare('SELECT u.id, u.student_number, u.name, w.balance_cents FROM users u LEFT JOIN wallets w ON u.id = w.user_id WHERE u.role = ? ORDER BY u.student_number');
    $stmt->execute(['student']);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $errors[] = 'Aluno não encontrado.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $student_number = trim($_POST['student_number'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $password = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';

  if ($student_number === '') {
    $errors[] = 'Número de estudante é obrigatório.';
  } elseif (!preg_match('/^[A-Za-z0-9_-]{3,20}$/', $student_number)) {
    $errors[] = 'Número de estudante deve ter entre 3 e 20 caracteres sem espaços.';
  }
  if ($name === '') {
    $errors[] = 'Nome do aluno é obrigatório.';
  }
  if ($password === '') {
    $errors[] = 'Senha é obrigatória.';
  }
  if ($password !== $password_confirm) {
    $errors[] = 'As senhas não coincidem.';
  }

  if (empty($errors)) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE student_number = ? LIMIT 1');
    $stmt->execute([$student_number]);

    if ($stmt->fetchColumn()) {
      $errors[] = 'Já existe um aluno com este número.';
    } else {
      $password_hash = password_hash($password, PASSWORD_DEFAULT);
      $qr_secret = bin2hex(random_bytes(16));

      $stmt = $pdo->prepare('INSERT INTO users (role, student_number, name, password_hash, qr_secret) VALUES (?, ?, ?, ?, ?)');
      $stmt->execute(['student', $student_number, $name, $password_hash, $qr_secret]);
      $user_id = (int)$pdo->lastInsertId();

      $stmt = $pdo->prepare('INSERT INTO wallets (user_id, balance_cents) VALUES (?, 0)');
      $stmt->execute([$user_id]);

      $success = 'Aluno registado com sucesso.';
      $student_number = '';
      $name = '';
      // Refresh students list
      $stmt = $pdo->prepare('SELECT u.id, u.student_number, u.name, w.balance_cents FROM users u LEFT JOIN wallets w ON u.id = w.user_id WHERE u.role = ? ORDER BY u.student_number');
      $stmt->execute(['student']);
      $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  }
}
page_header("Gestão de Alunos");

function eur($cents) {
  return number_format($cents/100, 2, ',', '.') . " €";
}
?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<div class="mb-3">
  <h4 class="mb-0">Gestão de Alunos</h4>
  <div class="text-muted">Registar e gerir alunos do sistema.</div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES); ?></div>
<?php endif; ?>

<div class="row g-3">
  <!-- ALUNOS -->
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h5 class="card-title mb-1">Alunos Registados</h5>
            <div class="text-muted small">Lista de todos os alunos</div>
          </div>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">Registar Aluno</button>
        </div>

        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Número de Estudante</th>
                <th>Nome</th>
                <th>Saldo (€)</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $student): ?>
                <tr>
                  <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                  <td><?php echo htmlspecialchars($student['name']); ?></td>
                  <td><?php echo eur($student['balance_cents']); ?></td>
                  <td class="text-end">
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $student['id']; ?>" data-name="<?php echo htmlspecialchars($student['name']); ?>">Apagar</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>


</div>

<!-- Modal Adicionar -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addStudentModalLabel">Registar Novo Aluno</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <div class="mb-3">
            <label for="student_number" class="form-label">Número de estudante</label>
            <input type="text" id="student_number" name="student_number" class="form-control" value="<?php echo htmlspecialchars($student_number, ENT_QUOTES); ?>" required>
          </div>

          <div class="mb-3">
            <label for="name" class="form-label">Nome completo</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>" required>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Senha</label>
            <input type="password" id="password" name="password" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="password_confirm" class="form-label">Confirmar senha</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
          </div>

          <div class="d-grid gap-2">
            <button class="btn btn-primary" type="submit">Registar aluno</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Tem certeza que deseja apagar o aluno <strong id="studentName"></strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form method="post" style="display:inline;">
          <input type="hidden" name="delete_student_id" id="deleteStudentId">
          <button type="submit" class="btn btn-danger">Apagar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
<?php if ($success): ?>
setTimeout(() => {
  location.reload();
}, 1500);
<?php endif; ?>

const deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('show.bs.modal', function (event) {
  const button = event.relatedTarget;
  const id = button.getAttribute('data-id');
  const name = button.getAttribute('data-name');
  document.getElementById('studentName').textContent = name;
  document.getElementById('deleteStudentId').value = id;
});
</script>

<?php page_footer();
