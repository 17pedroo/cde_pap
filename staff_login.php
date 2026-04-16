<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth_layout.php";

if (!empty($_SESSION["user_id"])) {
  $role = $_SESSION["role"] ?? "";
  if ($role === "staff" || $role === "admin") {
    header("Location: admin_dashboard.php");
  } elseif ($role === "student") {
    header("Location: dashboard.php");
  } elseif ($role === "guardian") {
    header("Location: guardian_dashboard.php");
  }
  exit;
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $password = $_POST["password"] ?? "";

  $stmt = $pdo->prepare("SELECT id, role, name, password_hash FROM users WHERE role IN ('staff','admin') AND is_active=1 LIMIT 1");
  $stmt->execute();
  $u = $stmt->fetch();

  if ($u && password_verify($password, $u["password_hash"])) {
    session_regenerate_id(true);
    $_SESSION["user_id"] = (int)$u["id"];
    $_SESSION["role"] = $u["role"];
    $_SESSION["name"] = $u["name"];
    header("Location: admin_dashboard.php");
    exit;
  } else {
    $error = "Palavra-passe inválida.";
  }
}
?>
<?php auth_page_header(
  "Acesso - Admin",
  "Acesso de admin",
  "Entrar na administracao escolar",
  "Entre num painel central com acessos, cantina, bar, gestão e relatórios.",
  "staff"
); ?>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="vstack gap-3">
  <div>
    <label class="form-label">Palavra-passe</label>
    <input class="form-control" type="password" name="password" required>
  </div>

  <div class="d-grid pt-2">
    <button class="btn btn-primary btn-lg">Entrar</button>
  </div>

  <div class="auth-links">
    <a href="login.php">Sou aluno</a>
    <a href="guardian_login.php">Sou encarregado</a>
  </div>
</form>

<?php auth_page_footer(); ?>