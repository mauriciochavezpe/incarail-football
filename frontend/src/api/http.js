import { session } from '../session'
import router from '../router'

const BASE_URL = (import.meta.env.VITE_API_URL ?? 'http://localhost:8000').replace(/\/$/, '')

/**
 * Error normalizado a partir del formato único de la API:
 * { error: { status, code, message, errors?, retry_after? } }
 */
export class ApiError extends Error {
  constructor({ status, code, message, errors = null, retryAfter = null }) {
    super(message)
    this.status = status
    this.code = code
    this.errors = errors
    this.retryAfter = retryAfter
  }
}

async function request(method, path, { body, query, auth = true } = {}) {
  const url = new URL(BASE_URL + path)
  Object.entries(query ?? {}).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v)
  })

  const headers = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (auth && session.state.token) headers.Authorization = `Bearer ${session.state.token}`

  let response
  try {
    response = await fetch(url, { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined })
  } catch {
    throw new ApiError({ status: 0, code: 'network_error', message: 'No se pudo conectar con el servidor. Revisa tu conexión.' })
  }

  if (response.status === 204) return null

  const payload = await response.json().catch(() => null)

  if (response.ok) return payload

  const err = payload?.error ?? {}
  const error = new ApiError({
    status: response.status,
    code: err.code ?? 'http_error',
    message: err.message ?? `Error inesperado (${response.status}).`,
    errors: err.errors ?? null,
    retryAfter: err.retry_after ?? (Number(response.headers.get('Retry-After')) || null),
  })

  // Sesión vencida o usuario desactivado: se cierra la sesión y se vuelve al login con un mensaje claro.
  if (auth && (error.status === 401 || error.code === 'account_disabled')) {
    session.clear(error.code === 'account_disabled' ? error.message : 'Tu sesión venció. Vuelve a iniciar sesión.')
    if (router.currentRoute.value.name !== 'login') {
      router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
    }
  }

  throw error
}

export const api = {
  get: (path, query, opts) => request('GET', path, { ...opts, query }),
  post: (path, body, opts) => request('POST', path, { ...opts, body }),
  put: (path, body, opts) => request('PUT', path, { ...opts, body }),
  patch: (path, body, opts) => request('PATCH', path, { ...opts, body }),
  delete: (path, opts) => request('DELETE', path, opts),
}
