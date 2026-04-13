<script setup>
import { reactive, watch } from 'vue'
import BaseButton from '../ui/BaseButton.vue'
import FormSwitch from '../forms/FormSwitch.vue'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '../../constants/icons'

const props = defineProps({
  modelValue: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  assetId: null,
  ticker: '',
  data_universe: false,
  eligible_universe: false,
  trading_universe: false,
  manual_reason: '',
})

watch(
  () => props.modelValue,
  (value) => {
    form.assetId = value?.asset_id || null
    form.ticker = value?.ticker || ''
    form.data_universe = Boolean(value?.memberships?.data_universe)
    form.eligible_universe = Boolean(value?.memberships?.eligible_universe)
    form.trading_universe = Boolean(value?.memberships?.trading_universe)
    form.manual_reason = ''
  },
  { immediate: true },
)

function submit() {
  emit('save', {
    asset_id: form.assetId,
    ticker: form.ticker,
    states: {
      data_universe: form.data_universe,
      eligible_universe: form.eligible_universe,
      trading_universe: form.trading_universe,
    },
    manual_reason: form.manual_reason?.trim() || null,
  })
}
</script>

<template>
  <form class="asset-form asset-form-shell" @submit.prevent="submit">
    <p class="muted">
      Ajuste manual de universos para <strong>{{ form.ticker || '-' }}</strong>.
    </p>

    <div class="asset-switch-grid">
      <FormSwitch
        v-model="form.data_universe"
        label="Data Universe"
        description="Ativo coberto na coleta ampla de dados."
      />
      <FormSwitch
        v-model="form.eligible_universe"
        label="Eligible Universe"
        description="Ativo apto para análise operacional."
      />
      <FormSwitch
        v-model="form.trading_universe"
        label="Trading Universe"
        description="Ativo priorizado para calls e execução."
      />
    </div>

    <label class="asset-form-field">
      Motivo manual
      <textarea
        v-model="form.manual_reason"
        class="text-input"
        placeholder="Descreva o motivo da promoção/rebaixamento manual."
      />
    </label>

    <div class="form-actions">
      <BaseButton variant="ghost" :icon-path="mdiCloseCircleOutline" type="button" @click="emit('cancel')">
        Fechar
      </BaseButton>
      <BaseButton :icon-path="mdiCheckCircleOutline" type="submit" :loading="loading">
        Salvar universos
      </BaseButton>
    </div>
  </form>
</template>

