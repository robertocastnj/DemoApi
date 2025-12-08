<template>
  <div class="simulator">
    <h2>Simulación XpressID (Sandbox)</h2>

    <div class="input-group">
      <label>Access Token de Veridas</label>
      <input
        v-model="token"
        placeholder="Pega aquí el access_token que obtuviste en Postman"
      />

      <button @click="loadIframe" :disabled="!token">Iniciar simulación</button>
    </div>

    <div v-if="iframeUrl" class="iframe-container">
      <iframe
        :src="iframeUrl"
        width="100%"
        height="650"
        allow="camera; microphone; fullscreen; geolocation"
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

const token = ref("");
const iframeUrl = ref("");
const result = ref(null);

const requestPermissions = async () => {
  try {
    console.log("Solicitando permisos de cámara y micrófono...");

    await navigator.mediaDevices.getUserMedia({
      video: true,
      audio: true,
    });

    console.log("Permisos otorgados correctamente.");
    return true;
  } catch (err) {
    console.error("Permisos rechazados:", err);
    return false;
  }
};

const loadIframe = async () => {
  const granted = await requestPermissions();

  if (!granted) {
    alert("Debes otorgar los permisos de cámara y micrófono para continuar");
    return;
  }
  // Agregamos mode=sandbox para que simule INE + biometría
  iframeUrl.value = `https://xpressid-web-work.us.veri-das.com/v3/?access_token=${token.value}&mode=sandbox`;
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

onMounted(() => {
  window.addEventListener("message", handleMessage);
});

onBeforeUnmount(() => {
  window.removeEventListener("message", handleMessage);
});
</script>

<style scoped>
.simulator {
  max-width: 800px;
  margin: 20px auto;
  padding: 20px;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
    sans-serif;
}

h2 {
  margin-bottom: 15px;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.input-group input {
  padding: 8px 10px;
  font-size: 14px;
  border-radius: 4px;
  border: 1px solid #ccc;
}

button {
  align-self: flex-start;
  padding: 8px 14px;
  border-radius: 4px;
  border: none;
  background: #2563eb;
  color: white;
  cursor: pointer;
  font-size: 14px;
}

button:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.iframe-container {
  margin-top: 20px;
  border: 1px solid #ddd;
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
