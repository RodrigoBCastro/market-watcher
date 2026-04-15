<script setup>
import BaseButton from '../ui/BaseButton.vue'
import DataTable from '../ui/DataTable.vue'
import StatusBadge from '../ui/StatusBadge.vue'
import { mdiCheckCircleOutline, mdiEyeOutline, mdiOpenInNew, mdiShieldAlertOutline } from '../../constants/icons'
import { formatNumber } from '../../utils/format'

defineProps({
  items: { type: Array, default: () => [] },
  loadingSymbol: { type: String, default: '' },
  blacklistLoadingSymbol: { type: String, default: '' },
})

const emit = defineEmits(['open-asset', 'inspect-symbol', 'toggle-blacklist'])

const columns = [
  { key: 'symbol', label: 'Symbol' },
  { key: 'name', label: 'Nome' },
  { key: 'asset_type', label: 'Tipo' },
  { key: 'sector', label: 'Setor' },
  { key: 'last_close', label: 'Close', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'last_change_percent', label: 'Change %', align: 'right', format: (value) => formatNumber(value, 2) },
  { key: 'last_volume', label: 'Volume', align: 'right', format: (value) => formatNumber(value, 0) },
  { key: 'market_cap', label: 'Market Cap', align: 'right', format: (value) => formatNumber(value, 0) },
  { key: 'is_listed', label: 'Listado', align: 'center' },
  { key: 'is_blacklisted_for_monitoring', label: 'Blacklist', align: 'center' },
  { key: 'universe', label: 'Universo' },
  { key: 'actions', label: 'Ações', align: 'right' },
]
</script>

<template>
  <DataTable :columns="columns" :rows="items" row-key="id" compact min-width="100%" wrap-cells>
    <template #cell-is_listed="{ value }">
      <StatusBadge :label="value ? 'sim' : 'não'" :tone="value ? 'positive' : 'warning'" />
    </template>

    <template #cell-is_blacklisted_for_monitoring="{ value }">
      <StatusBadge :label="value ? 'bloqueado' : 'permitido'" :tone="value ? 'negative' : 'positive'" />
    </template>

    <template #cell-universe="{ row }">
      <span>{{ row?.monitored_asset?.universe_type || '-' }}</span>
    </template>

    <template #cell-actions="{ row }">
      <div class="section-actions">
        <BaseButton
          size="sm"
          variant="ghost"
          :icon-path="mdiEyeOutline"
          icon-only
          :loading="loadingSymbol === row.symbol"
          :aria-label="`Ver detalhe de ${row.symbol}`"
          :title="`Ver detalhe de ${row.symbol}`"
          @click="emit('inspect-symbol', row.symbol)"
        />

        <BaseButton
          size="sm"
          :variant="row?.is_blacklisted_for_monitoring ? 'secondary' : 'danger'"
          :icon-path="row?.is_blacklisted_for_monitoring ? mdiCheckCircleOutline : mdiShieldAlertOutline"
          icon-only
          :loading="blacklistLoadingSymbol === row.symbol"
          :aria-label="row?.is_blacklisted_for_monitoring ? `Desbloquear ${row.symbol}` : `Bloquear ${row.symbol}`"
          :title="row?.is_blacklisted_for_monitoring ? `Desbloquear ${row.symbol}` : `Bloquear ${row.symbol}`"
          @click="emit('toggle-blacklist', row)"
        />

        <BaseButton
          v-if="row?.monitored_asset?.ticker"
          size="sm"
          variant="ghost"
          :icon-path="mdiOpenInNew"
          icon-only
          :aria-label="`Abrir ${row.monitored_asset.ticker}`"
          :title="`Abrir ${row.monitored_asset.ticker}`"
          @click="emit('open-asset', row.monitored_asset.ticker)"
        />
        <span v-else class="muted">-</span>
      </div>
    </template>
  </DataTable>
</template>
