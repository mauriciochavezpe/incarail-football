import { reactive, readonly } from 'vue'
import { api } from './api/http'

const STORAGE_KEY = 'football.session'

function load() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY)) ?? {}
  } catch {
    return {}
  }
}

function persist(state) {
  try {
    if (state.token) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ token: state.token, expiresAt: state.expiresAt }))
    } else {
      localStorage.removeItem(STORAGE_KEY)
    }
  } catch {
    /* almacenamiento no disponible: la sesión vive solo en memoria */
  }
}

const saved = load()

const state = reactive({
  token: saved.token ?? null,
  expiresAt: saved.expiresAt ?? null,
  user: null,
  // Reglas tal como las entrega la API: [{ action, subject }] (formato CASL).
  rules: [],
  // Motivo del último cierre de sesión forzado, para mostrarlo en el login.
  notice: null,
})

export const session = {
  state: readonly(state),

  get isAuthenticated() {
    return Boolean(state.token)
  },

  /** ¿El usuario puede hacer `action` sobre `subject`? Mismo criterio que el servidor. */
  can(action, subject) {
    return state.rules.some((r) => r.action === action && r.subject === subject)
  },

  async login(email, password) {
    const { data } = await api.post('/auth/login', { email, password }, { auth: false })
    state.token = data.access_token
    state.expiresAt = data.expires_at
    state.user = data.user
    state.rules = data.rules
    state.notice = null
    persist(state)
  },

  async logout() {
    try {
      if (state.token) await api.post('/auth/logout')
    } catch {
      /* si el token ya no sirve, igual cerramos la sesión local */
    }
    this.clear()
  },

  /** Recupera usuario y permisos actualizados (por si un admin cambió sus roles). */
  async restore() {
    if (!state.token) return
    try {
      const { data } = await api.get('/auth/me')
      state.user = data.user
      state.rules = data.rules
    } catch {
      /* http.js ya limpió la sesión si el token venció */
    }
  },

  clear(notice = null) {
    state.token = null
    state.expiresAt = null
    state.user = null
    state.rules = []
    state.notice = notice
    persist(state)
  },
}
