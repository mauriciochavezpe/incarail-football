<script setup>
/** Avisa cuando la respuesta viene de la copia local porque football-data no respondió. */
defineProps({ meta: Object })

function formatDate(iso) {
  return iso ? new Date(iso).toLocaleString('es-PE', { dateStyle: 'medium', timeStyle: 'short' }) : '—'
}
</script>

<template>
  <div v-if="meta?.stale" class="notice notice-warn" role="status">
    <strong>Mostrando datos guardados.</strong>
    {{ meta.warning }} Última actualización: {{ formatDate(meta.synced_at) }}.
  </div>
</template>
