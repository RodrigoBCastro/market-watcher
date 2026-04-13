<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import BacktestPanel from '../components/quant/BacktestPanel.vue'
import { mdiRefresh } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['notify'])

const loading = ref(true)
const runningBacktest = ref(false)
const error = ref('')
const backtests = ref([])

async function loadBacktests() {
  loading.value = true
  error.value = ''

  try {
    const response = await props.api.getBacktests(60)
    backtests.value = response?.items || []
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar histórico de backtests.'
  } finally {
    loading.value = false
  }
}

async function runBacktest(payload) {
  runningBacktest.value = true

  try {
    await props.api.runBacktest(payload)
    emit('notify', { tone: 'success', message: 'Backtest executado com sucesso.' })
    await loadBacktests()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao executar backtest.',
    })
  } finally {
    runningBacktest.value = false
  }
}

onMounted(loadBacktests)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Backtests" subtitle="Teste histórico da estratégia de sinais do motor em janela parametrizada.">
      <template #actions>
        <BaseButton size="sm" variant="ghost" :icon-path="mdiRefresh" :loading="loading" @click="loadBacktests">
          Atualizar
        </BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <BaseCard v-else>
      <BacktestPanel :items="backtests" :loading="runningBacktest" @run="runBacktest" />
    </BaseCard>
  </section>
</template>
