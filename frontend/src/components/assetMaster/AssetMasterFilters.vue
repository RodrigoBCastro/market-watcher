<script setup>
import { reactive, watch } from 'vue'
import BaseButton from '../ui/BaseButton.vue'
import { mdiFilterOutline } from '../../constants/icons'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'apply'])

const form = reactive({
  search: '',
  type: '',
  sector: '',
  listed: '',
  blacklisted: '',
  universe: '',
})

watch(
  () => props.modelValue,
  (value) => {
    form.search = value?.search || ''
    form.type = value?.type || ''
    form.sector = value?.sector || ''
    form.listed = value?.listed ?? ''
    form.blacklisted = value?.blacklisted ?? ''
    form.universe = value?.universe || ''
  },
  { immediate: true, deep: true },
)

function apply() {
  const payload = {
    search: form.search || null,
    type: form.type || null,
    sector: form.sector || null,
    listed: form.listed === '' ? null : form.listed,
    blacklisted: form.blacklisted === '' ? null : form.blacklisted,
    universe: form.universe || null,
  }

  emit('update:modelValue', payload)
  emit('apply')
}
</script>

<template>
  <div class="form-grid">
    <label>
      Busca
      <input v-model="form.search" class="date-input" type="text" placeholder="PETR4, Petrobras..." />
    </label>

    <label>
      Tipo
      <select v-model="form.type" class="date-input">
        <option value="">Todos</option>
        <option value="stock">stock</option>
        <option value="fund">fund</option>
        <option value="bdr">bdr</option>
        <option value="index">index</option>
        <option value="unknown">unknown</option>
      </select>
    </label>

    <label>
      Universo
      <select v-model="form.universe" class="date-input">
        <option value="">Todos</option>
        <option value="data_universe">data_universe</option>
        <option value="eligible_universe">eligible_universe</option>
        <option value="trading_universe">trading_universe</option>
      </select>
    </label>

    <label>
      Setor
      <input v-model="form.sector" class="date-input" type="text" placeholder="Financeiro..." />
    </label>

    <label>
      Listado
      <select v-model="form.listed" class="date-input">
        <option value="">Todos</option>
        <option value="true">Sim</option>
        <option value="false">Não</option>
      </select>
    </label>

    <label>
      Blacklist
      <select v-model="form.blacklisted" class="date-input">
        <option value="">Todos</option>
        <option value="true">Bloqueados</option>
        <option value="false">Permitidos</option>
      </select>
    </label>

    <div class="section-actions">
      <BaseButton :icon-path="mdiFilterOutline" :loading="loading" @click="apply">Aplicar filtros</BaseButton>
    </div>
  </div>
</template>
