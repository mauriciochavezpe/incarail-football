<script setup>
defineProps({ title: String, subtitle: String })
</script>

<template>
  <div class="page-header">
    <div>
      <slot name="back" />
      <h1>{{ title }}</h1>
      <p v-if="subtitle" class="muted">{{ subtitle }}</p>
    </div>
    <div class="page-actions"><slot /></div>
  </div>
</template>
