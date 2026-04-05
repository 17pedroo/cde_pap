<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
require_staff();

$errors = [];
$success = null;
$name = '';
$student_number = '';
$selected_students = [];

$students = [];
$stmt = $pdo->prepare('SELECT id, student_number, name FROM users WHERE role = ? ORDER BY student_number');
$stmt->execute(['student']);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['delete_guardian_id'])) {
  $delete_id = (int)$_POST['delete_guardian_id'];
  $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = ?');
  $stmt->execute([$delete_id, 'guardian']);
  if ($stmt->fetch()) {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$delete_id]);
    $success = 'Encarregado removido com sucesso.';
  } else {
    $errors[] = 'Encarregado não encontrado.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_guardian'])) {
  $student_number = trim($_POST['student_number'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $password = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';
  $selected_students = array_map('intval', $_POST['students'] ?? []);

  if ($student_number === '') {
    $errors[] = 'Login do encarregado é obrigatório.';
  }
  if ($name === '') {
    $errors[] = 'Nome do encarregado é obrigatório.';
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
      $errors[] = 'Já existe um utilizador com este login.';
    }
  }

  if (empty($errors)) {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $qr_secret = bin2hex(random_bytes(16));

    $stmt = $pdo->prepare('INSERT INTO users (role, student_number, name, password_hash, qr_secret) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(['guardian', $student_number, $name, $password_hash, $qr_secret]);
    $guardian_id = (int)$pdo->lastInsertId();

    if ($selected_students) {
      $stmt = $pdo->prepare('INSERT IGNORE INTO guardian_students (guardian_id, student_id) VALUES (?, ?)');
      foreach ($selected_students as $student_id) {
        $stmt->execute([$guardian_id, $student_id]);
      }
    }

    $success = 'Encarregado registado com sucesso.';
    $name = '';
    $student_number = '';
    $selected_students = [];
  }
}

$guardians = [];
$stmt = $pdo->query(
  'SELECT g.id, g.student_number, g.name,
   GROUP_CONCAT(u.name SEPARATOR ", ") AS students
   FROM users g
   LEFT JOIN guardian_students gs ON g.id = gs.guardian_id
   LEFT JOIN users u ON u.id = gs.student_id
   WHERE g.role = "guardian"
   GROUP BY g.id
   ORDER BY g.name'
);
$guardians = $stmt->fetchAll(PDO::FETCH_ASSOC);

page_header('Gestão de Encarregados');

function eur($cents) {
  return number_format($cents / 100, 2, ',', '.') . ' €';
}
?>
<div class="mb-3">
  <h4 class="mb-0">Gestão de Encarregados</h4>
  <div class="text-muted">Registar encarregados de educação e associá-los a alunos.</div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Novo encarregado</h5>
        <form method="post">
          <input type="hidden" name="create_guardian" value="1">
          <div class="mb-3">
            <label class="form-label">Login do encarregado</label>
            <input type="text" name="student_number" class="form-control" value="<?= htmlspecialchars($student_number) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Nome completo</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Senha</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirmar senha</label>
            <input type="password" name="password_confirm" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Alunos associados</label>
            <input type="text" id="studentSearch" class="form-control mb-3" placeholder="Pesquisar aluno por nome ou número">
            <div class="border rounded p-2" id="studentList" style="max-height: 280px; overflow:auto;">
              <?php foreach ($students as $student): ?>
                <div class="form-check student-item">
                  <input class="form-check-input" type="checkbox" name="students[]" value="<?= $student['id'] ?>"
                    id="student-<?= $student['id'] ?>"
                    <?= in_array($student['id'], $selected_students) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="student-<?= $student['id'] ?>">
                    <?= htmlspecialchars($student['name'] . ' (' . $student['student_number'] . ')') ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="d-grid">
            <button class="btn btn-primary">Registar encarregado</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Encarregados registados</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Login</th>
                <th>Nome</th>
                <th>Alunos</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($guardians as $guardian): ?>
                <tr>
                  <td><?= htmlspecialchars($guardian['student_number']) ?></td>
                  <td><?= htmlspecialchars($guardian['name']) ?></td>
                  <td><?= htmlspecialchars($guardian['students'] ?: '-') ?></td>
                  <td class="text-end">
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $guardian['id'] ?>" data-name="<?= htmlspecialchars($guardian['name']) ?>">Apagar</button>
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

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Tem certeza que deseja apagar o encarregado <strong id="guardianName"></strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form method="post" style="display:inline;">
          <input type="hidden" name="delete_guardian_id" id="deleteGuardianId">
          <button type="submit" class="btn btn-danger">Apagar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
const deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('show.bs.modal', function (event) {
  const button = event.relatedTarget;
  const id = button.getAttribute('data-id');
  const name = button.getAttribute('data-name');
  document.getElementById('guardianName').textContent = name;
  document.getElementById('deleteGuardianId').value = id;
});

const studentSearch = document.getElementById('studentSearch');
studentSearch?.addEventListener('input', function () {
  const query = this.value.trim().toLowerCase();
  document.querySelectorAll('#studentList .student-item').forEach(item => {
    const label = item.querySelector('.form-check-label');
    const text = label?.textContent?.toLowerCase() ?? '';
    item.style.display = text.includes(query) ? '' : 'none';
  });
});
</script>

<?php page_footer();
