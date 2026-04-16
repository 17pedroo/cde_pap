<?php
function auth_page_highlights(string $theme): array {
  return match ($theme) {
    "guardian" => [
      ["icon" => "bi-wallet2", "text" => "Acompanhe saldo, movimentos e carregamentos do aluno."],
      ["icon" => "bi-building-check", "text" => "Consulte acessos e utilizacao da cantina no mesmo painel."],
      ["icon" => "bi-phone", "text" => "Interface preparada para escritorio e telemovel."],
    ],
    "staff" => [
      ["icon" => "bi-grid-1x2-fill", "text" => "Entrada central para acessos, cantina, bar, produtos e relatórios."],
      ["icon" => "bi-kanban", "text" => "Navegação administrativa organizada por módulos e tarefas."],
      ["icon" => "bi-tablet-landscape", "text" => "Fluxo adaptado a secretária, receção e dispositivos móveis."],
    ],
    default => [
      ["icon" => "bi-qr-code", "text" => "Mostre o QR dinamico e use o cartao digital em segundos."],
      ["icon" => "bi-clock-history", "text" => "Veja saldo, movimentos e acessos num unico lugar."],
      ["icon" => "bi-phone", "text" => "Experiencia consistente entre smartphone e computador."],
    ],
  };
}

function auth_page_header(string $title, string $eyebrow, string $heading, string $description, string $theme = "student") {
  $highlights = auth_page_highlights($theme);
  ?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <title><?= htmlspecialchars($title) ?></title>

  <style>
    :root {
      color-scheme: light;
      --font-heading: "Manrope", "Segoe UI", sans-serif;
      --font-body: "IBM Plex Sans", "Segoe UI", sans-serif;
      --auth-bg: #edf2f7;
      --auth-surface: rgba(255, 255, 255, 0.9);
      --auth-text: #102033;
      --auth-muted: #617080;
      --auth-dark: #0b2233;
      --auth-accent: #0f766e;
      --auth-accent-strong: #0d5f59;
      --auth-accent-border: rgba(15, 118, 110, 0.55);
      --auth-accent-ring: rgba(15, 118, 110, 0.16);
    }

    body.theme-guardian {
      --auth-accent: #1d4ed8;
      --auth-accent-strong: #1e40af;
      --auth-accent-border: rgba(29, 78, 216, 0.55);
      --auth-accent-ring: rgba(29, 78, 216, 0.16);
    }

    body.theme-staff {
      --auth-accent: #0b2233;
      --auth-accent-strong: #081a28;
      --auth-accent-border: rgba(11, 34, 51, 0.55);
      --auth-accent-ring: rgba(11, 34, 51, 0.16);
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: var(--font-body);
      color: var(--auth-text);
      background:
        radial-gradient(circle at 18% 18%, rgba(15, 118, 110, 0.14), transparent 22%),
        radial-gradient(circle at 82% 14%, rgba(245, 158, 11, 0.12), transparent 18%),
        linear-gradient(135deg, #f8fafc 0%, var(--auth-bg) 100%);
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      pointer-events: none;
      background-image:
        linear-gradient(rgba(255, 255, 255, 0.4) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.4) 1px, transparent 1px);
      background-size: 34px 34px;
      mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.36), rgba(0, 0, 0, 0));
    }

    .auth-shell {
      min-height: 100vh;
      display: grid;
      grid-template-columns: minmax(380px, 1.1fr) minmax(320px, 0.9fr);
    }

    .auth-showcase {
      position: relative;
      isolation: isolate;
      overflow: hidden;
      padding: 2.25rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background:
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.1), transparent 22%),
        linear-gradient(160deg, var(--auth-dark) 0%, var(--auth-accent) 100%);
      color: #f8fafc;
    }

    .auth-showcase::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        linear-gradient(180deg, rgba(8, 26, 40, 0.08), rgba(8, 26, 40, 0.42)),
        url("assets/img/escola.jpg") center/cover no-repeat;
      opacity: 0.48;
      transform: scale(1.04);
      filter: saturate(0.88) contrast(1.05);
      -webkit-mask-image: linear-gradient(112deg, rgba(0, 0, 0, 0.98) 0%, rgba(0, 0, 0, 0.92) 34%, rgba(0, 0, 0, 0.58) 66%, transparent 94%);
      mask-image: linear-gradient(112deg, rgba(0, 0, 0, 0.98) 0%, rgba(0, 0, 0, 0.92) 34%, rgba(0, 0, 0, 0.58) 66%, transparent 94%);
    }

    .auth-showcase::after {
      content: "";
      position: absolute;
      inset: auto -90px -100px auto;
      width: 320px;
      height: 320px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(245, 158, 11, 0.26), transparent 68%);
      filter: blur(6px);
    }

    .showcase-header,
    .showcase-highlights,
    .showcase-footer {
      position: relative;
      z-index: 1;
    }

    .showcase-kicker {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.45rem 0.8rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.12);
      color: rgba(255, 255, 255, 0.86);
      font-size: 0.77rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.12em;
    }

    .showcase-header h1 {
      margin: 1.2rem 0 0.75rem;
      font-family: var(--font-heading);
      font-size: clamp(2.3rem, 4vw, 4rem);
      letter-spacing: -0.05em;
      line-height: 0.98;
    }

    .showcase-header p {
      max-width: 560px;
      color: rgba(241, 245, 249, 0.82);
      font-size: 1.02rem;
      line-height: 1.7;
      margin: 0;
    }

    .showcase-logo {
      width: 100%;
      max-width: 520px;
      height: 76px;
      object-fit: contain;
      padding: 0.7rem 1rem;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.96);
      box-shadow: 0 22px 40px rgba(0, 0, 0, 0.16);
      margin-top: 2rem;
    }

    .showcase-highlights {
      display: grid;
      gap: 0.9rem;
    }

    .highlight-card {
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      padding: 1rem 1.05rem;
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(12px);
    }

    .highlight-icon {
      width: 42px;
      height: 42px;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: rgba(255, 255, 255, 0.14);
      font-size: 1rem;
    }

    .highlight-card span {
      color: rgba(241, 245, 249, 0.82);
      line-height: 1.55;
    }

    .showcase-footer img {
      width: 100%;
      max-width: 500px;
      height: 54px;
      object-fit: contain;
      display: block;
      opacity: 0.92;
    }

    .auth-panel {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1.5rem;
    }

    .auth-panel-inner {
      width: 100%;
      max-width: 540px;
    }

    .auth-card {
      padding: 2rem;
      border-radius: 30px;
      background: var(--auth-surface);
      border: 1px solid rgba(255, 255, 255, 0.7);
      box-shadow: 0 36px 70px rgba(15, 23, 42, 0.12);
      backdrop-filter: blur(18px);
    }

    .auth-top-logo {
      width: 100%;
      height: 62px;
      object-fit: contain;
      display: block;
      margin-bottom: 1.35rem;
    }

    .auth-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      margin-bottom: 1rem;
      padding: 0.45rem 0.8rem;
      border-radius: 999px;
      background: rgba(15, 118, 110, 0.1);
      color: var(--auth-accent);
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.12em;
    }

    .auth-card h2 {
      margin: 0;
      font-family: var(--font-heading);
      font-size: clamp(1.9rem, 3vw, 2.5rem);
      letter-spacing: -0.05em;
    }

    .auth-card p {
      margin: 0.75rem 0 1.4rem;
      color: var(--auth-muted);
      line-height: 1.65;
    }

    .form-label {
      font-weight: 700;
      color: var(--auth-text);
    }

    .form-control {
      border-radius: 16px;
      border: 1px solid rgba(16, 32, 51, 0.12);
      background: rgba(248, 250, 252, 0.96);
      padding: 0.82rem 0.95rem;
    }

    .form-control:focus {
      border-color: var(--auth-accent-border);
      box-shadow: 0 0 0 0.2rem var(--auth-accent-ring);
      background: #fff;
    }

    .btn {
      border-radius: 999px;
      padding: 0.84rem 1rem;
      font-weight: 700;
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 18px 30px rgba(15, 23, 42, 0.08);
    }

    .btn-primary {
      --bs-btn-bg: var(--auth-accent);
      --bs-btn-border-color: var(--auth-accent);
      --bs-btn-hover-bg: var(--auth-accent-strong);
      --bs-btn-hover-border-color: var(--auth-accent-strong);
      --bs-btn-active-bg: var(--auth-accent-strong);
      --bs-btn-active-border-color: var(--auth-accent-strong);
    }

    .auth-links {
      display: flex;
      flex-wrap: wrap;
      gap: 0.85rem 1.1rem;
      justify-content: center;
      margin-top: 1rem;
    }

    .auth-links a {
      color: var(--auth-muted);
      font-size: 0.95rem;
      font-weight: 600;
      text-decoration: none;
    }

    .auth-links a:hover {
      color: var(--auth-accent);
    }

    .alert {
      border: none;
      border-radius: 18px;
      box-shadow: 0 18px 30px rgba(15, 23, 42, 0.06);
    }

    .auth-mobile-footer {
      margin-top: 1rem;
      text-align: center;
    }

    .auth-mobile-footer img {
      width: 100%;
      height: 46px;
      object-fit: contain;
      opacity: 0.9;
    }

    @media (max-width: 991.98px) {
      body {
        background:
          linear-gradient(180deg, rgba(248, 250, 252, 0.88), rgba(237, 242, 247, 0.96)),
          radial-gradient(circle at 18% 18%, rgba(15, 118, 110, 0.12), transparent 22%),
          url("assets/img/escola.jpg") center/cover no-repeat;
      }

      .auth-shell {
        grid-template-columns: 1fr;
      }

      .auth-panel {
        padding: 1.4rem 1rem;
      }

      .auth-card {
        border-radius: 24px;
        padding: 1.5rem;
      }
    }

    @media (max-width: 575.98px) {
      .auth-card {
        padding: 1.25rem;
      }

      .auth-top-logo {
        height: 54px;
      }

      .auth-links {
        flex-direction: column;
        align-items: center;
      }
    }
  </style>
</head>
<body class="theme-<?= htmlspecialchars($theme) ?>">
  <div class="auth-shell">
    <section class="auth-showcase d-none d-lg-flex">
      <div class="showcase-header">
        <span class="showcase-kicker"><i class="bi bi-grid-1x2-fill"></i>Dashboard escolar</span>
        <h1>Cartao Digital com acessos claros e navegacao consistente.</h1>
        <p><?= htmlspecialchars($description) ?></p>
        <img class="showcase-logo" src="assets/img/topo_escola.jpg" alt="Logotipos da escola" loading="lazy">
      </div>

      <div class="showcase-highlights">
        <?php foreach ($highlights as $highlight): ?>
          <div class="highlight-card">
            <span class="highlight-icon"><i class="bi <?= htmlspecialchars($highlight["icon"]) ?>"></i></span>
            <span><?= htmlspecialchars($highlight["text"]) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="showcase-footer">
        <img src="assets/img/rodape_pessoas2030.jpg" alt="Logotipos Pessoas 2030 e parceiros" loading="lazy">
      </div>
    </section>

    <main class="auth-panel">
      <div class="auth-panel-inner">
        <div class="auth-card">
          <img class="auth-top-logo" src="assets/img/topo_escola.jpg" alt="Logotipos da escola" loading="lazy">
          <span class="auth-chip"><i class="bi bi-person-badge"></i><?= htmlspecialchars($eyebrow) ?></span>
          <h2><?= htmlspecialchars($heading) ?></h2>
          <p><?= htmlspecialchars($description) ?></p>
<?php }

function auth_page_footer() { ?>
        </div>

        <div class="auth-mobile-footer d-lg-none">
          <img src="assets/img/rodape_pessoas2030.jpg" alt="Logotipos Pessoas 2030 e parceiros" loading="lazy">
        </div>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php }