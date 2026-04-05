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
    .app-wrap { max-width: 980px; }
    .brand-sub { font-size: .78rem; color: #6c757d; margin-top:-2px; }

    /* Faixas de logótipos (TOP e FOOTER) */
    .logos-bar { background:#fff; }
    .logos-inner { max-width: 980px; margin: 0 auto; padding: 10px 12px; }

    /* Mantém proporção SEM esticar e limita altura */
    .logo-img {
      width: 100%;
      height: 78px;           /* altura no PC */
      object-fit: contain;    /* não deforma */
      display: block;
    }
    .logo-img.footer { height: 70px; }

    /* Mobile: baixa um pouco a altura */
    @media (max-width: 576px) {
      .logo-img { height: 56px; }
      .logo-img.footer { height: 52px; }
      .logos-inner { padding: 8px 10px; }
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
      <a class="btn btn-outline-primary btn-sm" href="sobre.php">Sobre</a>

      <?php if (($_SESSION["role"] ?? "") === "staff" || ($_SESSION["role"] ?? "") === "admin"): ?>
        <a class="btn btn-outline-dark btn-sm" href="scanner.php">Scanner</a>
        <?php if (($_SESSION["role"] ?? "") === "student"): ?>
          <a class="btn btn-outline-dark btn-sm" href="portaria_logs.php">Leituras</a>
        <?php endif; ?>
        <a class="btn btn-outline-dark btn-sm" href="register_student.php">Alunos</a>
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