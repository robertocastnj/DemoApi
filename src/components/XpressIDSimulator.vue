<template>
  <div class="simulator">
    <h2>Validación de Identidad (Oz Leasing)</h2>

    <button
      v-if="!iframeUrl && !loading"
      @click="startProcess"
      class="primary-btn"
    >
      {{ hasError ? "Reintentar Conexión" : "Iniciar Verificación" }}
    </button>

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
        allow="
          camera;
          microphone;
          fullscreen;
          geolocation;
          gyroscope;
          accelerometer;
        "
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

// --- CONFIGURACIÓN ---
const backendBase = import.meta.env.VITE_BACKEND_URL; // http://localhost/DemoApi/public
const iframeBase = import.meta.env.VITE_IFRAME_URL; // https://xpressid-web-work.us.veri-das.com/v3/
const mode = import.meta.env.VITE_MODE; // sandbox

// --- ESTADOS ---
const iframeUrl = ref("");
const loading = ref(false);
const loadingMessage = ref("");
const errorMsg = ref("");
const qualityWarning = ref("");
const result = ref(null);
const hasError = ref(false);

// --- FUNCIÓN PRINCIPAL: INICIAR PROCESO ---
const startProcess = async () => {
  loading.value = true;
  loadingMessage.value = "Conectando con el servidor...";
  iframeUrl.value = "";
  errorMsg.value = "";
  result.value = null;
  hasError.value = false;
  // No limpiamos qualityWarning aquí para que el usuario siga viendo el mensaje mientras carga el nuevo

  try {
    // 1. Verificar permisos de cámara antes de pedir token
    await navigator.mediaDevices.getUserMedia({ video: true });

    // 2. Pedir Token al Backend (PHP)
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

    // 3. Cargar Iframe con el token nuevo
    iframeUrl.value = `${iframeBase}?access_token=${data.access_token}&mode=${mode}`;

    // Una vez cargado, limpiamos cualquier advertencia previa
    qualityWarning.value = "";
  } catch (e) {
    console.error(e);
    errorMsg.value = "Error: " + e.message;
    hasError.value = true;
    qualityWarning.value = ""; // Si falla la conexión, quitamos la alerta de calidad
  } finally {
    loading.value = false;
  }
};

// --- MANEJADOR DE MENSAJES DEL IFRAME ---
const handleMessage = (event) => {
  // Filtro de seguridad: solo aceptar mensajes de Veridas
  if (!event.origin.includes("veri-das")) return;

  const data = event.data;
  // Normalizamos dónde buscar el código (a veces viene en data, a veces en payload)
  const code = data.code || (data.payload && data.payload.code);

  console.log("Evento XpressID:", code);

  // LISTA DE ERRORES QUE DEBEN CAUSAR REINICIO AUTOMÁTICO
  const qualityErrors = [
    "BlurredImageError", // Foto movida
    "GlareDetected", // Brillo/Flash
    "FaceNotFoundError", // No se ve la cara
    "UnknownDocumentTypeError", // Documento no reconocido/oscuro
    "AnalysisError", // Error genérico de análisis
  ];

  if (qualityErrors.includes(code)) {
    // 1. Definir mensaje para el usuario
    let msg = "⚠️ Error de calidad detectado.";
    if (code === "BlurredImageError")
      msg = "⚠️ La foto salió borrosa. No te muevas.";
    if (code === "GlareDetected") msg = "⚠️ Se detectó mucho brillo (Flash).";
    if (code === "FaceNotFoundError") msg = "⚠️ No detectamos tu rostro.";
    if (code === "UnknownDocumentTypeError")
      msg = "⚠️ Documento no legible. Mejora la luz.";

    // 2. Mostrar alerta y ocultar iframe
    qualityWarning.value = msg;
    iframeUrl.value = ""; // Matamos el iframe actual para ocultar el error técnico

    // 3. Programar el reinicio automático
    console.log(`Reiniciando por ${code} en 2.5 segundos...`);
    setTimeout(() => {
      startProcess(); // <--- REINICIO AUTOMÁTICO
    }, 2500);
    return;
  }

  // --- ÉXITO ---
  if (code === "ProcessCompleted" || data.event === "finish") {
    result.value = data.payload || data;
    iframeUrl.value = ""; // Cerramos iframe
    qualityWarning.value = "";
  }

  // Limpiar advertencia si pasa de etapa (ej. capturó bien documento)
  else if (code === "DocumentCaptured" || code === "SelfieCaptured") {
    qualityWarning.value = "";
  }
};

onMounted(() => window.addEventListener("message", handleMessage));
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

/* Botón Principal */
.primary-btn {
  background: #2563eb;
  color: white;
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 16px;
  font-weight: bold;
}
.primary-btn:hover {
  background: #1d4ed8;
}

/* Estados de Carga */
.loading-state {
  margin: 20px 0;
  color: #666;
}
.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  animation: spin 1s linear infinite;
  margin: 0 auto 10px auto;
}
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Alerta de Calidad (Amarilla) */
.quality-alert {
  background: #fefce8;
  color: #854d0e;
  border: 2px solid #fde047;
  padding: 20px;
  border-radius: 8px;
  margin: 20px 0;
}
.quality-alert h3 {
  margin: 0 0 5px 0;
  font-size: 18px;
}
.quality-alert p {
  margin: 0;
  font-size: 14px;
}

/* Error de Sistema (Rojo) */
.error-banner {
  background: #fee2e2;
  color: #991b1b;
  padding: 15px;
  border-radius: 6px;
  margin: 20px 0;
}

/* Éxito (Verde) */
.success-card {
  background: #dcfce7;
  color: #166534;
  padding: 30px;
  border-radius: 12px;
  margin-top: 20px;
}
.success-card .icon {
  font-size: 40px;
  margin-bottom: 10px;
}

/* Iframe Container */
.iframe-container {
  margin-top: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  background: white;
}
</style>
