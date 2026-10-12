<script setup>
/** Muestra el primer error de validación (422) de un campo. */
defineProps({ error: Object, field: String })
</script>

<template>
  <small v-if="error?.errors?.[field]" class="field-error">{{ error.errors[field][0] }}</small>
</template>
