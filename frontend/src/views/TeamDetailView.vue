<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api/http'
import { useAsync } from '../composables/useAsync'
import { positionLabel, positionOrder } from '../positions'
import PageHeader from '../components/PageHeader.vue'
import AsyncState from '../components/AsyncState.vue'
import CrestImage from '../components/CrestImage.vue'

const route = useRoute()
const { data, error, loading, run } = useAsync((id) => api.get(`/team/${id}`))
watch(() => route.params.id, (id) => id && run(id), { immediate: true })

// Plantel agrupado por posición.
const groups = computed(() => {
  const map = new Map()
  for (const p of data.value?.players ?? []) {
    const key = positionLabel(p.position)
    if (!map.has(key)) map.set(key, [])
    map.get(key).push(p)
  }
  return [...map.entries()].sort(([a], [b]) => positionOrder(a) - positionOrder(b))
})

function age(dob) {
  if (!dob) return '—'
  const d = new Date(dob)
  const diff = Date.now() - d.getTime()
  return Math.floor(diff / (365.25 * 24 * 3600 * 1000))
}
</script>

<template>
  <PageHeader :title="data?.name ?? 'Equipo'">
    <template #back><RouterLink :to="{ name: 'teams' }" class="back">← Equipos</RouterLink></template>
  </PageHeader>

  <AsyncState :loading="loading" :error="error" @retry="run(route.params.id)">
    <section v-if="data" class="card team-hero">
      <CrestImage :src="data.crest" :alt="data.name" :size="88" />
      <dl class="facts">
        <div><dt>Estadio</dt><dd>{{ data.venue || '—' }}</dd></div>
        <div><dt>Fundación</dt><dd>{{ data.founded || '—' }}</dd></div>
        <div><dt>Colores</dt><dd>{{ data.club_colors || '—' }}</dd></div>
        <div><dt>Entrenador</dt><dd>{{ data.coach || '—' }}</dd></div>
        <div><dt>País</dt><dd>{{ data.area || '—' }}</dd></div>
        <div><dt>Ligas</dt><dd>
          <RouterLink v-for="c in data.competitions" :key="c.id" :to="{ name: 'competition', params: { id: c.code || c.id } }" class="chip">{{ c.name }}</RouterLink>
        </dd></div>
        <div v-if="data.website"><dt>Sitio web</dt><dd><a :href="data.website" target="_blank" rel="noopener">{{ data.website.replace(/^https?:\/\//, '') }}</a></dd></div>
      </dl>
    </section>

    <h2 class="section-title">Plantel <small class="muted">({{ data?.players_count ?? 0 }})</small></h2>
    <p v-if="data && !data.players.length" class="muted">Este equipo no tiene jugadores registrados.</p>
    <div v-for="[position, players] in groups" :key="position" class="squad-group">
      <h3>{{ position }}</h3>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th class="num">N.º</th><th>Nombre</th><th>Nacionalidad</th><th class="num">Edad</th></tr></thead>
          <tbody>
            <tr v-for="p in players" :key="p.id">
              <td class="num">{{ p.shirt_number ?? '—' }}</td>
              <td><RouterLink :to="{ name: 'player', params: { id: p.id } }">{{ p.name }}</RouterLink></td>
              <td>{{ p.nationality || '—' }}</td>
              <td class="num">{{ age(p.date_of_birth) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AsyncState>
</template>
