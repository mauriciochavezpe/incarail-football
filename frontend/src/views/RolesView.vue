<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { api } from '../api/http'
import { session } from '../session'
import { useAsync } from '../composables/useAsync'
import PageHeader from '../components/PageHeader.vue'
import AsyncState from '../components/AsyncState.vue'
import BaseModal from '../components/BaseModal.vue'
import AppAlert from '../components/AppAlert.vue'
import FieldError from '../components/FieldError.vue'

const SUBJECTS = { Competition: 'Ligas', Team: 'Equipos', Player: 'Jugadores', User: 'Usuarios', Role: 'Roles' }

const { data, error, loading, run } = useAsync(() => api.get('/roles'))
const permissions = ref([])
const feedback = ref(null)
const actionError = ref(null)

const can = (action) => session.can(action, 'Role')

// Permisos agrupados por "subject" para el formulario.
const permissionGroups = computed(() => {
  const groups = {}
  for (const p of permissions.value) (groups[p.subject] ??= []).push(p)
  return Object.entries(groups).map(([subject, items]) => ({ subject, label: SUBJECTS[subject] ?? subject, items }))
})

const editing = ref(null)
const form = reactive({ label: '', description: '', permissions: [] })
const formError = ref(null)
const saving = ref(false)

function openForm(role = {}) {
  editing.value = role
  formError.value = null
  Object.assign(form, {
    label: role.label ?? '',
    description: role.description ?? '',
    permissions: role.permissions?.map((p) => p.id) ?? [],
  })
}

const lockedPermissions = computed(() => editing.value?.name === 'admin')

async function save() {
  saving.value = true
  formError.value = null
  try {
    const payload = { label: form.label, description: form.description || null }
    if (!lockedPermissions.value) payload.permissions = form.permissions
    if (editing.value.id) await api.put(`/roles/${editing.value.id}`, payload)
    else await api.post('/roles', { ...payload, permissions: form.permissions })
    feedback.value = `Rol ${form.label} guardado.`
    editing.value = null
    await session.restore() // por si cambió un rol que yo mismo tengo
    run()
  } catch (e) {
    formError.value = e
  } finally {
    saving.value = false
  }
}

async function remove(role) {
  actionError.value = null
  feedback.value = null
  if (!window.confirm(`¿Eliminar el rol "${role.label}"?`)) return
  try {
    await api.delete(`/roles/${role.id}`)
    feedback.value = `Rol ${role.label} eliminado.`
    run()
  } catch (e) {
    actionError.value = e
  }
}

onMounted(async () => {
  run()
  try {
    permissions.value = (await api.get('/permissions')).data
  } catch {
    permissions.value = []
  }
})
</script>

<template>
  <PageHeader title="Roles y permisos" subtitle="Cada rol agrupa permisos. El servidor valida cada permiso en cada petición.">
    <button v-if="can('create')" class="btn btn-primary" @click="openForm()">+ Nuevo rol</button>
  </PageHeader>

  <AppAlert v-if="feedback" type="ok" :text="feedback" />
  <AppAlert :error="actionError" />

  <AsyncState :loading="loading" :error="error" :empty="!data?.length" empty-title="No hay roles" @retry="run">
    <div class="card-grid card-grid-wide">
      <article v-for="r in data" :key="r.id" class="card role-card">
        <div class="role-head">
          <div>
            <h3>{{ r.label }} <span v-if="r.is_system" class="badge">Base</span></h3>
            <p class="muted small">{{ r.description || 'Sin descripción' }} · {{ r.users_count }} usuario{{ r.users_count === 1 ? '' : 's' }}</p>
          </div>
        </div>
        <div class="chips">
          <span v-for="p in r.permissions" :key="p.id" class="chip" :title="`${p.action} ${p.subject}`">{{ p.label }}</span>
          <span v-if="!r.permissions.length" class="muted small">Sin permisos</span>
        </div>
        <div class="row-actions">
          <button v-if="can('update')" class="btn btn-ghost" @click="openForm(r)">Editar</button>
          <button v-if="can('delete') && !r.is_system" class="btn btn-ghost danger" @click="remove(r)">Eliminar</button>
        </div>
      </article>
    </div>
  </AsyncState>

  <BaseModal v-if="editing" :title="editing.id ? `Editar rol: ${editing.label}` : 'Nuevo rol'" @close="editing = null">
    <form class="form" @submit.prevent="save">
      <AppAlert v-if="formError && !formError.errors" :error="formError" />
      <label class="field"><span>Nombre del rol</span><input v-model.trim="form.label" required /><FieldError :error="formError" field="label" /></label>
      <label class="field"><span>Descripción</span><input v-model.trim="form.description" /><FieldError :error="formError" field="description" /></label>
      <p v-if="lockedPermissions" class="notice notice-info">Los permisos del administrador no se pueden quitar.</p>
      <fieldset v-for="g in permissionGroups" :key="g.subject" class="field perm-group" :disabled="lockedPermissions">
        <legend>{{ g.label }}</legend>
        <label v-for="p in g.items" :key="p.id" class="check">
          <input v-model="form.permissions" type="checkbox" :value="p.id" />
          <span>{{ p.label }} <code class="muted">{{ p.action }} {{ p.subject }}</code></span>
        </label>
      </fieldset>
      <FieldError :error="formError" field="permissions" />
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" @click="editing = null">Cancelar</button>
        <button class="btn btn-primary" :disabled="saving">{{ saving ? 'Guardando…' : 'Guardar' }}</button>
      </div>
    </form>
  </BaseModal>
</template>
