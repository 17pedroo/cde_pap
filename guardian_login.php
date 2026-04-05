<?php
require __DIR__ . "/includes/config.php";

if (!empty($_SESSION["user_id"])) {
  $role = $_SESSION["role"] ?? "";
  if ($role === "guardian") {
    header("Location: guardian_dashboard.php");
  } elseif ($role === "student") {
    header("Location: dashboard.php");
  } elseif ($role === "staff" || $role === "admin") {
    header("Location: scanner.php");
  }
  exit;
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $student_number = trim($_POST["student_number"] ?? "");
  $password = $_POST["password"] ?? "";

  $stmt = $pdo->prepare("SELECT * FROM users WHERE student_number=? AND role='guardian'");
  $stmt->execute([$student_number]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user["password_hash"])) {
    session_regenerate_id(true);
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["name"] = $user["name"];
    $_SESSION["role"] = $user["role"];
    header("Location: guardian_dashboard.php");
    exit;
  } else {
    $error = "Credenciais inválidas.";
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Encarregado</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  body {
    min-height: 100vh;
    background: url("assets/img/escola.jpg") center/cover no-repeat;
    position: relative;
    padding-bottom: 70px;
  }

  body::before {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.42);
    backdrop-filter: blur(6px);
  }

  .login-container {
    position: relative;
    z-index: 2;
    min-height: calc(100vh - 70px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }

  .login-card {
    width: 100%;
    max-width: 440px;
    border-radius: 14px;
  }

  .footer-logos {
    position: fixed;
    left: 0; right: 0; bottom: 0;
    background: rgba(255,255,255,0.85);
    border-top: 1px solid rgba(0,0,0,0.07);
    padding: 6px 12px;
    z-index: 3;
    backdrop-filter: blur(6px);
  }

  .footer-logos .inner {
    max-width: 980px;
    margin: 0 auto;
  }

  .footer-logos img{
    width: 100%;
    height: 44px;
    object-fit: contain;
    display: block;
    opacity: .95;
  }

  @media (max-width: 576px){
    body { padding-bottom: 62px; }
    .login-container { min-height: calc(100vh - 62px); }
    .footer-logos img{ height: 38px; }
  }
</style>
</head>
<body>

<div class="login-container">
  <div class="card shadow-lg login-card">
    <div class="card-body p-4">

      <img
        src="assets/img/topo_escola.jpg"
        alt="Logótipos da escola"
        class="mb-3"
        style="height:52px; width:100%; object-fit:contain; opacity:.95;"
      >

      <h3 class="mb-1 text-center fw-semibold">Cartão Digital</h3>
      <p class="text-muted text-center small mb-3">Acesso para encarregados de educação</p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Login do encarregado</label>
          <input type="text" name="student_number" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Palavra-passe</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <div class="d-grid">
          <button class="btn btn-primary">Entrar</button>
        </div>

        <div class="text-center mt-3">
          <a class="small text-decoration-none text-light-emphasis me-3" href="login.php">Sou aluno</a>
          <a class="small text-decoration-none text-light-emphasis" href="staff_login.php">Sou da portaria</a>
        </div>
      </form>
    </div>
  </div>
</div>

<footer class="footer-logos">
  <div class="inner">
    <img src="assets/img/rodape_pessoas2030.jpg" alt="Logótipos - Pessoas 2030 / Portugal 2030 / União Europeia / EQAVET">
  </div>
</footer>

</body>
</html>
