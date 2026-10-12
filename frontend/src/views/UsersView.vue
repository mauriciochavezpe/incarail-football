<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { api } from '../api/http'
import { session } from '../session'
import { useAsync } from '../composables/useAsync'
import { useDebounced } from '../composables/useDebounce'
import PageHeader from '../components/PageHeader.vue'
import AsyncState from '../components/AsyncState.vue'
import PaginationBar from '../components/PaginationBar.vue'
import BaseModal from '../components/BaseModal.vue'
import AppAlert from '../components/AppAlert.vue'
import FieldError from '../components/FieldError.vue'

const search = ref('')
const page = ref(1)
const debouncedSearch = useDebounced(search)
const { data, meta, error, loading, run } = useAsync(() => api.get('/users', { search: debouncedSearch.value, page: page.value }))

const canCreate = computed(() => session.can('create', 'User'))
const canUpdate = computed(() => session.can('update', 'User'))

const roles = ref([])
const feedback = ref(null)
const actionError = ref(null)

// --- Formulario (crear / editar) ---
const editing = ref(null) // null = cerrado, {} = nuevo, user = edición
const form = reactive({ name: '', email: '', password: '', roles: [], is_active: true })
const formError = ref(null)
const saving = ref(false)

function openForm(user = {}) {
  editing.value = user
  formError.value = null
  Object.assign(form, {
    name: user.name ?? '',
    email: user.email ?? '',
    password: '',
    roles: user.roles?.map((r) => r.id) ?? [],
    is_active: user.is_active ?? true,
  })
}

async function save() {
  saving.value = true
  formError.value = null
  try {
    const payload = { ...form }
    if (editing.value.id && !payload.password) delete payload.password
    if (editing.value.id) {
      await api.put(`/users/${editing.value.id}`, payload)
      feedback.value = `Usuario ${form.name} actualizado.`
    } else {
      await api.post('/users', payload)
      feedback.value = `Usuario ${form.name} creado.`
    }
    // Si me edité a mí mismo, refresco mis permisos.
    if (editing.value.id === session.state.user?.id) await session.restore()
    editing.value = null
    run()
  } catch (e) {
    formError.value = e
  } finally {
    saving.value = false
  }
}

async function toggleActive(user) {
  actionError.value = null
  feedback.value = null
  try {
    await api.patch(`/users/${user.id}/${user.is_active ? 'deactivate' : 'activate'}`)
    feedback.value = `${user.name} ${user.is_active ? 'fue desactivado y su sesión se cerró' : 'fue activado'}.`
    run()
  } catch (e) {
    actionError.value = e
  }
}

watch(debouncedSearch, () => {
  if (page.value !== 1) page.value = 1
  else run()
})
watch(page, run)

onMounted(async () => {
  run()
  if (session.can('read', 'Role')) {
    try {
      roles.value = (await api.get('/roles')).data
    } catch {
      roles.value = []
    }
  }
})

const lastLogin = (iso) => (iso ? new Date(iso).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' }) : 'Nunca')
</script>

<template>
  <PageHeader title="Usuarios" subtitle="Crea usuarios, asígnales roles y desactívalos cuando ya no deban entrar.">
    <input v-model="search" type="search" class="search" placeholder="Buscar por nombre o correo…" aria-label="Buscar usuario" />
    <button v-if="canCreate" class="btn btn-primary" @click="openForm()">+ Nuevo usuario</button>
  </PageHeader>

  <AppAlert v-if="feedback" type="ok" :text="feedback" />
  <AppAlert :error="actionError" />

  <AsyncState :loading="loading" :error="error" :empty="!data?.length" empty-title="No hay usuarios que coincidan" @retry="run">
    <div class="table-wrap">
      <table class="table table-responsive">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Roles</th><th>Estado</th><th>Último ingreso</th><th v-if="canUpdate"></th></tr></thead>
        <tbody>
          <tr v-for="u in data" :key="u.id">
            <td data-label="Nombre"><strong>{{ u.name }}</strong></td>
            <td data-label="Correo">{{ u.email }}</td>
            <td data-label="Roles"><span v-for="r in u.roles" :key="r.id" class="chip">{{ r.label }}</span></td>
            <td data-label="Estado">
              <span class="badge" :class="u.is_active ? 'badge-ok' : 'badge-off'">{{ u.is_active ? 'Activo' : 'Desactivado' }}</span>
            </td>
            <td data-label="Último ingreso">{{ lastLogin(u.last_login_at) }}</td>
            <td v-if="canUpdate" class="row-actions">
              <button class="btn btn-ghost" @click="openForm(u)">Editar</button>
              <button v-if="u.id !== session.state.user?.id" class="btn btn-ghost" :class="{ danger: u.is_active }" @click="toggleActive(u)">
                {{ u.is_active ? 'Desactivar' : 'Activar' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar :meta="meta" @change="page = $event" />
  </AsyncState>

  <BaseModal v-if="editing" :title="editing.id ? 'Editar usuario' : 'Nuevo usuario'" @close="editing = null">
    <form class="form" @submit.prevent="save">
      <AppAlert v-if="formError && !formError.errors" :error="formError" />
      <label class="field"><span>Nombre</span><input v-model.trim="form.name" required /><FieldError :error="formError" field="name" /></label>
      <label class="field"><span>Correo</span><input v-model.trim="form.email" type="email" required /><FieldError :error="formError" field="email" /></label>
      <label class="field">
        <span>Contraseña <small v-if="editing.id" class="muted">(déjala vacía para no cambiarla)</small></span>
        <input v-model="form.password" type="password" autocomplete="new-password" :required="!editing.id" />
        <small class="muted">Mínimo 8 caracteres, con letras y números.</small>
        <FieldError :error="formError" field="password" />
      </label>
      <fieldset class="field">
        <legend>Roles</legend>
        <label v-for="r in roles" :key="r.id" class="check">
          <input v-model="form.roles" type="checkbox" :value="r.id" />
          <span><strong>{{ r.label }}</strong> <small class="muted">{{ r.description }}</small></span>
        </label>
        <FieldError :error="formError" field="roles" />
      </fieldset>
      <label v-if="editing.id !== session.state.user?.id" class="check">
        <input v-model="form.is_active" type="checkbox" /> <span>Usuario activo</span>
      </label>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" @click="editing = null">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving">{{ saving ? 'Guardando…' : 'Guardar' }}</button>
      </div>
    </form>
  </BaseModal>
</template>
