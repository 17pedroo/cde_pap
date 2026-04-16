<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";
require_login();

$uid = (int)$_SESSION["user_id"];

// Saldo
$stmt = $pdo->prepare("SELECT balance_cents FROM wallets WHERE user_id=?");
$stmt->execute([$uid]);
$balance = (int)($stmt->fetchColumn() ?? 0);

// Últimos acessos (10)
$stmt = $pdo->prepare("
  SELECT action, scanned_at
  FROM access_logs
  WHERE user_id=?
  ORDER BY scanned_at DESC
  LIMIT 10
");
$stmt->execute([$uid]);
$accessRows = $stmt->fetchAll();

page_header("Início");

function eur($cents) {
  return number_format($cents/100, 2, ',', '.') . " €";
}

function translateTransactionType(string $type): string {
  return match ($type) {
    'topup' => 'Carregamento',
    'purchase' => 'Compra',
    'adjustment' => 'Ajuste',
    default => ucfirst($type),
  };
}

?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<div class="hero-banner">
  <div>
    <span class="hero-label"><i class="bi bi-person-badge"></i>Area do aluno</span>
    <h2>Ola, <?= htmlspecialchars($_SESSION["name"]) ?></h2>
    <p>Consulta saldo, apresenta o QR dinamico e acompanha os teus acessos num painel unico.</p>
  </div>
  <div class="hero-actions">
    <a class="btn btn-primary" href="movimentos.php">Ver movimentos</a>
    <a class="btn btn-outline-light" href="acessos.php">Ver acessos</a>
  </div>
</div>

<div class="row g-3">
  <!-- SALDO -->
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100 metric-card">
      <div class="card-body">
        <div class="text-muted">Saldo</div>
        <div class="display-6 mb-2"><?= eur($balance) ?></div>
        <div class="d-grid gap-2">
          <a class="btn btn-primary" href="movimentos.php">Ver movimentos</a>
        </div>
      </div>
    </div>
  </div>

  <!-- QR -->
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100 metric-card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h5 class="card-title mb-1">Cartão Digital</h5>
            <div class="text-muted small">QR de uso único com renovação automática</div>
          </div>
          <span class="badge text-bg-secondary">QR</span>
        </div>

        <div class="d-flex justify-content-center my-3">
          <div id="qrcode"></div>
        </div>

        <div class="text-muted small" id="qrstatus"></div>
      </div>
    </div>
  </div>

  <!-- ACESSOS -->
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100 metric-card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h5 class="card-title mb-1">Entradas/Saídas</h5>
            <div class="text-muted small">Últimos 10 registos</div>
          </div>
          <a class="btn btn-outline-primary btn-sm" href="acessos.php">Ver tudo</a>
        </div>

        <div class="table-responsive mt-3">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th class="text-end">Ação</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($accessRows as $r): ?>
              <tr>
                <td><?= htmlspecialchars(date("d/m H:i", strtotime($r["scanned_at"]))) ?></td>
                <td class="text-end">
                  <?php if ($r["action"] === "IN"): ?>
                    <span class="badge text-bg-success">Entrada</span>
                  <?php else: ?>
                    <span class="badge text-bg-danger">Saída</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($accessRows) === 0): ?>
              <tr><td colspan="2" class="text-muted">Sem registos ainda.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
const qrContainer = document.getElementById("qrcode");
const qrStatus = document.getElementById("qrstatus");
const QR_FALLBACK_REFRESH_MS = 15000;
let qrRefreshMs = QR_FALLBACK_REFRESH_MS;
let nextQrRefreshAt = 0;
let qrRefreshTimeout = null;

function renderQrStatus(message) {
  qrStatus.textContent = message;
}

function scheduleQrRefresh() {
  if (qrRefreshTimeout) {
    clearTimeout(qrRefreshTimeout);
  }

  qrRefreshTimeout = setTimeout(refreshQR, qrRefreshMs);
}

function updateQrCountdown() {
  if (!nextQrRefreshAt) {
    return;
  }

  const remainingSeconds = Math.max(0, Math.ceil((nextQrRefreshAt - Date.now()) / 1000));
  if (remainingSeconds === 0) {
    renderQrStatus("A atualizar QR...");
    return;
  }

  renderQrStatus(`QR pronto para leitura. Atualiza em ${remainingSeconds}s.`);
}

async function refreshQR() {
  renderQrStatus("A atualizar QR...");

  try {
    const response = await fetch("api/qr_token.php", { cache: "no-store" });
    if (!response.ok) {
      throw new Error("Falha ao carregar QR");
    }

    const data = await response.json();
    qrRefreshMs = Math.max(5000, ((parseInt(data.refresh_after_seconds, 10) || 15) * 1000));

    qrContainer.innerHTML = "";
    new QRCode(qrContainer, {
      text: data.token,
      width: 220,
      height: 220
    });

    nextQrRefreshAt = Date.now() + qrRefreshMs;
    updateQrCountdown();
    scheduleQrRefresh();
  } catch (error) {
    nextQrRefreshAt = Date.now() + QR_FALLBACK_REFRESH_MS;
    renderQrStatus("Não foi possível atualizar o QR. Recarregue a página se o problema continuar.");
    scheduleQrRefresh();
  }
}

refreshQR();
setInterval(updateQrCountdown, 1000);
</script>

<?php page_footer(); ?>
