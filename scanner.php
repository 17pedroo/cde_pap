<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/includes/layout.php";

require_staff();

page_header("Acessos QR");
?>
<div class="hero-banner">
  <div>
    <span class="hero-label"><i class="bi bi-qr-code-scan"></i>Leitor de acessos</span>
    <h2>Entradas e saidas por QR</h2>
    <p>Use a camara frontal e acompanhe a validacao em tempo real para gerir acessos no recinto escolar.</p>
  </div>
  <div class="hero-actions">
    <a class="btn btn-primary" href="portaria_logs.php">Ver registos</a>
    <a class="btn btn-outline-light" href="admin_dashboard.php">Painel admin</a>
    <a class="btn btn-outline-light" href="register_student.php">Gerir alunos</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-12 col-xl-4">
    <div class="card shadow-sm h-100 metric-card">
      <div class="card-body">
        <h5 class="card-title mb-3">Controlo rapido</h5>
        <div class="d-grid gap-2 mb-4">
          <button id="btnFS" class="btn btn-dark">Ecra inteiro</button>
          <button id="btnSwitchCam" class="btn btn-outline-dark">Trocar camara</button>
          <button id="btnReload" class="btn btn-outline-secondary">Recarregar</button>
        </div>

        <div class="scan-status">
          <div class="fw-semibold mb-2">Boas praticas</div>
          <div class="text-muted small">Use a camara frontal, mantenha o QR estavel e recarregue apenas se a camera bloquear.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
          <div>
            <h5 class="card-title mb-1">Scanner de QR</h5>
            <div class="text-muted small">Aproxime o codigo do aluno para registar IN ou OUT.</div>
          </div>
          <span class="badge text-bg-secondary">Acessos</span>
        </div>

        <div class="scan-reader">
          <div id="reader"></div>
        </div>

        <div class="scan-status mt-3">
          <div id="result" class="fw-semibold">Aguardando leitura...</div>
          <div id="details" class="text-muted mt-1"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<audio id="beep" preload="auto">
  <source src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YQAAAAA=" type="audio/wav">
</audio>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
const resultEl = document.getElementById("result");
const detailsEl = document.getElementById("details");
const beep = document.getElementById("beep");

const btnFS = document.getElementById("btnFS");
const btnSwitchCam = document.getElementById("btnSwitchCam");
const btnReload = document.getElementById("btnReload");

let lastScan = "";
let lastTime = 0;
let currentCameraId = null;
let cameraIds = [];
let cameraDetails = [];
let scanner = null;

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

btnFS.addEventListener("click", async () => {
  try {
    const element = document.documentElement;
    if (element.requestFullscreen) {
      await element.requestFullscreen();
    }
    try {
      await beep.play();
      beep.pause();
      beep.currentTime = 0;
    } catch (error) {}
    resultEl.textContent = "Aguardando leitura...";
    detailsEl.textContent = "";
  } catch (error) {}
});

btnReload.addEventListener("click", () => location.reload());

async function sendToken(token) {
  const response = await fetch("api/scan.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "token=" + encodeURIComponent(token)
  });
  return response.json();
}

function feedbackOK() {
  try {
    beep.currentTime = 0;
    beep.play();
  } catch (error) {}

  if (navigator.vibrate) {
    navigator.vibrate(120);
  }
}

async function initScanner() {
  cameraIds = [];
  try {
    const cameras = await Html5Qrcode.getCameras();
    cameraDetails = cameras;
    cameraIds = cameras.map(camera => camera.id);
    currentCameraId = cameraIds[0] || null;

    const frontCamera = cameras.find(camera => isFrontCamera(camera.id) && !isBackCamera(camera.id)) ||
      cameras.find(camera => isFrontCamera(camera.id));

    if (frontCamera) {
      currentCameraId = frontCamera.id;
    }
  } catch (error) {
    resultEl.textContent = "Nao foi possivel aceder a camara.";
    detailsEl.textContent = "Verifique permissoes do navegador.";
    return;
  }

  scanner = new Html5Qrcode("reader");
  await startWithCamera(currentCameraId);
}

async function startWithCamera(cameraId) {
  if (!scanner || !cameraId) {
    return;
  }

  try {
    await scanner.stop();
  } catch (error) {}

  resultEl.textContent = "Aguardando leitura...";
  detailsEl.textContent = "";

  await scanner.start(
    { deviceId: { exact: cameraId } },
    { fps: 10, qrbox: 250 },
    async decodedText => {
      const now = Date.now();
      if (decodedText === lastScan && (now - lastTime) < 2500) {
        return;
      }

      lastScan = decodedText;
      lastTime = now;
      resultEl.textContent = "A validar...";
      detailsEl.textContent = "";

      try {
        const data = await sendToken(decodedText);
        if (data.ok) {
          feedbackOK();
          resultEl.textContent = `${data.name} - ${data.action} registado`;
          detailsEl.textContent = `Hora: ${data.time} | Numero: ${data.student_number ?? "-"}`;
        } else {
          resultEl.textContent = "Erro: " + data.error;
        }
      } catch (error) {
        resultEl.textContent = "Erro ao comunicar com o servidor.";
      }
    },
    () => {}
  );

  syncPreviewOrientation(cameraId);
}

btnSwitchCam.addEventListener("click", async () => {
  if (!cameraIds.length || !scanner) {
    return;
  }

  const currentIndex = cameraIds.indexOf(currentCameraId);
  const nextIndex = currentIndex === -1 ? 0 : (currentIndex + 1) % cameraIds.length;
  currentCameraId = cameraIds[nextIndex];

  try {
    await startWithCamera(currentCameraId);
    resultEl.textContent = "Aguardando leitura...";
    detailsEl.textContent = "Camara trocada.";
  } catch (error) {
    resultEl.textContent = "Nao foi possivel trocar a camara.";
    detailsEl.textContent = "";
  }
});

initScanner();
</script>

<?php page_footer(); ?>

