<?php
function page_header(string $title) { ?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- favicon simples -->
  <link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%20viewBox%3D%270%200%20100%20100%27%3E%3Crect%20width%3D%27100%27%20height%3D%27100%27%20rx%3D%2720%27%20fill%3D%27%230d6efd%27/%3E%3Ctext%20x%3D%2750%27%20y%3D%2762%27%20font-size%3D%2746%27%20text-anchor%3D%27middle%27%20fill%3D%27white%27%20font-family%3D%27Arial%27%3ECD%3C/text%3E%3C/svg%3E">

  <title><?= htmlspecialchars($title) ?></title>

  <style>
    :root {
      color-scheme: light;
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: #111827;
    }

    body {
      background: radial-gradient(circle at top left, rgba(13, 110, 253, 0.12), transparent 24%),
                  radial-gradient(circle at top right, rgba(102, 16, 242, 0.09), transparent 30%),
                  #f8fafc;
      min-height: 100vh;
    }

    .app-wrap { max-width: 1100px; }
    .brand-sub { font-size: .78rem; color: #6c757d; margin-top: -2px; }

    .logos-bar {
      background: rgba(255,255,255,0.96);
      box-shadow: inset 0 -1px rgba(15,23,42,0.04);
    }
    .logos-inner { max-width: 1100px; margin: 0 auto; padding: 12px 16px; }

    .logo-img {
      width: 100%;
      height: 78px;
      object-fit: contain;
      display: block;
    }
    .logo-img.footer { height: 70px; }

    .navbar {
      background: rgba(255,255,255,0.94) !important;
      backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(15,23,42,0.08);
      box-shadow: 0 24px 70px rgba(15,23,42,0.06);
    }

    .navbar-brand {
      letter-spacing: 0.04em;
    }

    .btn {
      transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }
    .btn:hover {
      transform: translateY(-1px);
    }

    .card {
      border: none;
      border-radius: 1.15rem;
      box-shadow: 0 20px 48px rgba(15,23,42,0.08);
    }
    .card.shadow-sm {
      box-shadow: 0 14px 32px rgba(15,23,42,0.06);
    }

    .table th,
    .table td {
      vertical-align: middle;
    }

    @media (max-width: 576px) {
      .logo-img { height: 56px; }
      .logo-img.footer { height: 52px; }
      .logos-inner { padding: 10px 12px; }
    }
  </style>
</head>
<body class="bg-light">

<!-- TOP LOGOS -->
<header class="logos-bar border-bottom">
  <div class="logos-inner">
    <img class="logo-img" src="assets/img/topo_escola.jpg"
         alt="Logótipos - Agrupamento de Escolas Ferreira de Castro" loading="lazy">
  </div>
</header>

<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container app-wrap">
    <div class="d-flex flex-column">
      <span class="navbar-brand fw-semibold mb-0">Cartão Digital</span>
      <div class="brand-sub">Escola Ferreira de Castro • Oliveira de Azeméis</div>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
      <?php if (($_SESSION["role"] ?? "") === "student"): ?>
        <a class="btn btn-outline-primary btn-sm" href="dashboard.php">Dashboard</a>
        <a class="btn btn-outline-primary btn-sm" href="movimentos.php">Movimentos</a>
        <a class="btn btn-outline-primary btn-sm" href="acessos.php">Acessos</a>
      <?php endif; ?>
      <?php if (($_SESSION["role"] ?? "") === "guardian"): ?>
        <a class="btn btn-outline-primary btn-sm" href="guardian_dashboard.php">Aluno</a>
      <?php endif; ?>
      <a class="btn btn-outline-primary btn-sm" href="sobre.php">Sobre</a>

      <?php if (($_SESSION["role"] ?? "") === "staff" || ($_SESSION["role"] ?? "") === "admin"): ?>
        <a class="btn btn-outline-dark btn-sm" href="scanner.php">Scanner</a>
        <a class="btn btn-outline-dark btn-sm" href="portaria_logs.php">Leituras</a>
        <a class="btn btn-outline-dark btn-sm" href="register_student.php">Alunos</a>
        <a class="btn btn-outline-dark btn-sm" href="register_guardian.php">Encarregados</a>
      <?php endif; ?>

      <a class="btn btn-outline-secondary btn-sm" href="logout.php">Sair</a>
    </div>
  </div>
</nav>

<div class="container app-wrap py-4">
<?php }

function page_footer() { ?>
</div>

<!-- FOOTER LOGOS -->
<footer class="logos-bar border-top mt-4">
  <div class="logos-inner">
    <img class="logo-img footer" src="assets/img/rodape_pessoas2030.jpg"
         alt="Logótipos - Pessoas 2030 / Portugal 2030 / União Europeia / EQAVET" loading="lazy">
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php }