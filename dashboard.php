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

page_header("Dashboard");

function eur($cents) {
  return number_format($cents/100, 2, ',', '.') . " €";
}
?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<div class="mb-3">
  <h4 class="mb-0">Olá, <?= htmlspecialchars($_SESSION["name"]) ?></h4>
  <div class="text-muted">Bem-vindo ao teu cartão digital.</div>
</div>

<div class="row g-3">
  <!-- SALDO -->
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
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
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h5 class="card-title mb-1">Cartão Digital</h5>
            <div class="text-muted small">QR renova automaticamente</div>
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
    <div class="card shadow-sm h-100">
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
                    <span class="badge text-bg-success">IN</span>
                  <?php else: ?>
                    <span class="badge text-bg-danger">OUT</span>
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
async function refreshQR(){
  const r = await fetch("api/qr_token.php");
  const data = await r.json();

  document.getElementById("qrcode").innerHTML = "";
  new QRCode(document.getElementById("qrcode"), {
    text: data.token,
    width: 220,
    height: 220
  });

  document.getElementById("qrstatus").textContent =
    "Atualizado: " + new Date().toLocaleTimeString();
}

refreshQR();
setInterval(refreshQR, 30000);
</script>

<?php page_footer(); ?>
