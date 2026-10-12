<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api/http'
import { useAsync } from '../composables/useAsync'
import { useDebounced } from '../composables/useDebounce'
import { positionLabel } from '../positions'
import PageHeader from '../components/PageHeader.vue'
import AsyncState from '../components/AsyncState.vue'
import PaginationBar from '../components/PaginationBar.vue'
import CrestImage from '../components/CrestImage.vue'

const route = useRoute()
const router = useRouter()

const search = ref(route.query.search ?? '')
const page = ref(Number(route.query.page ?? 1))
const debouncedSearch = useDebounced(search)

const { data, meta, error, loading, run } = useAsync(() => api.get('/players', { search: debouncedSearch.value, page: page.value, per_page: 25 }))

function load() {
  router.replace({ query: { ...(debouncedSearch.value && { search: debouncedSearch.value }), ...(page.value > 1 && { page: page.value }) } })
  run()
}

watch(debouncedSearch, () => {
  if (page.value !== 1) page.value = 1
  else load()
})
watch(page, load)
onMounted(load)
</script>

<template>
  <PageHeader title="Jugadores" subtitle="Jugadores guardados a partir de las ligas importadas.">
    <input v-model="search" type="search" class="search" placeholder="Buscar jugador…" aria-label="Buscar jugador" />
  </PageHeader>

  <AsyncState
    :loading="loading" :error="error" :empty="!data?.length"
    empty-title="No hay jugadores guardados"
    :empty-text="search ? 'Prueba con otro nombre.' : 'Importa una liga desde la sección Ligas para ver sus jugadores aquí.'"
    @retry="run"
  >
    <div class="table-wrap">
      <table class="table table-responsive">
        <thead>
          <tr><th class="num">N.º</th><th>Nombre</th><th>Posición</th><th>Equipo</th><th>Nacionalidad</th></tr>
        </thead>
        <tbody>
          <tr v-for="p in data" :key="p.id">
            <td class="num" data-label="N.º">{{ p.shirt_number ?? '—' }}</td>
            <td data-label="Nombre"><RouterLink :to="{ name: 'player', params: { id: p.id } }"><strong>{{ p.name }}</strong></RouterLink></td>
            <td data-label="Posición">{{ positionLabel(p.position) }}</td>
            <td data-label="Equipo">
              <RouterLink v-if="p.team" :to="{ name: 'team', params: { id: p.team.id } }" class="inline-team">
                <CrestImage :src="p.team.crest" :alt="p.team.name" :size="20" /> {{ p.team.name }}
              </RouterLink>
              <span v-else class="muted">Sin equipo</span>
            </td>
            <td data-label="Nacionalidad">{{ p.nationality || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar :meta="meta" @change="page = $event" />
  </AsyncState>
</template>
