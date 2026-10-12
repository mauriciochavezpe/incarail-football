<script setup>
import { computed } from 'vue'
import { describeError } from '../api/errors'

const props = defineProps({ error: Object, type: { type: String, default: 'error' }, text: String })
const described = computed(() => (props.error ? describeError(props.error) : null))
</script>

<template>
  <div v-if="described || text" class="notice" :class="type === 'error' ? 'notice-error' : 'notice-ok'" role="alert">
    <template v-if="described"><strong>{{ described.title }}.</strong> {{ described.message }}</template>
    <template v-else>{{ text }}</template>
  </div>
</template>
