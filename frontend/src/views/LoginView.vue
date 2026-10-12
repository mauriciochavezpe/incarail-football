<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { session } from '../session'
import AppAlert from '../components/AppAlert.vue'
import FieldError from '../components/FieldError.vue'

const route = useRoute()
const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref(null)

async function submit() {
  loading.value = true
  error.value = null
  try {
    await session.login(email.value, password.value)
    const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/') ? route.query.redirect : '/'
    router.replace(redirect)
  } catch (e) {
    error.value = e
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <form class="login-card" novalidate @submit.prevent="submit">
      <div class="login-brand">⚽</div>
      <h1>Plataforma de fútbol</h1>
      <p class="muted">Ingresa con tu cuenta corporativa.</p>

      <div v-if="session.state.notice" class="notice notice-warn" role="status">{{ session.state.notice }}</div>
      <AppAlert v-if="error && !error.errors" :error="error" />

      <label class="field">
        <span>Correo</span>
        <input v-model.trim="email" type="email" autocomplete="username" required autofocus />
        <FieldError :error="error" field="email" />
      </label>
      <label class="field">
        <span>Contraseña</span>
        <input v-model="password" type="password" autocomplete="current-password" required />
        <FieldError :error="error" field="password" />
      </label>

      <button class="btn btn-primary btn-block" :disabled="loading || !email || !password">
        {{ loading ? 'Ingresando…' : 'Ingresar' }}
      </button>
    </form>
  </div>
</template>
