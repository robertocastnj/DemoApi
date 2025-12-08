<template>
  <div class="proceso-container">
    <!-- Título principal -->
    <div class="titulo-principal">
      Has elegido iniciar tu proceso en línea y estás un paso más cerca de
      obtener tu vehículo, para ello sigue las siguientes instrucciones:
    </div>

    <!-- PASOS -->
    <div class="steps-wrapper">
      <!-- Paso 1 -->
      <div class="step-card">
        <div class="step-left">
          <span class="step-title">Paso 1</span>
          <span class="step-icon">👤</span>
        </div>

        <div class="divider"></div>

        <div class="step-description">Validemos tu identidad</div>
      </div>

      <!-- Paso 2 -->
      <div class="step-card">
        <div class="step-left">
          <span class="step-title">Paso 2</span>
          <span class="step-icon">💳</span>
        </div>

        <div class="divider"></div>

        <div class="step-description">Calificación de crédito</div>
      </div>

      <!-- Paso 3 -->
      <div class="step-card">
        <div class="step-left">
          <span class="step-title">Paso 3</span>
          <span class="step-icon">📄</span>
        </div>

        <div class="divider"></div>

        <div class="step-description">Cargar documentos</div>
      </div>
    </div>
  </div>
</template>

<script setup>
// No necesita lógica
</script>

<style scoped>
/* CONTENEDOR GENERAL */
.proceso-container {
  width: 100%;
  max-width: 1400px;
  margin: 40px auto;
  text-align: center;
  font-family: "Inter", sans-serif;
}

/* TITULAR */
.titulo-principal {
  font-size: 26px;
  font-weight: 400;
  margin-bottom: 50px;
  max-width: 950px;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.4;
  color: #1d1d1f;
}

/* WRAPPER DE LOS PASOS */
.steps-wrapper {
  width: 100%;
}

/* CADA TARJETA DE PASO */
.step-card {
  background: #111111; /* color exacto del mockup */
  color: #ffffff;
  display: flex;
  align-items: center;
  padding: 28px 40px;
  border-bottom: 1px solid #2a2a2a; /* línea igual a la imagen */
}

/* IZQUIERDA: Paso + icono */
.step-left {
  display: flex;
  align-items: center;
  gap: 20px;
  min-width: 260px;
}

.step-title {
  font-size: 40px;
  font-weight: 700;
}

.step-icon {
  font-size: 38px;
}

/* SEPARADOR VERTICAL */
.divider {
  width: 2px;
  height: 50px;
  background: white;
  margin: 0 40px;
}

/* DESCRIPCIÓN DERECHA */
.step-description {
  font-size: 32px;
  font-weight: 500;
  color: white;
}
</style>
