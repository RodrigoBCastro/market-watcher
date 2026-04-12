<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import OpportunityTable from '../components/opportunities/OpportunityTable.vue'
import { mdiFilterOutline } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['open-asset', 'notify'])

const loading = ref(true)
const error = ref('')
const filterDate = ref('')
const top = ref([])
const avoid = ref([])
const marketBias = ref('neutro')
const resultDate = ref('')

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const date = filterDate.value || null
    const [topResponse, avoidResponse] = await Promise.all([
      props.api.getOpportunitiesTop(date),
      props.api.getOpportunitiesAvoid(date),
    ])

    top.value = topResponse?.items || []
    avoid.value = avoidResponse?.items || []
    resultDate.value = topResponse?.date || avoidResponse?.date || ''
    marketBias.value = topResponse?.market_bias || avoidResponse?.market_bias || 'neutro'
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar oportunidades.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Oportunidades" subtitle="Ranking do motor de decisão para entrada e exclusão.">
      <template #actions>
        <input v-model="filterDate" class="date-input" type="date" aria-label="Filtrar por data" />
        <BaseButton
          size="sm"
          variant="ghost"
          :icon-path="mdiFilterOutline"
          :loading="loading"
          @click="loadData"
        >
          Filtrar
        </BaseButton>
      </template>
    </SectionHeader>

    <BaseCard>
      <div class="inline-meta">
        <span><strong>Data:</strong> {{ resultDate || '-' }}</span>
        <span><strong>Viés:</strong> {{ marketBias }}</span>
      </div>
    </BaseCard>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <div v-else class="opportunity-grid">
      <BaseCard>
        <OpportunityTable title="Top 10 Oportunidades" :items="top" @open-asset="emit('open-asset', $event)" />
      </BaseCard>
      <BaseCard>
        <OpportunityTable title="Top 10 Para Evitar" :items="avoid" @open-asset="emit('open-asset', $event)" />
      </BaseCard>
    </div>
  </section>
</template>
