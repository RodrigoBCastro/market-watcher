<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import RiskSettingsForm from '../components/trading/risk/RiskSettingsForm.vue'
import PositionSizingCalculator from '../components/trading/risk/PositionSizingCalculator.vue'
import ExposureMatrixPanel from '../components/trading/risk/ExposureMatrixPanel.vue'
import CorrelationPanel from '../components/trading/risk/CorrelationPanel.vue'
import StatCard from '../components/ui/StatCard.vue'
import { formatCurrency, formatPercent } from '../utils/format'
import { mdiRefresh } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['notify'])

const loading = ref(true)
const savingSettings = ref(false)
const sizingLoading = ref(false)
const error = ref('')

const riskSettings = ref({})
const riskSummary = ref({})
const exposure = ref({})
const correlations = ref({})
const sizingResult = ref(null)

async function loadRiskData() {
  loading.value = true
  error.value = ''

  try {
    const [settingsResponse, riskResponse, exposureResponse, correlationResponse] = await Promise.all([
      props.api.getRiskSettings(),
      props.api.getPortfolioRisk(),
      props.api.getPortfolioExposure(),
      props.api.getPortfolioCorrelations(),
    ])

    riskSettings.value = settingsResponse || {}
    riskSummary.value = riskResponse || {}
    exposure.value = exposureResponse || {}
    correlations.value = correlationResponse || {}
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar dados de risco.'
  } finally {
    loading.value = false
  }
}

async function saveSettings(payload) {
  savingSettings.value = true

  try {
    riskSettings.value = await props.api.updateRiskSettings(payload)
    emit('notify', { tone: 'success', message: 'Parâmetros de risco atualizados.' })
    await loadRiskData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível salvar parâmetros de risco.',
    })
  } finally {
    savingSettings.value = false
  }
}

async function calculateSizing(payload) {
  sizingLoading.value = true

  try {
    sizingResult.value = await props.api.calculatePositionSizing(payload)
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível calcular o sizing.',
    })
  } finally {
    sizingLoading.value = false
  }
}

onMounted(loadRiskData)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Risco e Exposição" subtitle="Configuração de limites, sizing e concentração do portfólio.">
      <template #actions>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiRefresh" :loading="loading" @click="loadRiskData">
          Atualizar
        </BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <div class="stat-grid compact">
        <StatCard title="Capital Total" :value="formatCurrency(riskSummary?.capital_total ?? 0)" subtitle="Configuração ativa" />
        <StatCard title="Capital Alocado" :value="formatCurrency(riskSummary?.capital_allocated ?? 0)" subtitle="Posições abertas" />
        <StatCard title="Capital Livre" :value="formatCurrency(riskSummary?.capital_free ?? 0)" subtitle="Disponível para novas entradas" />
        <StatCard title="Risco Aberto" :value="formatPercent(riskSummary?.open_risk_percent ?? 0, 2)" subtitle="Sobre capital total" />
      </div>

      <BaseCard>
        <div class="panel-heading">
          <h3>Configurações de Risco</h3>
          <p class="muted">Limites de capital, posição, concentração e correlação por conta.</p>
        </div>
        <RiskSettingsForm :model-value="riskSettings" :loading="savingSettings" @save="saveSettings" />
      </BaseCard>

      <BaseCard>
        <div class="panel-heading">
          <h3>Position Sizing</h3>
          <p class="muted">Cálculo automático de posição sugerida com base em risco por operação.</p>
        </div>
        <PositionSizingCalculator :result="sizingResult" :loading="sizingLoading" @calculate="calculateSizing" />
      </BaseCard>

      <BaseCard>
        <div class="panel-heading">
          <h3>Exposição por Setor e Ativo</h3>
          <p class="muted">Concentração de alocação e limites ativos no portfólio.</p>
        </div>
        <ExposureMatrixPanel :exposure="exposure" />
      </BaseCard>

      <BaseCard>
        <div class="panel-heading">
          <h3>Correlação entre posições</h3>
          <p class="muted">Monitoramento de ativos correlacionados para evitar concentração invisível.</p>
        </div>
        <CorrelationPanel :correlations="correlations" />
      </BaseCard>
    </template>
  </section>
</template>
