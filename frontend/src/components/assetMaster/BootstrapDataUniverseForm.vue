<script setup>
import { reactive, watch } from 'vue'
import BaseButton from '../ui/BaseButton.vue'
import { mdiCheckCircleOutline, mdiCloseCircleOutline } from '../../constants/icons'

const props = defineProps({
  loading: { type: Boolean, default: false },
  modelValue: { type: Object, default: null },
})

const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  asset_types: 'stock',
  sectors: '',
  price_min: '',
  market_cap_min: '',
  volume_min: '',
  limit: 1000,
})

watch(
  () => props.modelValue,
  (value) => {
    if (!value) return
    form.asset_types = value.asset_types || 'stock'
    form.sectors = value.sectors || ''
    form.price_min = value.price_min ?? ''
    form.market_cap_min = value.market_cap_min ?? ''
    form.volume_min = value.volume_min ?? ''
    form.limit = Number(value.limit || 1000)
  },
  { immediate: true, deep: true },
)

function submit() {
  const assetTypes = form.asset_types
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)

  const sectors = form.sectors
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)

  emit('submit', {
    asset_types: assetTypes.length > 0 ? assetTypes : ['stock'],
    sectors,
    price_min: form.price_min === '' ? null : Number(form.price_min),
    market_cap_min: form.market_cap_min === '' ? null : Number(form.market_cap_min),
    volume_min: form.volume_min === '' ? null : Number(form.volume_min),
    limit: Number(form.limit || 1000),
  })
}
</script>

<template>
  <form class="asset-form asset-form-shell" @submit.prevent="submit">
    <div class="asset-form-grid">
      <label class="asset-form-field">
        Tipos de ativo (csv)
        <input v-model="form.asset_types" placeholder="stock" />
      </label>

      <label class="asset-form-field">
        Setores (csv)
        <input v-model="form.sectors" placeholder="Financeiro, Energia..." />
      </label>

      <label class="asset-form-field">
        Preço mínimo
        <input v-model="form.price_min" type="number" min="0" step="0.01" />
      </label>

      <label class="asset-form-field">
        Market cap mínimo
        <input v-model="form.market_cap_min" type="number" min="0" step="0.01" />
      </label>

      <label class="asset-form-field">
        Volume mínimo
        <input v-model="form.volume_min" type="number" min="0" step="1" />
      </label>

      <label class="asset-form-field">
        Limite
        <input v-model="form.limit" type="number" min="1" max="5000" />
      </label>
    </div>

    <div class="form-actions">
      <BaseButton variant="ghost" :icon-path="mdiCloseCircleOutline" type="button" @click="emit('cancel')">
        Cancelar
      </BaseButton>
      <BaseButton :icon-path="mdiCheckCircleOutline" type="submit" :loading="loading">
        Executar bootstrap
      </BaseButton>
    </div>
  </form>
</template>

