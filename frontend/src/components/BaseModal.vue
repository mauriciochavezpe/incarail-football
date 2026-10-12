<script setup>
import { onMounted, onUnmounted } from 'vue'

defineProps({ title: String })
const emit = defineEmits(['close'])

const onKey = (e) => e.key === 'Escape' && emit('close')
onMounted(() => document.addEventListener('keydown', onKey))
onUnmounted(() => document.removeEventListener('keydown', onKey))
</script>

<template>
  <Teleport to="body">
    <div class="modal-backdrop" @click.self="emit('close')">
      <div class="modal" role="dialog" aria-modal="true" :aria-label="title">
        <header class="modal-header">
          <h2>{{ title }}</h2>
          <button class="icon-btn" aria-label="Cerrar" @click="emit('close')">✕</button>
        </header>
        <slot />
      </div>
    </div>
  </Teleport>
</template>
