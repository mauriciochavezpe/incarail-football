<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../api/http'
import { useAsync } from '../composables/useAsync'
import { positionLabel } from '../positions'
import PageHeader from '../components/PageHeader.vue'
import AsyncState from '../components/AsyncState.vue'
import CrestImage from '../components/CrestImage.vue'

const route = useRoute()
const { data, error, loading, run } = useAsync((id) => api.get(`/players/${id}`))
watch(() => route.params.id, (id) => id && run(id), { immediate: true })

const age = computed(() => {
  const dob = data.value?.date_of_birth
  return dob ? Math.floor((Date.now() - new Date(dob).getTime()) / (365.25 * 24 * 3600 * 1000)) : null
})
const birth = computed(() =>
  data.value?.date_of_birth ? new Date(`${data.value.date_of_birth}T00:00:00`).toLocaleDateString('es-PE', { dateStyle: 'long' }) : '—',
)
</script>

<template>
  <PageHeader :title="data?.name ?? 'Jugador'" :subtitle="data ? positionLabel(data.position) : ''">
    <template #back><RouterLink :to="{ name: 'players' }" class="back">← Jugadores</RouterLink></template>
  </PageHeader>

  <AsyncState :loading="loading" :error="error" @retry="run(route.params.id)">
    <section v-if="data" class="card player-hero">
      <div class="shirt" :title="data.shirt_number ? 'Número de camiseta' : 'football-data no informa el número'">
        {{ data.shirt_number ?? '—' }}
      </div>
      <dl class="facts">
        <div><dt>Posición</dt><dd>{{ positionLabel(data.position) }}</dd></div>
        <div><dt>Número de camiseta</dt><dd>{{ data.shirt_number ?? 'No informado' }}</dd></div>
        <div><dt>Nacionalidad</dt><dd>{{ data.nationality || '—' }}</dd></div>
        <div><dt>Fecha de nacimiento</dt><dd>{{ birth }}<span v-if="age !== null" class="muted"> ({{ age }} años)</span></dd></div>
        <div>
          <dt>Equipo</dt>
          <dd>
            <RouterLink v-if="data.team" :to="{ name: 'team', params: { id: data.team.id } }" class="inline-team">
              <CrestImage :src="data.team.crest" :alt="data.team.name" :size="20" /> {{ data.team.name }}
            </RouterLink>
            <span v-else class="muted">Sin equipo actual</span>
          </dd>
        </div>
        <div v-if="data.team?.competitions?.length">
          <dt>Ligas</dt>
          <dd>
            <RouterLink v-for="c in data.team.competitions" :key="c.id" :to="{ name: 'competition', params: { id: c.code || c.id } }" class="chip">{{ c.name }}</RouterLink>
          </dd>
        </div>
      </dl>
    </section>
  </AsyncState>
</template>
