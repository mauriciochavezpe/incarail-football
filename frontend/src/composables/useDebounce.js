import { ref, watch } from 'vue'

/** Devuelve una copia de `source` que se actualiza `delay` ms después del último cambio. */
export function useDebounced(source, delay = 350) {
  const debounced = ref(source.value)
  let timer
  watch(source, (value) => {
    clearTimeout(timer)
    timer = setTimeout(() => (debounced.value = value), delay)
  })
  return debounced
}
