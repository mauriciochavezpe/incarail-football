import { ref, shallowRef } from 'vue'

/**
 * Estado estándar de una carga: loading / error / data.
 * Todas las pantallas lo usan para resolver igual los estados de carga y error.
 */
export function useAsync(fn) {
  const data = shallowRef(null)
  const meta = shallowRef(null)
  const error = shallowRef(null)
  const loading = ref(false)

  async function run(...args) {
    loading.value = true
    error.value = null
    try {
      const result = await fn(...args)
      data.value = result?.data ?? result
      meta.value = result?.meta ?? null
      return result
    } catch (e) {
      error.value = e
      return null
    } finally {
      loading.value = false
    }
  }

  return { data, meta, error, loading, run }
}
