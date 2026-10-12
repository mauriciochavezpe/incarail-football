<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api/http'
import { useAsync } from '../composables/useAsync'
import { useDebounced } from '../composables/useDebounce'
import PageHeader from '../components/PageHeader.vue'
import AsyncState from '../components/AsyncState.vue'
import PaginationBar from '../components/PaginationBar.vue'
import CrestImage from '../components/CrestImage.vue'

const route = useRoute()
const router = useRouter()

const search = ref(route.query.search ?? '')
const competitionId = ref(route.query.competition_id ?? '')
const page = ref(Number(route.query.page ?? 1))
const debouncedSearch = useDebounced(search)

const competitions = ref([])
const { data, meta, error, loading, run } = useAsync(() =>
  api.get('/team', { search: debouncedSearch.value, competition_id: competitionId.value, page: page.value, per_page: 24 }),
)

function load() {
  router.replace({ query: { ...(debouncedSearch.value && { search: debouncedSearch.value }), ...(competitionId.value && { competition_id: competitionId.value }), ...(page.value > 1 && { page: page.value }) } })
  run()
}

watch([debouncedSearch, competitionId], () => {
  // Al cambiar un filtro se vuelve a la página 1 (ese cambio ya dispara la carga).
  if (page.value !== 1) page.value = 1
  else load()
})
watch(page, load)

onMounted(async () => {
  load()
  // Para el filtro: solo las ligas ya importadas (sin costo externo si el catálogo está vigente).
  try {
    const res = await api.get('/competitions')
    competitions.value = res.data.filter((c) => c.imported)
  } catch {
    competitions.value = []
  }
})
</script>

<template>
  <PageHeader title="Equipos" subtitle="Equipos guardados a partir de las ligas importadas.">
    <div class="filters">
      <input v-model="search" type="search" class="search" placeholder="Buscar equipo…" aria-label="Buscar equipo" />
      <select v-model="competitionId" aria-label="Filtrar por liga">
        <option value="">Todas las ligas</option>
        <option v-for="c in competitions" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
      </select>
    </div>
  </PageHeader>

  <AsyncState
    :loading="loading" :error="error" :empty="!data?.length"
    empty-title="No hay equipos guardados"
    :empty-text="search || competitionId ? 'Prueba con otro filtro.' : 'Importa una liga desde la sección Ligas para ver sus equipos aquí.'"
    @retry="run"
  >
    <div class="card-grid">
      <RouterLink v-for="t in data" :key="t.id" :to="{ name: 'team', params: { id: t.id } }" class="card team-card">
        <CrestImage :src="t.crest" :alt="t.name" :size="48" />
        <div>
          <h3>{{ t.name }}</h3>
          <p class="muted small">{{ t.competitions?.map((c) => c.code || c.name).join(' · ') }} · {{ t.players_count }} jugadores</p>
        </div>
      </RouterLink>
    </div>
    <PaginationBar :meta="meta" @change="page = $event" />
  </AsyncState>
</template>
