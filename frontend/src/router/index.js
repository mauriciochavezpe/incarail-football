import { createRouter, createWebHistory } from 'vue-router'
import { session } from '../session'

/**
 * meta.can = [action, subject] → la ruta solo se muestra si el usuario tiene
 * ese permiso. Es una ayuda de UX: el servidor vuelve a validar todo.
 */
const routes = [
  { path: '/login', name: 'login', component: () => import('../views/LoginView.vue'), meta: { guest: true } },
  {
    path: '/',
    component: () => import('../components/AppLayout.vue'),
    meta: { auth: true },
    children: [
      { path: '', redirect: { name: 'competitions' } },
      { path: 'competitions', name: 'competitions', component: () => import('../views/CompetitionsView.vue'), meta: { can: ['read', 'Competition'], title: 'Ligas' } },
      { path: 'competitions/:id', name: 'competition', component: () => import('../views/CompetitionDetailView.vue'), meta: { can: ['read', 'Competition'], title: 'Liga' } },
      { path: 'teams', name: 'teams', component: () => import('../views/TeamsView.vue'), meta: { can: ['read', 'Team'], title: 'Equipos' } },
      { path: 'teams/:id', name: 'team', component: () => import('../views/TeamDetailView.vue'), meta: { can: ['read', 'Team'], title: 'Equipo' } },
      { path: 'players', name: 'players', component: () => import('../views/PlayersView.vue'), meta: { can: ['read', 'Player'], title: 'Jugadores' } },
      { path: 'players/:id', name: 'player', component: () => import('../views/PlayerDetailView.vue'), meta: { can: ['read', 'Player'], title: 'Jugador' } },
      { path: 'users', name: 'users', component: () => import('../views/UsersView.vue'), meta: { can: ['read', 'User'], title: 'Usuarios' } },
      { path: 'roles', name: 'roles', component: () => import('../views/RolesView.vue'), meta: { can: ['read', 'Role'], title: 'Roles y permisos' } },
      { path: 'forbidden', name: 'forbidden', component: () => import('../views/ForbiddenView.vue'), meta: { title: 'Sin permiso' } },
      { path: ':pathMatch(.*)*', name: 'not-found', component: () => import('../views/NotFoundView.vue'), meta: { title: 'No encontrado' } },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  if (to.matched.some((r) => r.meta.auth) && !session.isAuthenticated) {
    return { name: 'login', query: to.fullPath !== '/' ? { redirect: to.fullPath } : {} }
  }
  if (to.meta.guest && session.isAuthenticated) {
    return { name: 'competitions' }
  }
  if (to.meta.can && !session.can(...to.meta.can)) {
    return { name: 'forbidden', query: { from: to.fullPath } }
  }
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · Fútbol` : 'Fútbol · Plataforma interna'
})

export default router
