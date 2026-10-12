<script setup>
import { computed } from 'vue'
import { describeError } from '../api/errors'

/**
 * Resuelve en un solo lugar los tres estados de cualquier pantalla:
 * cargando, error (con reintento) y vacío. Si hay datos, muestra el slot.
 */
const props = defineProps({
  loading: Boolean,
  error: Object,
  empty: Boolean,
  emptyTitle: { type: String, default: 'No hay nada para mostrar' },
  emptyText: String,
})
defineEmits(['retry'])

const described = computed(() => describeError(props.error))
</script>

<template>
  <div v-if="loading" class="state" role="status" aria-live="polite">
    <span class="spinner" aria-hidden="true" />
    <p>Cargando…</p>
  </div>
  <div v-else-if="error" class="state state-error" role="alert">
    <p class="state-title">{{ described.title }}</p>
    <p>{{ described.message }}</p>
    <button class="btn" @click="$emit('retry')">Reintentar</button>
  </div>
  <div v-else-if="empty" class="state">
    <p class="state-title">{{ emptyTitle }}</p>
    <p v-if="emptyText" class="muted">{{ emptyText }}</p>
    <slot name="empty" />
  </div>
  <slot v-else />
</template>
