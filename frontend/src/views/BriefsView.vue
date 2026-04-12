<script setup>
import { onMounted, ref } from 'vue'
import SectionHeader from '../components/ui/SectionHeader.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import LoadingState from '../components/ui/LoadingState.vue'
import BriefListPanel from '../components/briefs/BriefListPanel.vue'
import BriefDetailPanel from '../components/briefs/BriefDetailPanel.vue'
import { mdiFileDocumentEditOutline } from '../constants/icons'

const props = defineProps({
  api: { type: Object, required: true },
})

const emit = defineEmits(['open-asset', 'notify'])

const loading = ref(true)
const detailLoading = ref(false)
const generating = ref(false)
const error = ref('')
const generateDate = ref('')
const selectedDate = ref('')
const briefs = ref([])
const selectedBrief = ref(null)

async function loadBriefs() {
  loading.value = true
  error.value = ''

  try {
    const response = await props.api.getBriefs()
    briefs.value = response?.items || []

    if (!selectedDate.value && briefs.value.length > 0) {
      selectedDate.value = briefs.value[0].brief_date
      await loadBriefDetail(selectedDate.value)
    }
  } catch (requestError) {
    error.value = requestError?.message || 'Falha ao carregar histórico de briefs.'
  } finally {
    loading.value = false
  }
}

async function loadBriefDetail(date) {
  if (!date) {
    selectedBrief.value = null
    return
  }

  detailLoading.value = true
  selectedDate.value = date

  try {
    selectedBrief.value = await props.api.getBriefByDate(date)
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Falha ao carregar brief selecionado.',
    })
  } finally {
    detailLoading.value = false
  }
}

async function generateBrief() {
  generating.value = true

  try {
    const payloadDate = generateDate.value || null
    const response = await props.api.generateBrief(payloadDate)
    selectedBrief.value = response
    selectedDate.value = response?.brief_date || ''
    emit('notify', { tone: 'success', message: 'Brief diário gerado com sucesso.' })
    await loadBriefs()
  } catch (requestError) {
    emit('notify', {
      tone: 'error',
      message: requestError?.message || 'Não foi possível gerar o brief.',
    })
  } finally {
    generating.value = false
  }
}

onMounted(loadBriefs)
</script>

<template>
  <section class="view-stack">
    <SectionHeader title="Briefs Diários" subtitle="Contexto macro e ranking operacional consolidado.">
      <template #actions>
        <input v-model="generateDate" class="date-input" type="date" aria-label="Data do brief" />
        <BaseButton size="sm" :icon-path="mdiFileDocumentEditOutline" :loading="generating" @click="generateBrief">
          Gerar Brief
        </BaseButton>
      </template>
    </SectionHeader>

    <LoadingState v-if="loading" />
    <p v-else-if="error" class="form-error">{{ error }}</p>

    <div v-else class="briefs-grid">
      <BaseCard>
        <div class="panel-heading">
          <h3>Histórico</h3>
        </div>
        <BriefListPanel :items="briefs" :selected-date="selectedDate" @select="loadBriefDetail" />
      </BaseCard>

      <LoadingState v-if="detailLoading" />
      <BriefDetailPanel v-else :brief="selectedBrief" @open-asset="emit('open-asset', $event)" />
    </div>
  </section>
</template>
