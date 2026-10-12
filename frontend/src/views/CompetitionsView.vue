<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../api/http'
import { session } from '../session'
import { useAsync } from '../composables/useAsync'
import PageHeader from '../components/PageHeader.vue'
import AsyncState from '../components/AsyncState.vue'
import SourceNotice from '../components/SourceNotice.vue'
import CrestImage from '../components/CrestImage.vue'

const { data, meta, error, loading, run } = useAsync(() => api.get('/competitions'))
const search = ref('')
const canImport = computed(() => session.can('import', 'Competition'))

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  return (data.value ?? []).filter((c) => !q || [c.name, c.code, c.area.name].some((v) => v?.toLowerCase().includes(q)))
})

onMounted(run)
</script>

<template>
  <PageHeader title="Ligas" subtitle="Competiciones disponibles en football-data.org. Importa una liga para guardar sus equipos y jugadores.">
    <input v-model="search" type="search" class="search" placeholder="Buscar liga o país…" aria-label="Buscar liga" />
  </PageHeader>

  <SourceNotice :meta="meta" />

  <AsyncState :loading="loading" :error="error" :empty="!filtered.length" empty-title="No hay ligas que coincidan" @retry="run">
    <div class="card-grid">
      <article v-for="c in filtered" :key="c.id" class="card competition-card">
        <div class="card-head">
          <CrestImage :src="c.emblem" :alt="c.name" :size="44" />
          <div>
            <h3>{{ c.name }}</h3>
            <p class="muted small">{{ c.area.name }} · {{ c.type === 'CUP' ? 'Copa' : 'Liga' }} <span v-if="c.code">· {{ c.code }}</span></p>
          </div>
        </div>
        <p class="small">
          <span v-if="c.imported" class="badge badge-ok">Importada · {{ c.teams_count }} equipos</span>
          <span v-else class="badge">Sin importar</span>
        </p>
        <RouterLink v-if="c.imported" :to="{ name: 'competition', params: { id: c.code || c.id } }" class="btn">Ver equipos</RouterLink>
        <RouterLink v-else-if="canImport" :to="{ name: 'competition', params: { id: c.code || c.id } }" class="btn btn-primary">Importar liga</RouterLink>
        <button v-else class="btn" disabled title="Tu rol solo puede ver ligas ya importadas">Aún no importada</button>
      </article>
    </div>
  </AsyncState>
</template>
