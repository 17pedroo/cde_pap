<?php
require __DIR__ . "/includes/config.php";

if (!empty($_SESSION["user_id"])) {
  $role = $_SESSION["role"] ?? "";
  if ($role === "staff" || $role === "admin") {
    header("Location: register_student.php");
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
    header("Location: register_student.php");
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
<title>Acesso - Portaria</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  :root {
    color-scheme: light;
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  }
  body{
    min-height:100vh;
    margin:0;
    position:relative;
    overflow:hidden;
    color: #111827;
    background: rgba(23, 26, 37, 0.32);
  }

  body::before{
    content:"";
    position:absolute;
    inset:0;
    background: radial-gradient(circle at 24% 24%, rgba(255,255,255,0.14), transparent 18%),
                radial-gradient(circle at 82% 80%, rgba(255,255,255,0.08), transparent 20%);
    pointer-events:none;
    z-index:1;
  }
  body::after {
    content:"";
    position:absolute;
    inset:0;
    background:
      linear-gradient(135deg, rgba(23, 26, 37, 0.88), rgba(239, 234, 221, 0.16)),
      radial-gradient(circle at top left, rgba(255, 247, 236, 0.16), transparent 18%),
      radial-gradient(circle at bottom right, rgba(120, 113, 95, 0.12), transparent 22%),
      url("assets/img/escola.jpg") center/cover no-repeat;
    background-blend-mode: overlay, overlay, overlay, normal;
    filter: blur(4px);
    transform: scale(1.02);
    z-index:0;
  }

  .wrap{
    position:relative;
    z-index:1;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:2rem 1.5rem;
  }

  .card{
    max-width:520px;
    width:100%;
    border-radius:28px;
    background: rgba(255,255,255,0.92);
    border: 1px solid rgba(15,23,42,0.08);
    box-shadow: 0 32px 80px rgba(15,23,42,0.14);
    backdrop-filter: blur(18px);
  }

  .footer-logos {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15,23,42,0.92);
    border-top: 1px solid rgba(255,255,255,0.08);
    padding: 10px 14px;
    z-index: 2;
    backdrop-filter: blur(18px);
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
    opacity: .88;
    filter: saturate(0.9);
  }

  .form-label {
    color: #0f172a !important;
    font-weight: 600;
    opacity: 1 !important;
  }

  .form-control {
    border-radius: 1rem;
    border: 1px solid rgba(226,232,240,0.18);
    background: rgba(255,255,255,0.95);
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
  }

  .form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 0.2rem rgba(59,130,246,0.18);
  }

  .btn {
    border-radius: 999px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 32px rgba(59,130,246,0.18);
  }

  @media (max-width: 576px){
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
      <div class="text-center text-muted small mb-3">Acesso Administrador</div>

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