<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";

require_staff();

$ticketTypes = [
  "almoco" => "Almoço"
];

page_header("Cantina - QR");
?>
<div class="mb-3">
  <h4 class="mb-0">Cantina</h4>
  <div class="text-muted">Selecione o tipo de senha e leia o QR do aluno.</div>
</div>

<div class="row g-3">
  <div class="col-12 col-xl-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Tipo de senha</label>
          <select id="ticketType" class="form-select">
            <?php foreach ($ticketTypes as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Notas</label>
          <input id="ticketNotes" class="form-control" placeholder="Opcional">
        </div>
        <div class="d-grid">
          <button id="btnReset" class="btn btn-outline-secondary">Reiniciar</button>
        </div>
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
          <span class="badge text-bg-secondary">Cantina</span>
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
const ticketType = document.getElementById("ticketType");
const ticketNotes = document.getElementById("ticketNotes");
const resultEl = document.getElementById("result");
const detailsEl = document.getElementById("details");
const btnFS = document.getElementById("btnFS");
const btnSwitchCam = document.getElementById("btnSwitchCam");
const btnReload = document.getElementById("btnReload");
const btnReset = document.getElementById("btnReset");

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

function setMessage(main, details = "") {
  resultEl.textContent = main;
  detailsEl.textContent = details;
}

async function sendToken(token) {
  const form = new URLSearchParams();
  form.append("token", token);
  form.append("ticket_type", ticketType.value);
  form.append("notes", ticketNotes.value);

  const response = await fetch("api/canteen_scan.php", {
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
    const backCam = cams.find(c => (c.label || "").toLowerCase().includes("back") || (c.label || "").toLowerCase().includes("trase"));
    if (backCam) currentCameraId = backCam.id;
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
        detailsEl.textContent = `Senha: ${ticketType.options[ticketType.selectedIndex].text} | Hora: ${new Date().toLocaleTimeString()}`;
      } catch (error) {
        setMessage("❌ Erro de comunicação");
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
btnReset.addEventListener("click", () => {
  ticketNotes.value = "";
  setMessage("Aguardando leitura…");
  detailsEl.textContent = "";
});

initScanner();
</script>
<?php page_footer(); ?>