<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";

require_staff();

$products = [];
$dbError = null;
try {
  $stmt = $pdo->prepare("SELECT id, name, price_cents, category FROM products WHERE is_active=1 ORDER BY category, name");
  $stmt->execute();
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $dbError = $e->getMessage();
}

page_header("Bar / Buffet - QR");
?>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
  <div>
    <h4 class="mb-0">Bar / Buffet</h4>
    <div class="text-muted">Selecione um produto e leia o QR do aluno para cobrar o valor.</div>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="manage_products.php">Gerir produtos</a>
</div>

<div class="row g-3">
  <div class="col-12 col-xl-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Produtos</h5>
        <?php if ($dbError): ?>
          <div class="alert alert-danger">
            Erro de base de dados: <?= htmlspecialchars($dbError) ?><br>
            Execute <code>setup.php</code> para criar as tabelas necessárias.
          </div>
        <?php elseif (!$products): ?>
          <div class="alert alert-warning">Nenhum produto ativo.</div>
        <?php else: ?>
          <div class="list-group">
            <?php foreach ($products as $product): ?>
              <label class="list-group-item">
                <input type="radio" name="product_id" value="<?= $product['id'] ?>" class="form-check-input me-2">
                <strong><?= htmlspecialchars($product['name']) ?></strong>
                <span class="text-muted"> - <?= number_format($product['price_cents'] / 100, 2, ',', '.') ?> €</span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h5 class="card-title mb-1">Scanner de QR</h5>
            <div class="text-muted small">Aproxime o QR Code do aluno ao leitor.</div>
          </div>
          <span class="badge text-bg-secondary">Bar</span>
        </div>

        <div class="d-flex gap-2 flex-wrap mb-3">
          <button id="btnFS" class="btn btn-dark btn-sm">Ecrã inteiro</button>
          <button id="btnSwitchCam" class="btn btn-outline-dark btn-sm">Trocar câmara</button>
          <button id="btnReload" class="btn btn-outline-secondary btn-sm">Recarregar</button>
        </div>

        <div id="reader" style="width:100%; max-width:540px; margin:auto;"></div>
        <hr>
        <div id="result" class="fw-semibold">Aguardando leitura…</div>
        <div id="details" class="text-muted"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
const resultEl = document.getElementById("result");
const detailsEl = document.getElementById("details");
const btnFS = document.getElementById("btnFS");
const btnSwitchCam = document.getElementById("btnSwitchCam");
const btnReload = document.getElementById("btnReload");

let scanner = null;
let cameraIds = [];
let currentCameraId = null;
let cameraDetails = [];
let lastScan = "";
let lastTime = 0;

function getCameraLabel(cameraId) {
  const camera = cameraDetails.find(item => item.id === cameraId);
  return (camera?.label || "").toLowerCase();
}

function isBackCamera(cameraId) {
  const label = getCameraLabel(cameraId);
  return label.includes("back") || label.includes("rear") || label.includes("trase") || label.includes("environment");
}

function isFrontCamera(cameraId) {
  const label = getCameraLabel(cameraId);
  return label.includes("front") || label.includes("frontal") || label.includes("user") || label.includes("face") || label.includes("selfie");
}

function syncPreviewOrientation(cameraId) {
  const applyOrientation = () => {
    const video = document.querySelector("#reader video");
    if (!video) {
      return;
    }

    const shouldUnmirror = isFrontCamera(cameraId) && !isBackCamera(cameraId);
    video.style.transform = shouldUnmirror ? "scaleX(-1)" : "scaleX(1)";
    video.style.transformOrigin = "center center";
  };

  requestAnimationFrame(() => requestAnimationFrame(applyOrientation));
}

function getSelectedProductId() {
  const checked = document.querySelector("input[name='product_id']:checked");
  return checked ? checked.value : null;
}

function setMessage(main, details = "") {
  resultEl.textContent = main;
  detailsEl.textContent = details;
}

async function sendToken(token) {
  const productId = getSelectedProductId();
  if (!productId) {
    throw new Error("Selecione um produto antes de ler o QR.");
  }

  const form = new URLSearchParams();
  form.append("token", token);
  form.append("product_id", productId);

  const response = await fetch("api/bar_scan.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: form.toString()
  });
  return response.json();
}

function feedbackOK() {
  if (navigator.vibrate) navigator.vibrate(100);
}

async function initScanner() {
  try {
    const cams = await Html5Qrcode.getCameras();
    cameraDetails = cams;
    cameraIds = cams.map(c => c.id);
    currentCameraId = cameraIds[0] || null;
    const frontCam = cams.find(c => isFrontCamera(c.id) && !isBackCamera(c.id)) || cams.find(c => isFrontCamera(c.id));
    if (frontCam) currentCameraId = frontCam.id;
  } catch (e) {
    setMessage("Não foi possível aceder à câmara.");
    return;
  }

  scanner = new Html5Qrcode("reader");
  startScanner();
}

async function startScanner() {
  if (!scanner || !currentCameraId) return;
  try { await scanner.stop(); } catch (e) {}
  setMessage("Aguardando leitura…");
  detailsEl.textContent = "";

  await scanner.start(
    { deviceId: { exact: currentCameraId } },
    { fps: 10, qrbox: 250 },
    async decodedText => {
      const now = Date.now();
      if (decodedText === lastScan && now - lastTime < 2500) return;
      lastScan = decodedText;
      lastTime = now;

      setMessage("A validar...");
      detailsEl.textContent = "";
      try {
        const data = await sendToken(decodedText);
        if (!data.ok) {
          setMessage("❌ " + data.error);
          return;
        }
        feedbackOK();
        setMessage(`✅ ${data.name} | ${data.student_number}`);
        detailsEl.textContent = `Produto: ${data.product} | Valor: ${(data.price_cents / 100).toFixed(2).replace('.', ',')} € | Saldo: ${data.new_balance}`;
      } catch (error) {
        setMessage("❌ " + (error.message || "Erro de comunicação"));
      }
    },
    () => {}
  );

  syncPreviewOrientation(currentCameraId);
}

btnFS.addEventListener("click", async () => {
  try { await document.documentElement.requestFullscreen(); } catch (e) {}
});

btnSwitchCam.addEventListener("click", async () => {
  if (!cameraIds.length) return;
  const idx = cameraIds.indexOf(currentCameraId);
  currentCameraId = cameraIds[(idx + 1) % cameraIds.length];
  await startScanner();
});

btnReload.addEventListener("click", () => window.location.reload());

initScanner();
</script>
<?php page_footer(); ?>