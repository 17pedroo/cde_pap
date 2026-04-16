<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth_layout.php";

if (!empty($_SESSION["user_id"])) {
  $role = $_SESSION["role"] ?? "";
  if ($role === "student") {
    header("Location: dashboard.php");
  } elseif ($role === "guardian") {
    header("Location: guardian_dashboard.php");
  } elseif ($role === "staff" || $role === "admin") {
    header("Location: admin_dashboard.php");
  }
  exit;
}

$error = null;
$student_number = trim($_POST["student_number"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $password = $_POST["password"] ?? "";

  $stmt = $pdo->prepare("SELECT * FROM users WHERE student_number=? AND role='student'");
  $stmt->execute([$student_number]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user["password_hash"])) {
    session_regenerate_id(true);
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["name"] = $user["name"];
    $_SESSION["role"] = $user["role"];
    header("Location: dashboard.php");
    exit;
  } else {
    $error = "Credenciais inválidas.";
  }
}
?>
<?php auth_page_header(
  "Acesso - Cartao Digital",
  "Acesso de aluno",
  "Entrar no painel digital",
  "Consulta saldo, QR e movimentos num ambiente pensado para telemovel e desktop.",
  "student"
); ?>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="vstack gap-3">
  <div>
    <label class="form-label">Numero de aluno</label>
    <input type="text" name="student_number" class="form-control" value="<?= htmlspecialchars($student_number) ?>" required>
  </div>

  <div>
    <label class="form-label">Palavra-passe</label>
    <input type="password" name="password" class="form-control" required>
  </div>

  <div class="d-grid pt-2">
    <button class="btn btn-primary btn-lg">Entrar</button>
  </div>

  <div class="auth-links">
    <a href="staff_login.php">Sou administrador</a>
    <a href="guardian_login.php">Sou encarregado</a>
  </div>
</form>

<?php auth_page_footer(); ?>