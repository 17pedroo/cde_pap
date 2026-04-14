<?php
require __DIR__ . "/includes/config.php";

if (!empty($_SESSION["user_id"])) {
  $role = $_SESSION["role"] ?? "";
  if ($role === "student") {
    header("Location: dashboard.php");
  } elseif ($role === "guardian") {
    header("Location: guardian_dashboard.php");
  } elseif ($role === "staff" || $role === "admin") {
    header("Location: scanner.php");
  }
  exit;
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $student_number = trim($_POST["student_number"] ?? "");
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
<!doctype html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Cartão Digital</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  :root {
    color-scheme: light;
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  }
  body {
    min-height: 100vh;
    margin: 0;
    position: relative;
    overflow: hidden;
    color: #111827;
    background: rgba(23, 26, 37, 0.32);
  }
  body::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 25% 25%, rgba(255,255,255,0.16), transparent 18%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.08), transparent 20%);
    pointer-events: none;
    z-index: 1;
  }
  body::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
      linear-gradient(135deg, rgba(23, 26, 37, 0.88), rgba(241, 245, 249, 0.18)),
      radial-gradient(circle at top left, rgba(255, 250, 240, 0.18), transparent 18%),
      radial-gradient(circle at bottom right, rgba(120, 113, 108, 0.14), transparent 22%),
      url("assets/img/escola.jpg") center/cover no-repeat;
    background-blend-mode: overlay, overlay, overlay, normal;
    filter: blur(4px);
    transform: scale(1.02);
    z-index: 0;
  }
  .login-container {
    position: relative;
    z-index: 1;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.5rem;
  }
  .login-card {
    width: 100%;
    max-width: 520px;
    border-radius: 28px;
    background: rgba(255,255,255,0.92);
    border: 1px solid rgba(15,23,42,0.08);
    box-shadow: 0 32px 80px rgba(15,23,42,0.14);
    backdrop-filter: blur(18px);
  }
  .login-card .card-body {
    padding: 2rem;
  }
  .login-card img {
    height: 52px;
    object-fit: contain;
  }
  .login-card .form-control {
    border-radius: 1rem;
    border: 1px solid rgba(15,23,42,0.12);
    background: rgba(255,255,255,0.96);
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
  }
  .login-card .form-label {
    color: #334155;
    font-weight: 600;
  }
  .login-card .text-muted {
    color: #64748b !important;
  }
  .login-card .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.16);
  }
  .login-card .btn {
    border-radius: 999px;
    padding: 0.95rem 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    background-color: #0d6efd;
    border-color: #0d6efd;
  }
  .login-card .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 32px rgba(183,121,31,0.22);
    background-color: #a66f1c;
  }
  .footer-logos {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.92);
    border-top: 1px solid rgba(15,23,42,0.08);
    padding: 10px 14px;
    z-index: 2;
    backdrop-filter: blur(18px);
  }
  .footer-logos .inner {
    max-width: 980px;
    margin: 0 auto;
  }
  .footer-logos img {
    width: 100%;
    height: 42px;
    object-fit: contain;
    display: block;
    opacity: 0.92;
  }
  @media (max-width: 576px) {
    .login-container { padding: 1.5rem; }
    .login-card { border-radius: 20px; }
    .footer-logos img { height: 34px; }
  }
</style>
</head>
<body>

<div class="login-container">
  <div class="card shadow-lg login-card">
    <div class="card-body p-4">

      <!-- Logos da escola (menor e mais elegante) -->
      <img
        src="assets/img/topo_escola.jpg"
        alt="Logótipos da escola"
        class="mb-3"
        style="height:52px; width:100%; object-fit:contain; opacity:.95;"
      >

      <h3 class="mb-1 text-center fw-semibold">Cartão Digital</h3>
      <p class="text-muted text-center small mb-3">Sistema de gestão escolar</p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Número de aluno</label>
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
          <a class="small text-decoration-none text-light-emphasis me-3" href="staff_login.php">Sou administrador</a>
          <a class="small text-decoration-none text-light-emphasis" href="guardian_login.php">Sou encarregado</a>
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