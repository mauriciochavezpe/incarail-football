<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api/http'
import { useAsync } from '../composables/useAsync'
import PageHeader from '../components/PageHeader.vue'
import AsyncState from '../components/AsyncState.vue'
import SourceNotice from '../components/SourceNotice.vue'
import CrestImage from '../components/CrestImage.vue'

const route = useRoute()
// GET /competitions/{id}: si la liga no estaba importada, la API la importa en esta misma llamada.
const { data, meta, error, loading, run } = useAsync((id) => api.get(`/competitions/${encodeURIComponent(id)}`))

watch(() => route.params.id, (id) => id && run(id), { immediate: true })

const season = computed(() => {
  const s = data.value?.current_season
  return s?.start ? `Temporada ${s.start.slice(0, 4)}/${s.end?.slice(0, 4)}` : ''
})
const justImported = computed(() => meta.value?.source === 'football-data')
</script>

<template>
  <PageHeader :title="data?.name ?? 'Liga'" :subtitle="data ? `${data.area.name} · ${season}` : ''">
    <template #back><RouterLink :to="{ name: 'competitions' }" class="back">← Ligas</RouterLink></template>
  </PageHeader>

  <div v-if="loading" class="notice notice-info" role="status">Consultando la liga… si es la primera vez, se importan sus equipos y jugadores desde football-data.</div>
  <SourceNotice :meta="meta" />
  <div v-if="justImported && !loading" class="notice notice-ok" role="status">Liga importada: {{ data.teams_count }} equipos guardados con sus planteles.</div>

  <AsyncState :loading="loading" :error="error" :empty="!data?.teams?.length" empty-title="Esta liga no tiene equipos registrados" @retry="run(route.params.id)">
    <div class="card-grid">
      <RouterLink v-for="t in data.teams" :key="t.id" :to="{ name: 'team', params: { id: t.id } }" class="card team-card">
        <CrestImage :src="t.crest" :alt="t.name" :size="48" />
        <div>
          <h3>{{ t.short_name || t.name }}</h3>
          <p class="muted small">{{ t.venue || t.area }} · {{ t.players_count }} jugadores</p>
        </div>
      </RouterLink>
    </div>
  </AsyncState>
</template>
