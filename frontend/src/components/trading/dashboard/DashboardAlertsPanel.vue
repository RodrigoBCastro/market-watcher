<script setup>
import { computed } from 'vue'
import BaseButton from '../../ui/BaseButton.vue'
import StatusBadge from '../../ui/StatusBadge.vue'
import { formatDate } from '../../../utils/format'
import { mdiOpenInNew } from '../../../constants/icons'

const props = defineProps({
  alerts: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['open-alerts'])

const latest = computed(() => props.alerts?.latest || [])
</script>

<template>
  <div class="stacked-section">
    <div class="section-header-inline">
      <div>
        <p class="eyebrow">Não lidos</p>
        <p class="big-value">{{ Number(alerts?.unread_count ?? 0) }}</p>
      </div>
      <BaseButton
        size="sm"
        variant="ghost"
        :icon-path="mdiOpenInNew"
        @click="emit('open-alerts')"
      >
        Abrir central
      </BaseButton>
    </div>

    <ul class="alert-items">
      <li v-for="item in latest" :key="item.id" class="alert-item">
        <div class="section-header-inline">
          <strong>{{ item.title }}</strong>
          <StatusBadge :label="item.severity || 'info'" />
        </div>
        <p class="muted">{{ item.message }}</p>
        <small>{{ formatDate((item.created_at || '').slice(0, 10)) }}</small>
      </li>
    </ul>
  </div>
</template>
