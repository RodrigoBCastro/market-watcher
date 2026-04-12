<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, required: true },
  description: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

function onChange(event) {
  emit('update:modelValue', event.target.checked)
}
</script>

<template>
  <label class="form-switch" :class="{ 'is-disabled': disabled }">
    <input
      class="form-switch-input"
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      @change="onChange"
    />

    <span class="form-switch-track" aria-hidden="true">
      <span class="form-switch-thumb" />
    </span>

    <span class="form-switch-text">
      <strong>{{ label }}</strong>
      <small v-if="description">{{ description }}</small>
    </span>
  </label>
</template>
