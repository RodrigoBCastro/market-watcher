<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import BaseButton from './BaseButton.vue'
import EmptyState from './EmptyState.vue'
import LoadingState from './LoadingState.vue'

const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  rowKey: { type: [String, Function], default: '' },
  minWidth: { type: [String, Number], default: '' },
  maxHeight: { type: [String, Number], default: '' },
  compact: { type: Boolean, default: false },
  wrapCells: { type: Boolean, default: false },
  disableScroll: { type: Boolean, default: false },
  stickyHeader: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  fallbackValue: { type: String, default: '-' },
  emptyTitle: { type: String, default: 'Sem registros' },
  emptyText: { type: String, default: 'A tabela sera preenchida quando houver dados.' },

  enablePagination: { type: Boolean, default: false },
  paginationMode: { type: String, default: 'client' },
  pagination: { type: Object, default: null },
  page: { type: Number, default: null },
  perPage: { type: Number, default: null },
  totalRows: { type: Number, default: null },
  defaultPerPage: { type: Number, default: 10 },
  perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
  showPerPageSelector: { type: Boolean, default: true },
  showPaginationSummary: { type: Boolean, default: true },

  sortMode: { type: String, default: 'client' },
  sortKey: { type: String, default: '' },
  sortDirection: { type: String, default: '' },
  initialSortKey: { type: String, default: '' },
  initialSortDirection: { type: String, default: 'asc' },

  actions: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'update:page',
  'update:perPage',
  'update:sortKey',
  'update:sortDirection',
  'pagination-change',
  'page-change',
  'per-page-change',
  'sort-change',
  'action',
])

const OVERLAY_DELAY_MS = 140

function hasExplicitValue(value) {
  return value !== null && value !== undefined
}

function toCssSize(value) {
  if (value === '' || value === null || value === undefined) {
    return ''
  }

  if (typeof value === 'number') {
    return `${value}px`
  }

  return String(value)
}

function toPositiveInt(value, fallback = 1) {
  const parsed = Number(value)
  if (Number.isFinite(parsed) && parsed > 0) {
    return Math.floor(parsed)
  }
  return fallback
}

function toNonNegativeInt(value, fallback = 0) {
  const parsed = Number(value)
  if (Number.isFinite(parsed) && parsed >= 0) {
    return Math.floor(parsed)
  }
  return fallback
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max)
}

function normalizeSortDirection(value, fallback = 'asc') {
  const normalized = String(value || '').toLowerCase()
  if (normalized === 'asc' || normalized === 'desc') {
    return normalized
  }
  return fallback
}

function getValueByPath(source, path) {
  if (!source || typeof path !== 'string' || !path.trim()) {
    return undefined
  }

  return path.split('.').reduce((accumulator, key) => {
    if (accumulator === null || accumulator === undefined) {
      return undefined
    }

    return accumulator[key]
  }, source)
}

function resolveDynamicValue(candidate, payload, fallback = '') {
  if (typeof candidate === 'function') {
    return candidate(payload)
  }

  return candidate ?? fallback
}

const internalPage = ref(toPositiveInt(props.page ?? props.pagination?.current_page ?? props.pagination?.page, 1))
const internalPerPage = ref(
  toPositiveInt(props.perPage ?? props.pagination?.per_page, toPositiveInt(props.defaultPerPage, 10)),
)
const internalSortKey = ref(props.sortKey || props.initialSortKey || '')
const internalSortDirection = ref(normalizeSortDirection(props.sortDirection || props.initialSortDirection, 'asc'))

watch(
  () => props.pagination,
  (meta) => {
    if (!meta) {
      return
    }

    if (meta.current_page !== undefined || meta.page !== undefined) {
      internalPage.value = toPositiveInt(meta.current_page ?? meta.page, internalPage.value)
    }

    if (meta.per_page !== undefined) {
      internalPerPage.value = toPositiveInt(meta.per_page, internalPerPage.value)
    }
  },
  { deep: true },
)

watch(
  () => props.page,
  (value) => {
    if (hasExplicitValue(value)) {
      internalPage.value = toPositiveInt(value, internalPage.value)
    }
  },
)

watch(
  () => props.perPage,
  (value) => {
    if (hasExplicitValue(value)) {
      internalPerPage.value = toPositiveInt(value, internalPerPage.value)
    }
  },
)

watch(
  () => props.sortKey,
  (value) => {
    if (hasExplicitValue(value)) {
      internalSortKey.value = value
    }
  },
)

watch(
  () => props.sortDirection,
  (value) => {
    if (hasExplicitValue(value)) {
      internalSortDirection.value = normalizeSortDirection(value, internalSortDirection.value)
    }
  },
)

const wrapperStyle = computed(() => {
  const maxHeight = toCssSize(props.maxHeight)
  if (!maxHeight) {
    return {}
  }

  return { maxHeight }
})

const tableStyle = computed(() => {
  const minWidth = toCssSize(props.minWidth)
  if (!minWidth) {
    return {}
  }

  return { minWidth }
})

const isPaginationEnabled = computed(() => props.enablePagination)
const isServerPagination = computed(() => isPaginationEnabled.value && props.paginationMode === 'server')
const isServerSort = computed(() => props.sortMode === 'server' || isServerPagination.value)

const resolvedPerPage = computed(() => {
  if (hasExplicitValue(props.perPage)) {
    return toPositiveInt(props.perPage, internalPerPage.value)
  }

  if (props.pagination?.per_page !== undefined) {
    return toPositiveInt(props.pagination.per_page, internalPerPage.value)
  }

  return toPositiveInt(internalPerPage.value, toPositiveInt(props.defaultPerPage, 10))
})

const resolvedPageRaw = computed(() => {
  if (hasExplicitValue(props.page)) {
    return toPositiveInt(props.page, 1)
  }

  if (props.pagination?.current_page !== undefined || props.pagination?.page !== undefined) {
    return toPositiveInt(props.pagination.current_page ?? props.pagination.page, 1)
  }

  return toPositiveInt(internalPage.value, 1)
})

const resolvedSortKey = computed(() => props.sortKey || internalSortKey.value || '')
const resolvedSortDirection = computed(() =>
  normalizeSortDirection(props.sortDirection || internalSortDirection.value, 'asc'),
)

const normalizedPerPageOptions = computed(() => {
  const values = [...props.perPageOptions, resolvedPerPage.value]
    .map((value) => toPositiveInt(value, 0))
    .filter((value) => value > 0)

  return [...new Set(values)].sort((first, second) => first - second)
})

const columnByKey = computed(() => {
  const map = new Map()
  for (const column of props.columns) {
    map.set(column.key, column)
  }
  return map
})

function getColumnRawValue(row, column) {
  if (typeof column.value === 'function') {
    return column.value(row, column)
  }

  if (typeof column.path === 'string' && column.path) {
    return getValueByPath(row, column.path)
  }

  return row?.[column.key]
}

function getColumnSortValue(row, column) {
  if (typeof column.sortValue === 'function') {
    return column.sortValue(row, column)
  }

  if (typeof column.sortKey === 'string' && column.sortKey) {
    return getValueByPath(row, column.sortKey)
  }

  return getColumnRawValue(row, column)
}

function formatValue(row, column) {
  const raw = getColumnRawValue(row, column)

  if (typeof column.format === 'function') {
    return column.format(raw, row, column)
  }

  return raw ?? props.fallbackValue
}

function compareValues(first, second) {
  if (first === null || first === undefined) {
    return second === null || second === undefined ? 0 : 1
  }

  if (second === null || second === undefined) {
    return -1
  }

  if (first instanceof Date && second instanceof Date) {
    return first.getTime() - second.getTime()
  }

  if (typeof first === 'number' && typeof second === 'number') {
    return first - second
  }

  const parsedFirst = Number(first)
  const parsedSecond = Number(second)
  if (
    Number.isFinite(parsedFirst) &&
    Number.isFinite(parsedSecond) &&
    String(first).trim() !== '' &&
    String(second).trim() !== ''
  ) {
    return parsedFirst - parsedSecond
  }

  const firstTimestamp = Date.parse(first)
  const secondTimestamp = Date.parse(second)
  if (Number.isFinite(firstTimestamp) && Number.isFinite(secondTimestamp)) {
    return firstTimestamp - secondTimestamp
  }

  return String(first).localeCompare(String(second), 'pt-BR', {
    numeric: true,
    sensitivity: 'base',
  })
}

function isColumnSortable(column) {
  return Boolean(column?.sortable)
}

const sortedRows = computed(() => {
  if (isServerSort.value || !resolvedSortKey.value) {
    return props.rows
  }

  const column = columnByKey.value.get(resolvedSortKey.value)
  if (!column || !isColumnSortable(column)) {
    return props.rows
  }

  const directionFactor = resolvedSortDirection.value === 'desc' ? -1 : 1
  return [...props.rows].sort((firstRow, secondRow) => {
    const firstValue = getColumnSortValue(firstRow, column)
    const secondValue = getColumnSortValue(secondRow, column)
    return compareValues(firstValue, secondValue) * directionFactor
  })
})

const resolvedTotalRows = computed(() => {
  if (!isPaginationEnabled.value) {
    return sortedRows.value.length
  }

  if (isServerPagination.value) {
    if (hasExplicitValue(props.totalRows)) {
      return toNonNegativeInt(props.totalRows, sortedRows.value.length)
    }

    if (props.pagination?.total !== undefined) {
      return toNonNegativeInt(props.pagination.total, sortedRows.value.length)
    }
  }

  return sortedRows.value.length
})

const totalPages = computed(() => {
  if (!isPaginationEnabled.value) {
    return 1
  }

  return Math.max(1, Math.ceil(resolvedTotalRows.value / resolvedPerPage.value))
})

const resolvedPage = computed(() => clamp(resolvedPageRaw.value, 1, totalPages.value))

const visibleRows = computed(() => {
  if (!isPaginationEnabled.value || isServerPagination.value) {
    return sortedRows.value
  }

  const start = (resolvedPage.value - 1) * resolvedPerPage.value
  return sortedRows.value.slice(start, start + resolvedPerPage.value)
})

const hasRows = computed(() => visibleRows.value.length > 0)
const showInitialLoading = computed(() => props.loading && !hasRows.value)
const overlayVisible = ref(false)
let overlayTimerId = null

function clearOverlayTimer() {
  if (overlayTimerId !== null) {
    clearTimeout(overlayTimerId)
    overlayTimerId = null
  }
}

function startOverlayTimer() {
  overlayTimerId = setTimeout(() => {
    overlayVisible.value = true
    overlayTimerId = null
  }, OVERLAY_DELAY_MS)
}

watch(
  () => props.loading && hasRows.value,
  (isLoading) => {
    clearOverlayTimer()

    if (isLoading) {
      startOverlayTimer()
      return
    }

    overlayVisible.value = false
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  clearOverlayTimer()
})

const visibleStart = computed(() => {
  if (!hasRows.value) {
    return 0
  }

  return (resolvedPage.value - 1) * resolvedPerPage.value + 1
})

const visibleEnd = computed(() => {
  if (!hasRows.value) {
    return 0
  }

  return visibleStart.value + visibleRows.value.length - 1
})

const summaryText = computed(() => {
  if (!isPaginationEnabled.value) {
    return `Total de ${resolvedTotalRows.value} registros`
  }

  if (!hasRows.value) {
    return `Mostrando 0 de ${resolvedTotalRows.value} registros`
  }

  return `Mostrando ${visibleStart.value}-${visibleEnd.value} de ${resolvedTotalRows.value} registros`
})

const pageItems = computed(() => {
  const pages = totalPages.value
  const currentPage = resolvedPage.value

  if (pages <= 7) {
    return Array.from({ length: pages }, (_, index) => ({
      type: 'page',
      key: `page-${index + 1}`,
      value: index + 1,
    }))
  }

  const items = [{ type: 'page', key: 'page-1', value: 1 }]
  const windowStart = Math.max(2, currentPage - 1)
  const windowEnd = Math.min(pages - 1, currentPage + 1)

  if (windowStart > 2) {
    items.push({ type: 'ellipsis', key: 'ellipsis-left' })
  }

  for (let pageNumber = windowStart; pageNumber <= windowEnd; pageNumber += 1) {
    items.push({ type: 'page', key: `page-${pageNumber}`, value: pageNumber })
  }

  if (windowEnd < pages - 1) {
    items.push({ type: 'ellipsis', key: 'ellipsis-right' })
  }

  items.push({ type: 'page', key: `page-${pages}`, value: pages })
  return items
})

const showFooter = computed(() => {
  if (!isPaginationEnabled.value) {
    return false
  }

  return props.showPerPageSelector || props.showPaginationSummary || totalPages.value > 1
})

const canGoPrev = computed(() => resolvedPage.value > 1)
const canGoNext = computed(() => resolvedPage.value < totalPages.value)

function emitPaginationPayload(nextPage = resolvedPage.value, nextPerPage = resolvedPerPage.value) {
  emit('pagination-change', {
    page: nextPage,
    perPage: nextPerPage,
    total: resolvedTotalRows.value,
    totalPages: totalPages.value,
    mode: props.paginationMode,
  })
}

function updatePage(nextPage) {
  const safePage = clamp(toPositiveInt(nextPage, resolvedPage.value), 1, totalPages.value)
  if (safePage === resolvedPage.value) {
    return
  }

  internalPage.value = safePage
  emit('update:page', safePage)
  emit('page-change', safePage)
  emitPaginationPayload(safePage, resolvedPerPage.value)
}

function updatePerPage(nextPerPage) {
  const safePerPage = toPositiveInt(nextPerPage, resolvedPerPage.value)
  if (safePerPage === resolvedPerPage.value) {
    return
  }

  const didResetPage = resolvedPage.value !== 1

  internalPerPage.value = safePerPage
  internalPage.value = 1

  emit('update:perPage', safePerPage)
  emit('per-page-change', safePerPage)

  if (didResetPage) {
    emit('update:page', 1)
    emit('page-change', 1)
  }

  emitPaginationPayload(1, safePerPage)
}

function handlePerPageChange(event) {
  updatePerPage(event.target.value)
}

function requestSort(column) {
  if (!isColumnSortable(column)) {
    return
  }

  const isSameColumn = resolvedSortKey.value === column.key
  const nextDirection = isSameColumn && resolvedSortDirection.value === 'asc' ? 'desc' : 'asc'

  internalSortKey.value = column.key
  internalSortDirection.value = nextDirection

  emit('update:sortKey', column.key)
  emit('update:sortDirection', nextDirection)
  emit('sort-change', {
    columnKey: column.key,
    field: column.sortKey || column.key,
    direction: nextDirection,
    mode: isServerSort.value ? 'server' : 'client',
  })

  if (isPaginationEnabled.value && resolvedPage.value !== 1) {
    updatePage(1)
  }
}

function getSortState(column) {
  if (resolvedSortKey.value !== column.key) {
    return 'none'
  }

  return resolvedSortDirection.value
}

function getColumnStyle(column) {
  const style = {}
  const width = toCssSize(column.width)
  const minWidth = toCssSize(column.minWidth)
  const maxWidth = toCssSize(column.maxWidth)

  if (width) {
    style.width = width
  }

  if (minWidth) {
    style.minWidth = minWidth
  }

  if (maxWidth) {
    style.maxWidth = maxWidth
  }

  return style
}

function getHeaderClass(column) {
  return [column.align ? `is-${column.align}` : '', resolveDynamicValue(column.headerClass, { column }, '')]
}

function getCellClass(row, column, rowIndex) {
  return [
    column.align ? `is-${column.align}` : '',
    resolveDynamicValue(column.cellClass, { row, column, rowIndex, value: getColumnRawValue(row, column) }, ''),
  ]
}

function getRowKey(row, rowIndex) {
  if (typeof props.rowKey === 'function') {
    const keyValue = props.rowKey(row, rowIndex)
    if (keyValue !== null && keyValue !== undefined && keyValue !== '') {
      return keyValue
    }
  }

  if (typeof props.rowKey === 'string' && props.rowKey) {
    const keyValue = getValueByPath(row, props.rowKey) ?? row?.[props.rowKey]
    if (keyValue !== null && keyValue !== undefined && keyValue !== '') {
      return keyValue
    }
  }

  const firstColumn = props.columns[0]
  const fallback = firstColumn ? getColumnRawValue(row, firstColumn) : 'row'
  return `${resolvedPage.value}-${rowIndex}-${fallback ?? 'row'}`
}

function resolveColumnActions(column) {
  if (Array.isArray(column.actions) && column.actions.length > 0) {
    return column.actions
  }

  if (column.key === 'actions' && props.actions.length > 0) {
    return props.actions
  }

  return []
}

function isActionVisible(action, row) {
  if (action.visible === undefined) {
    return true
  }

  return Boolean(resolveDynamicValue(action.visible, row, true))
}

function isActionDisabled(action, row) {
  return Boolean(resolveDynamicValue(action.disabled, row, false))
}

function isActionLoading(action, row) {
  return Boolean(resolveDynamicValue(action.loading, row, false))
}

function resolveActionLabel(action, row) {
  const label = resolveDynamicValue(action.label, row, '')
  return label === null || label === undefined ? '' : String(label)
}

function resolveActionTitle(action, row) {
  const title = resolveDynamicValue(action.title, row, '')
  return title === null || title === undefined ? '' : String(title)
}

function resolveActionAriaLabel(action, row) {
  const ariaLabel = resolveDynamicValue(action.ariaLabel, row, '')
  if (ariaLabel) {
    return String(ariaLabel)
  }

  return resolveActionLabel(action, row) || 'acao'
}

function resolveActionVariant(action) {
  return action.variant || 'ghost'
}

function resolveActionSize(action) {
  return action.size || 'sm'
}

function resolveActionIconOnly(action, row) {
  if (action.iconOnly !== undefined) {
    return Boolean(resolveDynamicValue(action.iconOnly, row, false))
  }

  return !resolveActionLabel(action, row)
}

function resolveActionIconPath(action, row) {
  return resolveDynamicValue(action.iconPath, row, '')
}

function resolveActionKey(action, rowIndex, actionIndex) {
  return action.key || action.name || `row-${rowIndex}-action-${actionIndex}`
}

function handleActionClick(action, row, rowIndex, column) {
  if (isActionDisabled(action, row) || isActionLoading(action, row)) {
    return
  }

  if (typeof action.onClick === 'function') {
    action.onClick({ action, row, rowIndex, column })
  }

  emit('action', {
    action,
    actionKey: action.key || action.name || action.label || 'action',
    row,
    rowIndex,
    column,
  })
}
</script>

<template>
  <div class="data-table-component">
    <div
      class="table-wrapper data-table-component__wrapper"
      :class="{ compact, 'no-scroll': disableScroll }"
      :style="wrapperStyle"
    >
      <LoadingState v-if="showInitialLoading">
        <slot name="loading">Carregando dados...</slot>
      </LoadingState>

      <template v-else-if="hasRows">
        <table
          class="data-table data-table-component__table"
          :class="{ 'wrap-cells': wrapCells, 'sticky-head': stickyHeader }"
          :style="tableStyle"
        >
          <thead>
            <tr>
              <th
                v-for="column in columns"
                :key="`head-${column.key}`"
                :class="getHeaderClass(column)"
                :style="getColumnStyle(column)"
              >
                <slot
                  :name="`header-${column.key}`"
                  :column="column"
                  :sort-state="getSortState(column)"
                  :toggle-sort="() => requestSort(column)"
                >
                  <button
                    v-if="isColumnSortable(column)"
                    type="button"
                    class="table-sort-button"
                    @click="requestSort(column)"
                  >
                    <span>{{ column.label }}</span>
                    <span class="table-sort-indicator" :data-state="getSortState(column)" aria-hidden="true">
                      <span class="caret caret-up">▲</span>
                      <span class="caret caret-down">▼</span>
                    </span>
                  </button>
                  <span v-else>{{ column.label }}</span>
                </slot>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, rowIndex) in visibleRows" :key="getRowKey(row, rowIndex)">
              <td
                v-for="(column, columnIndex) in columns"
                :key="`cell-${rowIndex}-${column.key}-${columnIndex}`"
                :class="getCellClass(row, column, rowIndex)"
                :style="getColumnStyle(column)"
              >
                <slot
                  :name="`cell-${column.key}`"
                  :row="row"
                  :row-index="rowIndex"
                  :column="column"
                  :value="getColumnRawValue(row, column)"
                >
                  <div v-if="resolveColumnActions(column).length > 0" class="inline-actions">
                    <BaseButton
                      v-for="(action, actionIndex) in resolveColumnActions(column).filter((item) => isActionVisible(item, row))"
                      :key="resolveActionKey(action, rowIndex, actionIndex)"
                      :size="resolveActionSize(action)"
                      :variant="resolveActionVariant(action)"
                      :icon-path="resolveActionIconPath(action, row)"
                      :icon-only="resolveActionIconOnly(action, row)"
                      :aria-label="resolveActionAriaLabel(action, row)"
                      :title="resolveActionTitle(action, row)"
                      :disabled="isActionDisabled(action, row)"
                      :loading="isActionLoading(action, row)"
                      @click="handleActionClick(action, row, rowIndex, column)"
                    >
                      {{ resolveActionLabel(action, row) }}
                    </BaseButton>
                  </div>
                  <span v-else>{{ formatValue(row, column) }}</span>
                </slot>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="overlayVisible" class="table-loading-overlay" role="status" aria-live="polite">
          <span class="spinner" aria-hidden="true" />
          <span>Atualizando...</span>
        </div>
      </template>

      <EmptyState v-else :title="emptyTitle" :text="emptyText">
        <slot name="empty" />
      </EmptyState>
    </div>

    <div v-if="showFooter" class="table-footer">
      <div v-if="showPaginationSummary" class="table-summary">
        <slot
          name="summary"
          :start="visibleStart"
          :end="visibleEnd"
          :total="resolvedTotalRows"
          :page="resolvedPage"
          :per-page="resolvedPerPage"
          :total-pages="totalPages"
        >
          {{ summaryText }}
        </slot>
      </div>

      <div class="table-footer-right">
        <label v-if="showPerPageSelector" class="table-per-page">
          <span>Linhas por pagina</span>
          <select
            class="table-select"
            :value="resolvedPerPage"
            :disabled="loading"
            @change="handlePerPageChange"
          >
            <option v-for="size in normalizedPerPageOptions" :key="size" :value="size">{{ size }}</option>
          </select>
        </label>

        <nav v-if="totalPages > 1" class="table-pagination" aria-label="Paginacao da tabela">
          <button
            type="button"
            class="table-page-btn"
            :disabled="!canGoPrev || loading"
            @click="updatePage(1)"
          >
            &laquo;
          </button>
          <button
            type="button"
            class="table-page-btn"
            :disabled="!canGoPrev || loading"
            @click="updatePage(resolvedPage - 1)"
          >
            &lsaquo;
          </button>

          <template v-for="item in pageItems" :key="item.key">
            <span v-if="item.type === 'ellipsis'" class="table-page-ellipsis">...</span>
            <button
              v-else
              type="button"
              class="table-page-btn"
              :class="{ 'is-active': item.value === resolvedPage }"
              :disabled="loading"
              @click="updatePage(item.value)"
            >
              {{ item.value }}
            </button>
          </template>

          <button
            type="button"
            class="table-page-btn"
            :disabled="!canGoNext || loading"
            @click="updatePage(resolvedPage + 1)"
          >
            &rsaquo;
          </button>
          <button
            type="button"
            class="table-page-btn"
            :disabled="!canGoNext || loading"
            @click="updatePage(totalPages)"
          >
            &raquo;
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<style scoped>
.data-table-component {
  display: grid;
  gap: 10px;
}

.data-table-component__wrapper {
  min-width: 0;
  position: relative;
}

.data-table-component__table.sticky-head thead th {
  position: sticky;
  top: 0;
  z-index: 4;
}

.table-sort-button {
  width: 100%;
  border: none;
  background: transparent;
  color: inherit;
  font: inherit;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  text-align: inherit;
  cursor: pointer;
  padding: 0;
}

.table-sort-button:hover {
  color: var(--text-main);
}

.table-sort-button:focus-visible,
.table-page-btn:focus-visible,
.table-select:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.table-sort-indicator {
  display: inline-grid;
  gap: 1px;
  line-height: 0.84;
  font-size: 0.74rem;
  color: var(--text-muted);
  opacity: 1;
  min-width: 14px;
  justify-items: center;
}

.table-sort-indicator .caret {
  opacity: 0.8;
  font-weight: 700;
}

.table-sort-indicator[data-state='asc'] .caret-up,
.table-sort-indicator[data-state='desc'] .caret-down {
  opacity: 1;
  color: var(--accent);
}

.table-sort-indicator[data-state='asc'] .caret-down,
.table-sort-indicator[data-state='desc'] .caret-up {
  opacity: 0.35;
}

.table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 8px 10px;
  background: var(--bg-soft);
}

.table-summary {
  color: var(--text-muted);
  font-size: 0.84rem;
}

.table-footer-right {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-left: auto;
}

.table-per-page {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
  font-size: 0.82rem;
}

.table-select {
  min-height: 32px;
  border-radius: 8px;
  border: 1px solid var(--border-strong);
  background: var(--form-input-bg);
  color: var(--form-input-fg);
  font: inherit;
  padding: 0 8px;
}

.table-pagination {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.table-page-btn {
  min-width: 32px;
  min-height: 32px;
  border-radius: 8px;
  border: 1px solid var(--button-secondary-border);
  background: var(--button-ghost-bg);
  color: var(--button-ghost-fg);
  cursor: pointer;
  font: inherit;
  padding: 0 8px;
  transition: 140ms ease;
}

.table-page-btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.table-page-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.table-page-btn.is-active {
  border-color: var(--accent);
  background: var(--accent-soft);
  color: var(--text-main);
}

.table-page-ellipsis {
  color: var(--text-muted);
  min-width: 20px;
  text-align: center;
}

.table-loading-overlay {
  position: absolute;
  inset: 0;
  background: var(--bg-overlay);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--text-muted);
  font-size: 0.84rem;
  backdrop-filter: blur(1.5px);
  animation: overlay-fade-in 120ms ease;
}

@keyframes overlay-fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@media (max-width: 920px) {
  .table-footer {
    align-items: stretch;
  }

  .table-footer-right {
    width: 100%;
    justify-content: space-between;
  }

  .table-pagination {
    max-width: 100%;
    overflow-x: auto;
  }
}
</style>
