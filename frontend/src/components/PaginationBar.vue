<script setup>
/** Paginación a partir del "meta" estándar de los Resources de Laravel. */
defineProps({ meta: Object })
defineEmits(['change'])
</script>

<template>
  <nav v-if="meta && meta.last_page > 1" class="pagination" aria-label="Paginación">
    <button class="btn btn-ghost" :disabled="meta.current_page <= 1" @click="$emit('change', meta.current_page - 1)">← Anterior</button>
    <span class="muted">Página {{ meta.current_page }} de {{ meta.last_page }} · {{ meta.total }} resultados</span>
    <button class="btn btn-ghost" :disabled="meta.current_page >= meta.last_page" @click="$emit('change', meta.current_page + 1)">Siguiente →</button>
  </nav>
  <p v-else-if="meta" class="muted small">{{ meta.total }} resultado{{ meta.total === 1 ? '' : 's' }}</p>
</template>
