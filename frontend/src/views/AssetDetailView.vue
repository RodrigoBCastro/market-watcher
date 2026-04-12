<script setup>
import { onMounted, ref, watch } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import AssetPricePanel from '../components/assets/AssetPricePanel.vue'
import IndicatorSnapshotPanel from '../components/assets/IndicatorSnapshotPanel.vue'
import AssetAnalysisPanel from '../components/assets/AssetAnalysisPanel.vue'
import { mdiArrowLeft, mdiRefresh } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
  ticker: { type: String, required: true },
})

const emit = defineEmits(['back', 'notify'])

const loading = ref(true)
const error = ref('')
const quotes = ref([])
const indicators = ref([])
const analysis = ref(null)

async function loadAssetDetails() {
  loading.value = true
  error.value = ''

  const failures = []

  try {
    const response = await props.api.getAssetQuotes(props.ticker)
    quotes.value = response?.items || []
  } catch (requestError) {
    quotes.value = []
    failures.push(requestError?.message || 'Falha ao carregar cotações.')
  }

  try {
    const response = await props.api.getAssetIndicators(props.ticker)
    indicators.value = response?.items || []
  } catch (requestError) {
    indicators.value = []
    failures.push(requestError?.message || 'Falha ao carregar indicadores.')
  }

  try {
    analysis.value = await props.api.getAssetAnalysis(props.ticker)
  } catch (requestError) {
    analysis.value = null
    failures.push(requestError?.message || 'Falha ao carregar análise.')
  }

  if (failures.length === 3) {
    error.value = failures[0]
  }

  if (failures.length > 0 && failures.length < 3) {
    emit('notify', {
      tone: 'warning',
      message: 'Parte dos dados do ativo não pôde ser carregada.',
    })
  }

  loading.value = false
}

watch(() => props.ticker, loadAssetDetails)
onMounted(loadAssetDetails)
</script>

<template>
  <section class="view-stack">
    <SectionHeader :title="`Análise de ${ticker}`" subtitle="Leitura completa de preços, indicadores e decisão.">
      <template #actions>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiArrowLeft" @click="emit('back')">Voltar</BaseButton>
        <BaseButton size="sm" :icon-path="mdiRefresh" :loading="loading" @click="loadAssetDetails">Recarregar</BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <AssetPricePanel :symbol="ticker" :quotes="quotes" />
      <IndicatorSnapshotPanel :indicators="indicators" />
      <AssetAnalysisPanel :analysis="analysis" />
    </template>
  </section>
</template>
