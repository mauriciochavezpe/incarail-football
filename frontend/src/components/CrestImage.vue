<script setup>
import { ref } from 'vue'

/** Escudo o emblema con un respaldo si la imagen externa falla. */
defineProps({ src: String, alt: String, size: { type: Number, default: 40 } })
const failed = ref(false)
</script>

<template>
  <img v-if="src && !failed" :src="src" :alt="alt" :width="size" :height="size" class="crest" loading="lazy" @error="failed = true" />
  <span v-else class="crest crest-fallback" :style="{ width: size + 'px', height: size + 'px' }" aria-hidden="true">{{ (alt || '?').slice(0, 2).toUpperCase() }}</span>
</template>
