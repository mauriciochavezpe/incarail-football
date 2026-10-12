<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { session } from '../session'

const route = useRoute()
const router = useRouter()
const menuOpen = ref(false)

const links = [
  { to: { name: 'competitions' }, label: 'Ligas', can: ['read', 'Competition'], icon: '🏆' },
  { to: { name: 'teams' }, label: 'Equipos', can: ['read', 'Team'], icon: '🛡️' },
  { to: { name: 'players' }, label: 'Jugadores', can: ['read', 'Player'], icon: '👟' },
  { to: { name: 'users' }, label: 'Usuarios', can: ['read', 'User'], icon: '👤', admin: true },
  { to: { name: 'roles' }, label: 'Roles y permisos', can: ['read', 'Role'], icon: '🔐', admin: true },
]

// El menú solo muestra lo que el usuario puede usar.
const visible = computed(() => links.filter((l) => session.can(...l.can)))
const roleLabel = computed(() => session.state.user?.roles?.map((r) => r.label).join(', '))

watch(() => route.fullPath, () => (menuOpen.value = false))

async function logout() {
  await session.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="shell">
    <header class="topbar">
      <button class="icon-btn menu-toggle" :aria-expanded="menuOpen" aria-label="Abrir menú" @click="menuOpen = !menuOpen">☰</button>
      <RouterLink to="/" class="brand">⚽ <span>Fútbol</span></RouterLink>
      <div class="topbar-user">
        <div class="who">
          <strong>{{ session.state.user?.name }}</strong>
          <small>{{ roleLabel }}</small>
        </div>
        <button class="btn btn-ghost" @click="logout">Salir</button>
      </div>
    </header>

    <nav class="sidebar" :class="{ open: menuOpen }" aria-label="Navegación principal">
      <template v-for="(link, i) in visible" :key="link.label">
        <div v-if="link.admin && !visible[i - 1]?.admin" class="nav-section">Administración</div>
        <RouterLink :to="link.to" class="nav-link"><span aria-hidden="true">{{ link.icon }}</span>{{ link.label }}</RouterLink>
      </template>
    </nav>
    <div v-if="menuOpen" class="backdrop" @click="menuOpen = false" />

    <main class="content">
      <RouterView />
    </main>
  </div>
</template>
