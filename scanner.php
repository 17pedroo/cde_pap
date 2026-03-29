<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";
require_staff();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Leitor - Portaria</title>
  <style>
    body { background:#f8f9fa; }
    .kiosk-wrap { max-width: 720px; }
    #reader { width: 100%; max-width: 520px; margin: 0 auto; }
    .big-status { font-size: 1.1rem; }
  </style>
</head>
<body>
<div class="container py-3 kiosk-wrap">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <h3 class="mb-0">Leitor de Entradas</h3>
      <div class="text-muted">Aproxime o QR Code do aluno</div>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="logout.php">Sair</a>
  </div>

  <div class="d-grid gap-2 d-md-flex mb-3">
    <button id="btnFS" class="btn btn-dark">Ecrã inteiro</button>
    <button id="btnSwitchCam" class="btn btn-outline-dark">Trocar câmara</button>
    <button id="btnReload" class="btn btn-outline-secondary">Recarregar</button>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div id="reader"></div>
      <hr>
      <div id="result" class="big-status fw-semibold">Aguardando leitura…</div>
      <div id="details" class="text-muted"></div>
    </div>
  </div>

  <div class="text-muted mt-3 small">
    Dica: para melhor leitura, use a câmara traseira e boa iluminação.
  </div>
</div>

<!-- som simples (alguns browsers só tocam depois do primeiro clique - por isso temos botão de ecrã inteiro) -->
<audio id="beep" preload="auto">
  <source src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YQAAAAA=" type="audio/wav">
</audio>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
  const resultEl  = document.getElementById('result');
  const detailsEl = document.getElementById('details');
  const beep      = document.getElementById('beep');

  const btnFS        = document.getElementById('btnFS');
  const btnSwitchCam = document.getElementById('btnSwitchCam');
  const btnReload    = document.getElementById('btnReload');

  let lastScan = "";
  let lastTime = 0;

  // fullscreen
  btnFS.addEventListener("click", async () => {
    try {
      const el = document.documentElement;
      if (el.requestFullscreen) await el.requestFullscreen();
      // tentativa de "armar" o som (alguns browsers só deixam tocar após interação)
      try { beep.play().then(()=>beep.pause()).catch(()=>{}); } catch(e) {}
      resultEl.textContent = "Aguardando leitura…";
      detailsEl.textContent = "";
    } catch (e) {}
  });

  btnReload.addEventListener("click", () => location.reload());

  async function sendToken(token) {
    const r = await fetch("api/scan.php", {
      method: "POST",
      headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: "token=" + encodeURIComponent(token)
    });
    return r.json();
  }

  function feedbackOK() {
    try { beep.currentTime = 0; beep.play(); } catch(e) {}
    if (navigator.vibrate) navigator.vibrate(120);
  }

  // ===== Camera / Scanner setup =====
  let currentCameraId = null;
  let cameraIds = [];
  let scanner = null;

  async function initScanner() {
    // lista câmaras
    cameraIds = [];
    try {
      const cams = await Html5Qrcode.getCameras();
      cameraIds = cams.map(c => c.id);
      currentCameraId = cameraIds[0] || null;
    } catch (e) {
      resultEl.textContent = "❌ Não foi possível aceder à câmara (permissões?).";
      detailsEl.textContent = "";
      return;
    }

    // tenta escolher a traseira se existir
    // (nem sempre dá para identificar nomes em todos os dispositivos, mas tentamos)
    try {
      const cams = await Html5Qrcode.getCameras();
      const back = cams.find(c => (c.label || "").toLowerCase().includes("back")) ||
                   cams.find(c => (c.label || "").toLowerCase().includes("trase")) ||
                   cams.find(c => (c.label || "").toLowerCase().includes("rear"));
      if (back) currentCameraId = back.id;
    } catch (e) {}

    // cria scanner
    scanner = new Html5Qrcode("reader");

    await startWithCamera(currentCameraId);
  }

  async function startWithCamera(camId) {
    if (!scanner || !camId) return;

    // se já está a correr, para primeiro
    try { await scanner.stop(); } catch(e) {}

    resultEl.textContent = "Aguardando leitura…";
    detailsEl.textContent = "";

    const config = { fps: 10, qrbox: 250 };

    await scanner.start(
      { deviceId: { exact: camId } },
      config,
      async (decodedText) => {
        // anti-dup (evita disparar mil vezes)
        const now = Date.now();
        if (decodedText === lastScan && (now - lastTime) < 2500) return;
        lastScan = decodedText;
        lastTime = now;

        resultEl.textContent = "A validar…";
        detailsEl.textContent = "";

        try {
          const data = await sendToken(decodedText);
          if (data.ok) {
            feedbackOK();
            resultEl.textContent = `✅ ${data.name} — ${data.action} registado`;
            detailsEl.textContent = `Hora: ${data.time} | Nº: ${data.student_number ?? "-"}`;
          } else {
            resultEl.textContent = "❌ Erro: " + data.error;
          }
        } catch (e) {
          resultEl.textContent = "❌ Erro ao comunicar com o servidor.";
        }
      },
      () => { /* ignore scan failure callback */ }
    );
  }

  // trocar câmera
  btnSwitchCam.addEventListener("click", async () => {
    if (!cameraIds.length || !scanner) return;

    const idx = cameraIds.indexOf(currentCameraId);
    const nextIdx = (idx === -1) ? 0 : (idx + 1) % cameraIds.length;
    currentCameraId = cameraIds[nextIdx];

    try {
      await startWithCamera(currentCameraId);
      resultEl.textContent = "Aguardando leitura…";
      detailsEl.textContent = "Câmara trocada.";
    } catch (e) {
      resultEl.textContent = "❌ Não foi possível trocar a câmara.";
      detailsEl.textContent = "";
    }
  });

  // iniciar
  initScanner();
</script>
</body>
</html>

