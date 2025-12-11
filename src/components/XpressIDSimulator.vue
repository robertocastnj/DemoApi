<template>
  <div class="simulator">
    <h2>Simulación XpressID</h2>

    <button @click="loadIframe">Iniciar simulación</button>

    <div v-if="iframeUrl" class="iframe-container">
      <iframe
        :src="iframeUrl"
        width="100%"
        height="650"
        allow="camera; fullscreen; geolocation"
        style="border: none"
      ></iframe>
    </div>

    <div v-if="result" class="result">
      <h3>Resultado recibido del iframe</h3>
      <pre>{{ result }}</pre>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
const backendUrl = import.meta.env.VITE_BACKEND_URL;
const iframeBase = import.meta.env.VITE_IFRAME_URL;
const mode = import.meta.env.VITE_MODE;

async function getToken() {
  const res = await fetch(`${backendUrl}/api_veridas/token.php`, {
    method: "POST",
  });

  const text = await res.text();
  console.log("RAW RESPONSE FROM PHP:", text);

  let data;
  try {
    data = JSON.parse(text);
  } catch (e) {
    console.error("JSON inválido:", e);
    return null;
  }

  console.log("PARSED JSON:", data);

  return data.access_token;
}

const token = ref("");
const iframeUrl = ref("");
const result = ref(null);

const requestPermissions = async () => {
  try {
    console.log("Solicitando permisos...");
    await navigator.mediaDevices.getUserMedia({ video: true });
    console.log("Permisos concedidos.");
    return true;
  } catch (err) {
    console.error("Permisos rechazados:", err);
    return false;
  }
};

const loadIframe = async () => {
  const granted = await requestPermissions();
  if (!granted) {
    alert("Debes otorgar permisos de cámara para continuar");
    return;
  }

  const generatedToken = await getToken();
  if (!generatedToken) {
    alert("No fue posible obtener el token");
    return;
  }

  token.value = generatedToken;

  iframeUrl.value = `${iframeBase}?access_token=${generatedToken}&mode=${mode}`;
  result.value = null;
};

const handleMessage = (event) => {
  if (!event.origin.includes("veri-das.com")) return;

  console.log("Mensaje desde XpressID:", event.data);

  if (event.data?.event === "finish") {
    result.value = event.data.payload;
  } else if (event.data?.event === "error") {
    result.value = {
      error: true,
      details: event.data.payload,
    };
  }
};

onMounted(() => window.addEventListener("message", handleMessage));
onBeforeUnmount(() => window.removeEventListener("message", handleMessage));
</script>

<style scoped>
.simulator {
  width: 100%;
  margin: 20px auto;
  padding: 20px;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
    sans-serif;
}
button {
  padding: 8px 14px;
  border-radius: 4px;
  border: none;
  background: #2563eb;
  color: white;
  cursor: pointer;
  font-size: 14px;
}
.iframe-container {
  margin-top: 20px;
  border: 1px solid #ddd;
  width: 100%;
}
.result {
  margin-top: 20px;
  background: #f3f4f6;
  border-radius: 6px;
  padding: 12px;
  font-size: 13px;
  white-space: pre-wrap;
}
</style>
