<script setup>
import { computed } from 'vue'
import EmptyState from './EmptyState.vue'

const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: '' },
  compact: { type: Boolean, default: false },
  minWidth: { type: [String, Number], default: '' },
  wrapCells: { type: Boolean, default: false },
  disableScroll: { type: Boolean, default: false },
})

function formatValue(row, column) {
  const raw = row[column.key]

  if (typeof column.format === 'function') {
    return column.format(raw, row)
  }

  return raw ?? '-'
}

const tableStyle = computed(() => {
  if (props.minWidth === '' || props.minWidth === null) {
    return {}
  }

  if (typeof props.minWidth === 'number') {
    return { minWidth: `${props.minWidth}px` }
  }

  return { minWidth: props.minWidth }
})
</script>

<template>
  <div class="table-wrapper" :class="{ compact, 'no-scroll': disableScroll }">
    <table
      v-if="rows.length > 0"
      class="data-table"
      :class="{ 'wrap-cells': wrapCells }"
      :style="tableStyle"
    >
      <thead>
        <tr>
          <th v-for="col in columns" :key="col.key" :class="col.align ? `is-${col.align}` : ''">{{ col.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in rows" :key="rowKey ? row[rowKey] : `${index}-${row[columns[0]?.key]}`">
          <td
            v-for="col in columns"
            :key="`${index}-${col.key}`"
            :class="col.align ? `is-${col.align}` : ''"
          >
            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
              {{ formatValue(row, col) }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
    <EmptyState v-else title="Sem registros" text="A tabela será preenchida quando houver dados." />
  </div>
</template>
