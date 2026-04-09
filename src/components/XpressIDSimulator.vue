<template>
  <div class="simulator">
    <h2>Validación de Identidad (Oz Leasing)</h2>

    <div v-if="!isBrowserSupported && !isMobile" class="browser-alert">
      <div class="warning-icon">🛑</div>
      <h3>Navegador no compatible</h3>
      <p>{{ browserWarning }}</p>
      <p class="instruction">
        <strong>Por favor, copia el enlace de esta página y ábrelo directamente en Google Chrome o Apple Safari.</strong>
      </p>
    </div>

    <div v-if="isBrowserSupported && !iframeUrl && !loading && !isMobile" class="id-warning-card">
      <div class="warning-icon">📄</div>
      <h3>Ten tu identificación a la mano</h3>
      <p>
        El proceso de validación tiene un tiempo limitado. Para evitar cancelar 
        tu solicitud o consumir intentos fallidos, por favor asegúrate de tener tu 
        <strong>Identificación Oficial</strong> lista antes de continuar.
      </p>
      <button @click="startProcess" class="primary-btn">
        {{ hasError ? "Reintentar Conexión" : "Tengo mi ID lista, Iniciar" }}
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>{{ loadingMessage }}</p>
    </div>

    <div v-if="qualityWarning" class="quality-alert">
      <h3>{{ qualityWarning }}</h3>
      <p>Reiniciando cámara en unos segundos...</p>
    </div>

    <div v-if="errorMsg" class="error-banner">
      {{ errorMsg }}
    </div>

    <div v-if="iframeUrl" class="iframe-container">
      <iframe
        :src="iframeUrl"
        width="100%"
        height="700"
        allow="camera; microphone; fullscreen; geolocation; gyroscope; accelerometer;"
        style="border: none"
      ></iframe>
    </div>

    <div v-if="result" class="success-card">
      <div class="icon">✅</div>
      <h3>Proceso Completado</h3>
      <p>La validación se ha realizado correctamente.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";

// Variables de entorno
const backendBase = import.meta.env.VITE_BACKEND_URL.replace(/\/$/, ""); 
const iframeBase = import.meta.env.VITE_IFRAME_URL; 
const mode = import.meta.env.VITE_MODE; 

// Estados
const iframeUrl = ref("");
const loading = ref(false);
const loadingMessage = ref("");
const errorMsg = ref("");
const qualityWarning = ref("");
const result = ref(null);
const hasError = ref(false);
const isMobile = ref(false);

// NUEVO: Estados para la compatibilidad del navegador
const isBrowserSupported = ref(true);
const browserWarning = ref("");

// NUEVO: Función para validar el navegador
const checkBrowserCompatibility = () => {
  const ua = navigator.userAgent || navigator.vendor || window.opera;

  // 1. Detectar navegadores embebidos de Redes Sociales (WebViews)
  // Estos bloquean la cámara casi siempre.
  if (ua.includes("FBAN") || ua.includes("FBAV") || ua.includes("Instagram") || ua.includes("WhatsApp") || ua.includes("LinkedInApp")) {
    isBrowserSupported.value = false;
    browserWarning.value = "Parece que abriste este enlace desde una red social (Facebook, Instagram, etc.). Estos navegadores internos bloquean el acceso a tu cámara.";
    return;
  }

  // 2. Detectar navegadores principales permitidos por Veridas
  const isChrome = /Chrome/.test(ua) && /Google Inc/.test(navigator.vendor);
  const isSafari = /Safari/.test(ua) && /Apple Computer/.test(navigator.vendor);
  const isFirefox = /Firefox/.test(ua);
  const isEdge = /Edg/.test(ua);
  const isOpera = /OPR/.test(ua) || /Opera/.test(ua);

  // Si no es ninguno de los principales, lanzamos advertencia
  // Nota: iOS Chrome y iOS Edge usan WebKit bajo el capó y se detectan diferente, 
  // pero la regla general los deja pasar si no son in-app browsers.
  if (!isChrome && !isSafari && !isFirefox && !isEdge && !isOpera) {
    // Aquí puedes decidir si bloquearlo por completo (false) o solo mostrar una advertencia (true pero con mensaje).
    // Por seguridad, lo bloqueamos pidiendo Chrome/Safari.
    isBrowserSupported.value = false;
    browserWarning.value = "Tu navegador actual podría no ser compatible con nuestro sistema de validación de identidad.";
  }
};

const startProcess = async () => {
  loading.value = true;
  loadingMessage.value = "Conectando con el servidor...";
  iframeUrl.value = "";
  errorMsg.value = "";
  result.value = null;
  hasError.value = false;

  try {
    if (!isMobile.value) {
      await navigator.mediaDevices.getUserMedia({ video: true });
    }

    loadingMessage.value = "Generando sesión segura...";
    const url = `${backendBase}/api_veridas/token.php`;

    const res = await fetch(url, { method: "POST" });
    const text = await res.text();

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      throw new Error("El servidor no devolvió un JSON válido.");
    }

    if (!res.ok || !data.access_token) {
      throw new Error(data.error || "No se recibió el token de acceso.");
    }

    iframeUrl.value = `${iframeBase}?access_token=${data.access_token}&mode=${mode}`;
    qualityWarning.value = "";

  } catch (e) {
    console.error(e);
    errorMsg.value = "Error: " + e.message;
    hasError.value = true;
    qualityWarning.value = "";
  } finally {
    loading.value = false;
  }
};

const handleMessage = (event) => {
  if (!event.origin.includes("veri-das")) return;

  const data = event.data;
  const code = data.code || (data.payload && data.payload.code);

  console.log("Evento XpressID:", code);

  const qualityErrors = [
    "BlurredImageError", 
    "GlareDetected", 
    "FaceNotFoundError", 
    "UnknownDocumentTypeError", 
    "AnalysisError", 
  ];

  if (qualityErrors.includes(code)) {
    let msg = "⚠️ Error de calidad detectado.";
    if (code === "BlurredImageError") msg = "⚠️ La foto salió borrosa. No te muevas.";
    if (code === "GlareDetected") msg = "⚠️ Se detectó mucho brillo (Flash).";
    if (code === "FaceNotFoundError") msg = "⚠️ No detectamos tu rostro.";
    if (code === "UnknownDocumentTypeError") msg = "⚠️ Documento no legible. Mejora la luz.";

    qualityWarning.value = msg;
    iframeUrl.value = ""; 

    console.log(`Reiniciando por ${code} en 2.5 segundos...`);
    setTimeout(() => {
      startProcess();
    }, 2500);
    return;
  }

  if (code === "ProcessCompleted" || data.event === "finish") {
    result.value = data.payload || data;
    iframeUrl.value = ""; 
    qualityWarning.value = "";
  } else if (code === "DocumentCaptured" || code === "SelfieCaptured") {
    qualityWarning.value = "";
  }
};

onMounted(async () => {
  // 1. Validar el navegador antes de hacer nada
  checkBrowserCompatibility();

  window.addEventListener("message", handleMessage);

  const urlParams = new URLSearchParams(window.location.search);
  const tokenFromUrl = urlParams.get("access_token");

  // Si viene del QR, nos saltamos la validación del navegador porque ya lo obligó la computadora
  // y procedemos directamente.
  if (tokenFromUrl) {
    console.log("Token detectado en URL. Continuando desde QR...");
    isMobile.value = true;
    loading.value = true;
    loadingMessage.value = "Abriendo cámara del celular...";

    try {
      await navigator.mediaDevices.getUserMedia({ video: true });
      iframeUrl.value = `${iframeBase}?access_token=${tokenFromUrl}&mode=${mode}`;
    } catch (err) {
      errorMsg.value = "Debes permitir el acceso a la cámara para continuar.";
    } finally {
      loading.value = false;
    }
  }
});

onBeforeUnmount(() => window.removeEventListener("message", handleMessage));
</script>

<style scoped>
.simulator {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  font-family: sans-serif;
  text-align: center;
}

/* ESTILOS DE LA ALERTA DE NAVEGADOR */
.browser-alert {
  background-color: #fef2f2;
  border: 1px solid #fca5a5;
  border-radius: 12px;
  padding: 30px 20px;
  margin: 20px auto;
  max-width: 500px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.browser-alert .warning-icon {
  font-size: 48px;
  margin-bottom: 10px;
}
.browser-alert h3 {
  color: #991b1b;
  margin-top: 0;
  margin-bottom: 10px;
}
.browser-alert p {
  color: #7f1d1d;
  font-size: 15px;
  line-height: 1.5;
  margin-bottom: 15px;
}
.browser-alert .instruction {
  background: #fff;
  padding: 10px;
  border-radius: 6px;
  border: 1px dashed #f87171;
  color: #b91c1c;
}

/* Estilos para la tarjeta de aviso de ID */
.id-warning-card {
  background-color: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  padding: 30px 20px;
  margin: 20px auto;
  max-width: 500px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.id-warning-card .warning-icon {
  font-size: 48px;
  margin-bottom: 10px;
}
.id-warning-card h3 {
  color: #0f172a;
  margin-top: 0;
  margin-bottom: 10px;
}
.id-warning-card p {
  color: #475569;
  font-size: 15px;
  line-height: 1.5;
  margin-bottom: 25px;
}

.primary-btn {
  background: #2563eb;
  color: white;
  padding: 14px 28px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: bold;
  transition: background 0.2s;
  width: 100%;
}
.primary-btn:hover { background: #1d4ed8; }

.loading-state { margin: 20px 0; color: #666; }
.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  animation: spin 1s linear infinite;
  margin: 0 auto 10px auto;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.quality-alert {
  background: #fefce8;
  color: #854d0e;
  border: 2px solid #fde047;
  padding: 20px;
  border-radius: 8px;
  margin: 20px 0;
}
.quality-alert h3 { margin: 0 0 5px 0; font-size: 18px; }
.quality-alert p { margin: 0; font-size: 14px; }

.error-banner {
  background: #fee2e2;
  color: #991b1b;
  padding: 15px;
  border-radius: 6px;
  margin: 20px 0;
}

.success-card {
  background: #dcfce7;
  color: #166534;
  padding: 30px;
  border-radius: 12px;
  margin-top: 20px;
}
.success-card .icon { font-size: 40px; margin-bottom: 10px; }

.iframe-container {
  margin-top: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  background: white;
}
</style>