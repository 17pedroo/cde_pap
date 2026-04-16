<?php
function app_current_script(): string {
  $scriptName = $_SERVER["SCRIPT_NAME"] ?? ($_SERVER["PHP_SELF"] ?? "");
  return basename((string)$scriptName);
}

function app_role_label(string $role): string {
  return match ($role) {
    "student" => "Aluno",
    "guardian" => "Encarregado",
    "staff", "admin" => "Admin",
    default => "Acesso",
  };
}

function app_user_initials(string $name): string {
  $parts = preg_split('/\s+/', trim($name)) ?: [];
  $initials = "";

  foreach ($parts as $part) {
    if ($part === "") {
      continue;
    }

    $initials .= strtoupper(substr($part, 0, 1));
    if (strlen($initials) >= 2) {
      break;
    }
  }

  return $initials !== "" ? $initials : "CD";
}

function app_display_name(string $name, string $role): string {
  $displayName = trim($name);

  if ($displayName === '') {
    return app_role_label($role);
  }

  if ($role === 'staff' || $role === 'admin') {
    $displayName = preg_replace('/portaria/i', 'Admin', $displayName) ?? $displayName;
    $displayName = preg_replace('/staff/i', 'Admin', $displayName) ?? $displayName;
  }

  return $displayName;
}

function app_navigation_sections(string $role): array {
  $commonSection = [
    [
      "href" => "sobre.php",
      "label" => "Sobre o projeto",
      "icon" => "bi-info-circle",
      "match" => ["sobre.php"],
    ],
  ];

  return match ($role) {
    "student" => [
      [
        "label" => "Aluno",
        "items" => [
          [
            "href" => "dashboard.php",
            "label" => "Visao geral",
            "icon" => "bi-grid-1x2-fill",
            "match" => ["dashboard.php", "index.php"],
          ],
          [
            "href" => "movimentos.php",
            "label" => "Movimentos",
            "icon" => "bi-wallet2",
            "match" => ["movimentos.php"],
          ],
          [
            "href" => "acessos.php",
            "label" => "Acessos",
            "icon" => "bi-door-open-fill",
            "match" => ["acessos.php"],
          ],
        ],
      ],
      [
        "label" => "Sistema",
        "items" => $commonSection,
      ],
    ],
    "guardian" => [
      [
        "label" => "Encarregado",
        "items" => [
          [
            "href" => "guardian_dashboard.php",
            "label" => "Aluno associado",
            "icon" => "bi-people-fill",
            "match" => ["guardian_dashboard.php"],
          ],
        ],
      ],
      [
        "label" => "Sistema",
        "items" => $commonSection,
      ],
    ],
    "staff", "admin" => [
      [
        "label" => "Admin",
        "items" => [
          [
            "href" => "admin_dashboard.php",
            "label" => "Painel admin",
            "icon" => "bi-grid-1x2-fill",
            "match" => ["admin_dashboard.php"],
          ],
          [
            "href" => "scanner.php",
            "label" => "Acessos QR",
            "icon" => "bi-qr-code-scan",
            "match" => ["scanner.php"],
          ],
          [
            "href" => "portaria_logs.php",
            "label" => "Registos de acessos",
            "icon" => "bi-journal-check",
            "match" => ["portaria_logs.php"],
          ],
          [
            "href" => "canteen.php",
            "label" => "Cantina",
            "icon" => "bi-cup-hot-fill",
            "match" => ["canteen.php"],
          ],
          [
            "href" => "bar.php",
            "label" => "Bar e buffet",
            "icon" => "bi-cup-straw",
            "match" => ["bar.php"],
          ],
        ],
      ],
      [
        "label" => "Gestão",
        "items" => [
          [
            "href" => "manage_products.php",
            "label" => "Produtos",
            "icon" => "bi-box-seam",
            "match" => ["manage_products.php"],
          ],
          [
            "href" => "reports.php",
            "label" => "Relatorios",
            "icon" => "bi-bar-chart-line-fill",
            "match" => ["reports.php"],
          ],
          [
            "href" => "register_student.php",
            "label" => "Alunos",
            "icon" => "bi-mortarboard-fill",
            "match" => ["register_student.php"],
          ],
          [
            "href" => "register_guardian.php",
            "label" => "Encarregados",
            "icon" => "bi-person-vcard-fill",
            "match" => ["register_guardian.php"],
          ],
        ],
      ],
      [
        "label" => "Sistema",
        "items" => $commonSection,
      ],
    ],
    default => [
      [
        "label" => "Sistema",
        "items" => $commonSection,
      ],
    ],
  };
}

function app_nav_is_active(array $item, string $currentScript): bool {
  $matches = $item["match"] ?? [$item["href"]];
  return in_array($currentScript, $matches, true);
}

function render_app_sidebar(string $currentScript, string $role): void {
  $name = app_display_name((string)($_SESSION["name"] ?? "Utilizador"), $role);
  $roleLabel = app_role_label($role);
  $sections = app_navigation_sections($role);
  $initials = app_user_initials($name);
  ?>
  <div class="app-sidebar-body">
    <div class="sidebar-brand">
      <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
          <div class="sidebar-kicker">Cartao Digital</div>
          <h2 class="mb-2">Painel escolar com navegacao central.</h2>
          <p class="mb-0">Acesso rapido aos modulos principais, com leitura clara em escritorio e telemovel.</p>
        </div>
        <span class="brand-pill">2026</span>
      </div>
      <img src="assets/img/topo_escola.jpg" alt="Logotipos da escola" loading="lazy">
    </div>

    <div class="sidebar-user-panel">
      <div class="sidebar-user-avatar"><?= htmlspecialchars($initials) ?></div>
      <div>
        <div class="sidebar-user-name"><?= htmlspecialchars($name) ?></div>
        <div class="sidebar-user-role"><?= htmlspecialchars($roleLabel) ?></div>
      </div>
    </div>

    <nav class="sidebar-menu" aria-label="Navegacao principal">
      <?php foreach ($sections as $section): ?>
        <div class="sidebar-section">
          <div class="nav-section-label"><?= htmlspecialchars($section["label"]) ?></div>
          <div class="app-nav">
            <?php foreach ($section["items"] as $item): ?>
              <?php $isActive = app_nav_is_active($item, $currentScript); ?>
              <a class="app-nav-link<?= $isActive ? ' active' : '' ?>" href="<?= htmlspecialchars($item["href"]) ?>">
                <span class="nav-link-icon"><i class="bi <?= htmlspecialchars($item["icon"]) ?>"></i></span>
                <span><?= htmlspecialchars($item["label"]) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-note">
        <strong>Fluxo simplificado</strong>
        <span>As tarefas frequentes ficam agrupadas por perfil para reduzir cliques e mudancas de contexto.</span>
      </div>
      <a class="btn btn-light w-100" href="logout.php">
        <i class="bi bi-box-arrow-right me-2"></i>Terminar sessao
      </a>
    </div>
  </div>
<?php }

function page_header(string $title) {
  $currentScript = app_current_script();
  $role = (string)($_SESSION["role"] ?? "guest");
  $roleLabel = app_role_label($role);
  $name = app_display_name((string)($_SESSION["name"] ?? "Utilizador"), $role);
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
  <link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%20viewBox%3D%270%200%20100%20100%27%3E%3Crect%20width%3D%27100%27%20height%3D%27100%27%20rx%3D%2722%27%20fill%3D%27%230b2233%27/%3E%3Ctext%20x%3D%2750%27%20y%3D%2762%27%20font-size%3D%2746%27%20text-anchor%3D%27middle%27%20fill%3D%27white%27%20font-family%3D%27Arial%27%3ECD%3C/text%3E%3C/svg%3E">
  <title><?= htmlspecialchars($title) ?></title>

  <style>
    :root {
      color-scheme: light;
      --font-heading: "Manrope", "Segoe UI", sans-serif;
      --font-body: "IBM Plex Sans", "Segoe UI", sans-serif;
      --bg: #edf2f7;
      --surface: #ffffff;
      --surface-soft: #f5f8fb;
      --sidebar: #0b2233;
      --sidebar-strong: #081a28;
      --sidebar-text: #dbe8f2;
      --sidebar-muted: #94a9b8;
      --text: #102033;
      --muted: #617080;
      --border: rgba(16, 32, 51, 0.10);
      --primary: #0f766e;
      --primary-strong: #0d5f59;
      --accent: #f59e0b;
    }

    * {
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: var(--font-body);
      color: var(--text);
      background:
        radial-gradient(circle at top left, rgba(15, 118, 110, 0.15), transparent 22%),
        radial-gradient(circle at 88% 14%, rgba(245, 158, 11, 0.12), transparent 18%),
        linear-gradient(180deg, #f8fafc 0%, var(--bg) 100%);
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      pointer-events: none;
      background-image:
        linear-gradient(rgba(255, 255, 255, 0.42) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.42) 1px, transparent 1px);
      background-size: 36px 36px;
      mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0));
      z-index: -1;
    }

    a {
      text-decoration: none;
    }

    .app-shell {
      display: flex;
      min-height: 100vh;
    }

    .app-sidebar {
      width: 320px;
      flex-shrink: 0;
      background: linear-gradient(180deg, var(--sidebar) 0%, var(--sidebar-strong) 100%);
      color: var(--sidebar-text);
      border-right: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 22px 0 48px rgba(8, 26, 40, 0.12);
    }

    .offcanvas.app-sidebar {
      --bs-offcanvas-width: 320px;
      border-right: 0;
    }

    .offcanvas.app-sidebar .offcanvas-body {
      padding: 0;
      overflow-y: auto;
      overscroll-behavior: contain;
      -webkit-overflow-scrolling: touch;
    }

    .offcanvas.app-sidebar .offcanvas-header {
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      color: #fff;
      padding: 1rem 1.25rem;
    }

    .offcanvas.app-sidebar .btn-close {
      filter: invert(1) grayscale(1);
    }

    .app-sidebar-body {
      min-height: 100%;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      padding: 1.5rem;
    }

    .offcanvas.app-sidebar .app-sidebar-body {
      min-height: auto;
      padding-bottom: calc(1.5rem + env(safe-area-inset-bottom, 0px));
    }

    .sidebar-brand {
      padding: 1.3rem;
      border-radius: 28px;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.11), rgba(255, 255, 255, 0.04));
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    .sidebar-brand h2 {
      margin: 0;
      font-family: var(--font-heading);
      font-size: 1.35rem;
      letter-spacing: -0.03em;
      color: #fff;
    }

    .sidebar-brand p {
      color: var(--sidebar-muted);
      margin: 0;
      line-height: 1.55;
    }

    .sidebar-kicker {
      margin-bottom: 0.55rem;
      font-size: 0.74rem;
      text-transform: uppercase;
      letter-spacing: 0.16em;
      font-weight: 700;
      color: rgba(255, 255, 255, 0.62);
    }

    .brand-pill {
      padding: 0.5rem 0.7rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.08em;
    }

    .sidebar-brand img {
      width: 100%;
      height: 54px;
      object-fit: contain;
      display: block;
      margin-top: 1rem;
      padding: 0.5rem;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.98);
    }

    .sidebar-user-panel {
      display: flex;
      align-items: center;
      gap: 0.9rem;
      padding: 1rem 1.05rem;
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sidebar-user-avatar {
      width: 52px;
      height: 52px;
      border-radius: 18px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #fcd34d, #f59e0b);
      color: #4a2600;
      font-family: var(--font-heading);
      font-weight: 800;
      letter-spacing: 0.06em;
    }

    .sidebar-user-name {
      font-weight: 700;
      color: #fff;
    }

    .sidebar-user-role {
      color: var(--sidebar-muted);
      font-size: 0.92rem;
    }

    .sidebar-menu {
      display: grid;
      gap: 1.15rem;
    }

    .nav-section-label {
      margin-bottom: 0.55rem;
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.15em;
      font-weight: 700;
      color: var(--sidebar-muted);
    }

    .app-nav {
      display: flex;
      flex-direction: column;
      gap: 0.42rem;
    }

    .app-nav-link {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      padding: 0.82rem 0.95rem;
      border-radius: 18px;
      color: var(--sidebar-text);
      transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
    }

    .app-nav-link:hover {
      transform: translateX(4px);
      color: #fff;
      background: rgba(255, 255, 255, 0.08);
    }

    .app-nav-link.active {
      color: #fff;
      background: linear-gradient(135deg, rgba(15, 118, 110, 0.96), rgba(13, 95, 89, 0.92));
      box-shadow: 0 18px 34px rgba(0, 0, 0, 0.24);
    }

    .nav-link-icon {
      width: 40px;
      height: 40px;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: rgba(255, 255, 255, 0.08);
      font-size: 1rem;
    }

    .app-nav-link.active .nav-link-icon {
      background: rgba(255, 255, 255, 0.16);
    }

    .sidebar-footer {
      margin-top: auto;
      display: grid;
      gap: 0.8rem;
    }

    .sidebar-note {
      padding: 1rem 1.1rem;
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.08);
      color: var(--sidebar-muted);
      line-height: 1.55;
    }

    .sidebar-note strong {
      display: block;
      margin-bottom: 0.25rem;
      color: #fff;
      font-family: var(--font-heading);
    }

    .sidebar-footer .btn-light {
      border: 0;
      color: var(--sidebar);
      box-shadow: 0 14px 30px rgba(0, 0, 0, 0.16);
    }

    .app-main {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      padding: 1rem;
    }

    .app-topbar {
      position: sticky;
      top: 1rem;
      z-index: 1010;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      padding: 1.1rem 1.25rem;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.78);
      border: 1px solid rgba(255, 255, 255, 0.72);
      box-shadow: 0 22px 44px rgba(15, 23, 42, 0.08);
      backdrop-filter: blur(20px);
    }

    .page-kicker {
      margin-bottom: 0.2rem;
      font-size: 0.76rem;
      text-transform: uppercase;
      letter-spacing: 0.16em;
      font-weight: 800;
      color: var(--primary);
    }

    .app-page-title {
      margin: 0;
      font-family: var(--font-heading);
      font-size: clamp(1.4rem, 2vw, 2rem);
      letter-spacing: -0.04em;
    }

    .topbar-meta {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .topbar-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      padding: 0.66rem 0.95rem;
      border-radius: 999px;
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--muted);
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
    }

    .app-content {
      width: 100%;
      max-width: 1480px;
      margin: 1.25rem auto 0;
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .app-footer {
      margin-top: auto;
      padding-top: 1.25rem;
      padding-bottom: 0.5rem;
    }

    .footer-band {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.2rem;
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.82);
      border: 1px solid var(--border);
      box-shadow: 0 18px 38px rgba(15, 23, 42, 0.05);
    }

    .footer-copy strong {
      display: block;
      font-family: var(--font-heading);
      letter-spacing: -0.03em;
    }

    .footer-copy span {
      color: var(--muted);
      font-size: 0.94rem;
    }

    .footer-band img {
      width: min(100%, 520px);
      height: 54px;
      object-fit: contain;
      display: block;
    }

    .hero-banner {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 1rem;
      padding: 1.55rem 1.65rem;
      border-radius: 30px;
      background: linear-gradient(135deg, rgba(11, 34, 51, 0.97), rgba(15, 118, 110, 0.88));
      color: #f8fafc;
      box-shadow: 0 26px 50px rgba(11, 34, 51, 0.2);
    }

    .hero-banner h2 {
      margin: 0;
      color: #fff;
      font-family: var(--font-heading);
      font-size: clamp(1.7rem, 3vw, 2.5rem);
      letter-spacing: -0.04em;
    }

    .hero-banner p {
      margin: 0.6rem 0 0;
      max-width: 680px;
      color: rgba(241, 245, 249, 0.84);
      line-height: 1.6;
    }

    .hero-label {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      margin-bottom: 0.65rem;
      padding: 0.45rem 0.75rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.1);
      color: rgba(255, 255, 255, 0.84);
      font-size: 0.76rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.1em;
    }

    .hero-actions {
      display: flex;
      gap: 0.7rem;
      flex-wrap: wrap;
    }

    .hero-banner .btn-primary {
      --bs-btn-bg: #ffffff;
      --bs-btn-border-color: #ffffff;
      --bs-btn-color: var(--sidebar);
      --bs-btn-hover-bg: #ecf4f4;
      --bs-btn-hover-border-color: #ecf4f4;
      --bs-btn-hover-color: var(--sidebar);
      --bs-btn-active-bg: #ffffff;
      --bs-btn-active-border-color: #ffffff;
      --bs-btn-active-color: var(--sidebar);
    }

    .metric-card {
      position: relative;
      overflow: hidden;
    }

    .metric-card::after {
      content: "";
      position: absolute;
      right: -48px;
      bottom: -58px;
      width: 160px;
      height: 160px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(15, 118, 110, 0.14), transparent 68%);
      pointer-events: none;
    }

    .scan-reader {
      width: 100%;
      max-width: 560px;
      margin: 0 auto;
      padding: 1rem;
      border-radius: 26px;
      border: 1px dashed rgba(15, 118, 110, 0.24);
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(241, 245, 249, 0.92));
    }

    .scan-status {
      padding: 1rem 1.15rem;
      border-radius: 22px;
      background: var(--surface-soft);
      border: 1px solid var(--border);
    }

    .card {
      border: 1px solid rgba(255, 255, 255, 0.7);
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.88);
      box-shadow: 0 24px 48px rgba(15, 23, 42, 0.07);
      backdrop-filter: blur(14px);
    }

    .card.shadow-sm {
      box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
    }

    .card-body {
      padding: 1.35rem;
    }

    h4,
    h5,
    .card-title,
    .display-6 {
      font-family: var(--font-heading);
      letter-spacing: -0.035em;
    }

    .text-muted {
      color: var(--muted) !important;
    }

    .table {
      margin-bottom: 0;
      --bs-table-bg: transparent;
      --bs-table-striped-bg: rgba(15, 118, 110, 0.04);
      --bs-table-border-color: rgba(15, 23, 42, 0.07);
    }

    .table th,
    .table td {
      vertical-align: middle;
    }

    .table thead th {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      font-weight: 700;
      color: var(--muted);
      border-bottom-width: 1px;
    }

    .badge {
      padding: 0.48rem 0.72rem;
      border-radius: 999px;
      font-weight: 700;
    }

    .alert {
      border: none;
      border-radius: 20px;
      box-shadow: 0 18px 30px rgba(15, 23, 42, 0.06);
    }

    .form-label {
      font-weight: 700;
      color: var(--text);
    }

    .form-control,
    .form-select {
      border-radius: 16px;
      border: 1px solid rgba(15, 23, 42, 0.12);
      background: rgba(248, 250, 252, 0.94);
      padding: 0.78rem 0.95rem;
      transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: rgba(15, 118, 110, 0.45);
      box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.12);
      background: #fff;
    }

    .btn {
      border-radius: 999px;
      padding: 0.72rem 1rem;
      font-weight: 700;
      transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
    }

    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 26px rgba(15, 23, 42, 0.08);
    }

    .btn-primary {
      --bs-btn-bg: var(--primary);
      --bs-btn-border-color: var(--primary);
      --bs-btn-hover-bg: var(--primary-strong);
      --bs-btn-hover-border-color: var(--primary-strong);
      --bs-btn-active-bg: var(--primary-strong);
      --bs-btn-active-border-color: var(--primary-strong);
    }

    .btn-outline-primary {
      --bs-btn-color: var(--primary);
      --bs-btn-border-color: rgba(15, 118, 110, 0.28);
      --bs-btn-hover-bg: rgba(15, 118, 110, 0.08);
      --bs-btn-hover-border-color: rgba(15, 118, 110, 0.3);
      --bs-btn-hover-color: var(--primary);
      --bs-btn-active-bg: rgba(15, 118, 110, 0.14);
      --bs-btn-active-border-color: rgba(15, 118, 110, 0.32);
      --bs-btn-active-color: var(--primary);
    }

    .btn-dark {
      --bs-btn-bg: var(--sidebar);
      --bs-btn-border-color: var(--sidebar);
      --bs-btn-hover-bg: #143046;
      --bs-btn-hover-border-color: #143046;
      --bs-btn-active-bg: #143046;
      --bs-btn-active-border-color: #143046;
    }

    .list-group {
      gap: 0.65rem;
      --bs-list-group-border-color: transparent;
    }

    .list-group-item {
      border-radius: 18px;
      border: 1px solid var(--border);
      background: var(--surface-soft);
    }

    code {
      color: #9a3412;
      background: rgba(245, 158, 11, 0.12);
      padding: 0.12rem 0.38rem;
      border-radius: 0.5rem;
    }

    @media (max-width: 991.98px) {
      .app-main {
        padding: 0.75rem;
      }

      .app-topbar {
        top: 0.75rem;
        padding: 1rem;
        border-radius: 22px;
      }

      .hero-banner,
      .footer-band {
        flex-direction: column;
        align-items: flex-start;
      }
    }

    @media (max-width: 767.98px) {
      .topbar-meta {
        width: 100%;
        justify-content: flex-start;
      }

      .app-topbar {
        align-items: flex-start;
        flex-direction: column;
      }
    }

    @media (max-width: 575.98px) {
      .card,
      .hero-banner,
      .footer-band,
      .app-topbar {
        border-radius: 20px;
      }

      .card-body {
        padding: 1.1rem;
      }

      .hero-banner {
        padding: 1.25rem;
      }

      .sidebar-brand img,
      .footer-band img {
        height: 46px;
      }
    }
  </style>
</head>
<body>
<div class="app-shell">
  <aside class="app-sidebar d-none d-lg-flex">
    <?php render_app_sidebar($currentScript, $role); ?>
  </aside>

  <div class="offcanvas offcanvas-start app-sidebar d-lg-none" tabindex="-1" id="appNav" aria-labelledby="appNavLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="appNavLabel">Navegacao</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body">
      <?php render_app_sidebar($currentScript, $role); ?>
    </div>
  </div>

  <main class="app-main">
    <header class="app-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-primary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appNav" aria-controls="appNav">
          <i class="bi bi-list me-2"></i>Menu
        </button>
        <div>
          <div class="page-kicker">Painel <?= htmlspecialchars($roleLabel) ?></div>
          <h1 class="app-page-title"><?= htmlspecialchars($title) ?></h1>
        </div>
      </div>

      <div class="topbar-meta">
        <div class="topbar-chip">
          <i class="bi bi-person-circle"></i>
          <span><?= htmlspecialchars($name) ?></span>
        </div>
        <div class="topbar-chip">
          <i class="bi bi-layout-text-window-reverse"></i>
          <span><?= htmlspecialchars($roleLabel) ?></span>
        </div>
      </div>
    </header>

    <div class="app-content">
<?php }

function page_footer() { ?>
    </div>

    <footer class="app-footer">
      <div class="footer-band">
        <div class="footer-copy">
          <strong>Cartao Digital</strong>
          <span>Painel unificado para alunos, encarregados e operacao escolar.</span>
        </div>
        <img src="assets/img/rodape_pessoas2030.jpg" alt="Logotipos Pessoas 2030 e parceiros" loading="lazy">
      </div>
    </footer>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php }