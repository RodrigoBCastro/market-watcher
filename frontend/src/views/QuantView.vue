<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import StatCard from '../components/ui/StatCard.vue'
import SetupRankingTable from '../components/quant/SetupRankingTable.vue'
import QuantAlertsPanel from '../components/quant/QuantAlertsPanel.vue'
import BacktestPanel from '../components/quant/BacktestPanel.vue'
import OptimizerPanel from '../components/quant/OptimizerPanel.vue'
import { formatNumber, formatPercent } from '../utils/format'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['notify'])

const loading = ref(true)
const error = ref('')
const runningBacktest = ref(false)
const runningOptimizer = ref(false)
const applyingWeights = ref(false)

const dashboard = ref({
  headline: {
    total_trades: 0,
    winrate: 0,
    expectancy: 0,
    profit_factor: null,
    max_drawdown: 0,
  },
  setup_ranking: [],
  alerts: [],
})

const backtests = ref([])
const optimizerWeights = ref({ technical_weight: 0.6, expectancy_weight: 0.4 })
const optimizerResult = ref(null)

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [dashboardResponse, backtestResponse, optimizerCurrentResponse] = await Promise.all([
      props.api.getQuantDashboard(),
      props.api.getBacktests(40),
      props.api.getOptimizerCurrent(),
    ])

    dashboard.value = dashboardResponse || dashboard.value
    backtests.value = backtestResponse?.items || []
    optimizerWeights.value = optimizerCurrentResponse?.weights || optimizerWeights.value
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar dashboard quant.'
  } finally {
    loading.value = false
  }
}

async function runBacktest(payload) {
  runningBacktest.value = true

  try {
    await props.api.runBacktest(payload)
    emit('notify', { tone: 'success', message: 'Backtest executado com sucesso.' })
    await loadData()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao executar backtest.',
    })
  } finally {
    runningBacktest.value = false
  }
}

async function runOptimizer() {
  runningOptimizer.value = true

  try {
    optimizerResult.value = await props.api.runOptimizer()
    emit('notify', { tone: 'success', message: 'Otimização concluída.' })
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao rodar otimizador.',
    })
  } finally {
    runningOptimizer.value = false
  }
}

async function applyWeights(weights) {
  applyingWeights.value = true

  try {
    const response = await props.api.applyOptimizerWeights(weights)
    optimizerWeights.value = response?.weights || optimizerWeights.value
    emit('notify', { tone: 'success', message: 'Pesos aplicados com sucesso.' })
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao aplicar pesos.',
    })
  } finally {
    applyingWeights.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Quant" subtitle="Edge estatístico, backtest e otimização de ranking.">
      <template #actions>
        <BaseButton size="sm" variant="ghost" :loading="loading" @click="loadData">Atualizar</BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <template v-else>
      <div class="stat-grid">
        <StatCard
          title="Winrate"
          :value="formatPercent(dashboard.headline.winrate, 2)"
          subtitle="Histórico consolidado"
        />
        <StatCard
          title="Expectancy"
          :value="formatNumber(dashboard.headline.expectancy, 3)"
          subtitle="Expectativa média por trade"
        />
        <StatCard
          title="Profit Factor"
          :value="formatNumber(dashboard.headline.profit_factor, 2)"
          subtitle="Lucro bruto / perda bruta"
        />
        <StatCard
          title="Max Drawdown"
          :value="formatPercent(dashboard.headline.max_drawdown, 2)"
          subtitle="Risco máximo observado"
        />
      </div>

      <div class="dashboard-grid">
        <BaseCard>
          <div class="panel-heading">
            <h3>Ranking de Setups</h3>
          </div>
          <SetupRankingTable :items="dashboard.setup_ranking" />
        </BaseCard>

        <BaseCard>
          <QuantAlertsPanel :alerts="dashboard.alerts" />
        </BaseCard>
      </div>

      <BaseCard>
        <BacktestPanel :items="backtests" :loading="runningBacktest" @run="runBacktest" />
      </BaseCard>

      <BaseCard>
        <OptimizerPanel
          :weights="optimizerWeights"
          :loading="runningOptimizer"
          :applying="applyingWeights"
          :result="optimizerResult"
          @run="runOptimizer"
          @apply="applyWeights"
        />
      </BaseCard>
    </template>
  </section>
</template>
