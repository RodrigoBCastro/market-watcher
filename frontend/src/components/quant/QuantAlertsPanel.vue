<script setup>
defineProps({
  alerts: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="stacked-section">
    <h3>Alertas Quant</h3>
    <ul v-if="alerts.length > 0" class="alert-items">
      <li v-for="(alert, index) in alerts" :key="`${alert.type || 'alert'}-${index}`" class="alert-item">
        <strong>{{ alert.type || 'alerta' }}</strong>
        <span>{{ alert.message || '-' }}</span>
        <small>{{ alert.value ?? '-' }}</small>
      </li>
    </ul>
    <p v-else class="muted">Sem alertas ativos no momento.</p>
  </div>
</template>
