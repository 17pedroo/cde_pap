<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";

require_staff();

function admin_metric(PDO $pdo, string $sql): int {
  $stmt = $pdo->query($sql);
  return (int)($stmt->fetchColumn() ?? 0);
}

function admin_money_metric(PDO $pdo, string $sql): string {
  $stmt = $pdo->query($sql);
  $value = (int)($stmt->fetchColumn() ?? 0);
  return number_format($value / 100, 2, ',', '.') . ' €';
}

$student_count = admin_metric($pdo, "SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1");
$guardian_count = admin_metric($pdo, "SELECT COUNT(*) FROM users WHERE role='guardian' AND is_active=1");
$access_count_today = admin_metric($pdo, "SELECT COUNT(*) FROM access_logs WHERE DATE(scanned_at) = CURDATE()");
$active_product_count = admin_metric($pdo, "SELECT COUNT(*) FROM products WHERE is_active=1");
$canteen_count_today = admin_metric($pdo, "SELECT COUNT(*) FROM canteen_tickets WHERE DATE(reserved_at) = CURDATE()");
$bar_total_today = admin_money_metric($pdo, "SELECT COALESCE(-SUM(amount_cents), 0) FROM wallet_transactions WHERE type='purchase' AND description LIKE 'Compra bar:%' AND DATE(created_at) = CURDATE()");

$quick_links = [
  [
    'title' => 'Acessos QR',
    'text' => 'Abrir o leitor de QR para registar entradas e saídas dos alunos.',
    'href' => 'scanner.php',
    'icon' => 'bi-qr-code-scan',
    'button' => 'Abrir leitor',
  ],
  [
    'title' => 'Registos de acessos',
    'text' => 'Consultar leituras recentes, filtrar por aluno e exportar resultados.',
    'href' => 'portaria_logs.php',
    'icon' => 'bi-journal-check',
    'button' => 'Ver registos',
  ],
  [
    'title' => 'Cantina',
    'text' => 'Gerir senhas e acompanhar o fluxo diário da cantina.',
    'href' => 'canteen.php',
    'icon' => 'bi-cup-hot-fill',
    'button' => 'Abrir cantina',
  ],
  [
    'title' => 'Bar e buffet',
    'text' => 'Cobrar produtos por QR e acompanhar compras em tempo real.',
    'href' => 'bar.php',
    'icon' => 'bi-cup-straw',
    'button' => 'Abrir bar',
  ],
  [
    'title' => 'Produtos',
    'text' => 'Adicionar, editar e ativar produtos disponíveis para venda.',
    'href' => 'manage_products.php',
    'icon' => 'bi-box-seam',
    'button' => 'Gerir produtos',
  ],
  [
    'title' => 'Relatórios',
    'text' => 'Ver vendas, tickets e atividade com filtros e exportação.',
    'href' => 'reports.php',
    'icon' => 'bi-bar-chart-line-fill',
    'button' => 'Ver relatórios',
  ],
  [
    'title' => 'Alunos',
    'text' => 'Registar novos alunos e consultar os existentes.',
    'href' => 'register_student.php',
    'icon' => 'bi-mortarboard-fill',
    'button' => 'Gerir alunos',
  ],
  [
    'title' => 'Encarregados',
    'text' => 'Associar encarregados aos alunos e atualizar ligações existentes.',
    'href' => 'register_guardian.php',
    'icon' => 'bi-person-vcard-fill',
    'button' => 'Gerir encarregados',
  ],
];

page_header('Painel Admin');
?>

<div class="hero-banner">
  <div>
    <span class="hero-label"><i class="bi bi-grid-1x2-fill"></i>Painel central</span>
    <h2>Administração escolar</h2>
    <p>Este é o ponto de entrada do admin. A partir daqui pode abrir acessos, cantina, bar, gestão e relatórios sem entrar diretamente num leitor QR.</p>
  </div>
  <div class="hero-actions">
    <a class="btn btn-primary" href="reports.php">Ver relatórios</a>
    <a class="btn btn-outline-light" href="scanner.php">Abrir acessos QR</a>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Alunos ativos</div>
        <div class="display-6 mb-1"><?= $student_count ?></div>
        <div class="text-muted small">Contas de aluno disponíveis no sistema.</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Encarregados ativos</div>
        <div class="display-6 mb-1"><?= $guardian_count ?></div>
        <div class="text-muted small">Contas ligadas aos alunos registados.</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Produtos ativos</div>
        <div class="display-6 mb-1"><?= $active_product_count ?></div>
        <div class="text-muted small">Itens disponíveis no bar e buffet.</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Leituras de hoje</div>
        <div class="display-6 mb-1"><?= $access_count_today ?></div>
        <div class="text-muted small">Entradas e saídas registadas hoje.</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Senhas da cantina hoje</div>
        <div class="display-6 mb-1"><?= $canteen_count_today ?></div>
        <div class="text-muted small">Pedidos e leituras de cantina do dia.</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card shadow-sm metric-card h-100">
      <div class="card-body">
        <div class="text-muted">Vendas do bar hoje</div>
        <div class="display-6 mb-1"><?= htmlspecialchars($bar_total_today) ?></div>
        <div class="text-muted small">Valor total vendido no dia.</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <?php foreach ($quick_links as $link): ?>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="hero-label"><i class="bi <?= htmlspecialchars($link['icon']) ?>"></i><?= htmlspecialchars($link['title']) ?></span>
          </div>
          <h5 class="card-title mb-2"><?= htmlspecialchars($link['title']) ?></h5>
          <p class="text-muted flex-grow-1 mb-3"><?= htmlspecialchars($link['text']) ?></p>
          <a class="btn btn-outline-primary" href="<?= htmlspecialchars($link['href']) ?>"><?= htmlspecialchars($link['button']) ?></a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php page_footer(); ?>