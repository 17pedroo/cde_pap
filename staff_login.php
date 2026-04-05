<?php
require __DIR__ . "/includes/config.php";

if (!empty($_SESSION["user_id"])) {
  $role = $_SESSION["role"] ?? "";
  if ($role === "staff" || $role === "admin") {
    header("Location: scanner.php");
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
    header("Location: scanner.php");
    exit;
  } else {
    $error = "Palavra-passe inválida.";
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Portaria</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  body{
    min-height:100vh;
    background:url("assets/img/escola.jpg") center/cover no-repeat;
    position:relative;
    padding-bottom: 70px;
  }

  body::before{
    content:"";
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.42);
    backdrop-filter: blur(6px);
  }

  .wrap{
    position:relative;
    z-index:2;
    min-height: calc(100vh - 70px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
  }

  .card{
    max-width:440px;
    width:100%;
    border-radius:14px;
  }

  .footer-logos {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
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
    .wrap { min-height: calc(100vh - 62px); }
    .footer-logos img{ height: 38px; }
  }
</style>
</head>
<body>

<div class="wrap">
  <div class="card shadow-lg">
    <div class="card-body p-4">

      <img
        src="assets/img/topo_escola.jpg"
        alt="Logótipos da escola"
        class="mb-3"
        style="height:52px; width:100%; object-fit:contain; opacity:.95;"
      >

      <h3 class="text-center mb-1 fw-semibold">Cartão Digital</h3>
      <div class="text-center text-muted small mb-3">Acesso Portaria</div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <label class="form-label">Palavra-passe</label>
        <input class="form-control" type="password" name="password" required>

        <div class="d-grid mt-3">
          <button class="btn btn-dark">Entrar</button>
        </div>

        <div class="text-center mt-3">
          <a class="small text-decoration-none text-light-emphasis" href="login.php">Sou aluno</a>
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