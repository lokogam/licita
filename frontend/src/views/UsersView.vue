<script setup>
/**
 * User administration (admins only): list, create, edit and delete users.
 */
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppModal from '@/components/base/AppModal.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageHeader from '@/components/base/PageHeader.vue'
import RoleBadge from '@/components/users/RoleBadge.vue'
import UserFormModal from '@/components/users/UserFormModal.vue'
import { createUser, deleteUser, listUsers, updateUser } from '@/api/users'
import { useAuth } from '@/composables/useAuth'
import { useToasts } from '@/composables/useToasts'
import { formatDate } from '@/utils/formatters'

const router = useRouter()
const { user: currentUser, refresh, can } = useAuth()
const { notify } = useToasts()

const users = ref([])
const loading = ref(true)
const error = ref('')

/* Create / edit modal: `editingUser` is null when creating. */
const formOpen = ref(false)
const editingUser = ref(null)
const saving = ref(false)
const formErrors = ref({})

/* Delete confirmation */
const deletingUser = ref(null)
const deleting = ref(false)
const deleteError = ref('')

const total = computed(() => users.value.length)

/** @param {{id: number}} user */
const isSelf = (user) => user.id === currentUser.value?.id

async function load() {
  loading.value = true
  error.value = ''
  try {
    users.value = await listUsers()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(load)

/** @param {object|null} user */
function openForm(user = null) {
  editingUser.value = user
  formErrors.value = {}
  formOpen.value = true
}

function closeForm() {
  formOpen.value = false
  editingUser.value = null
}

async function save(payload) {
  saving.value = true
  formErrors.value = {}
  try {
    if (editingUser.value) {
      const updated = await updateUser(editingUser.value.id, payload)
      users.value = users.value.map((user) => (user.id === updated.id ? updated : user))
      notify(`Usuario ${updated.name} actualizado correctamente.`)
      if (isSelf(updated)) await syncOwnAccount()
    } else {
      const created = await createUser(payload)
      users.value = [...users.value, created]
      notify(`Usuario ${created.name} creado correctamente.`)
    }
    closeForm()
  } catch (e) {
    formErrors.value = e.errors ?? {}
    notify(e.message, 'danger')
  } finally {
    saving.value = false
  }
}

/** Editing your own account changes the session data (name, role, abilities). */
async function syncOwnAccount() {
  try {
    await refresh()
  } catch {
    return
  }
  if (!can('users.manage')) router.replace({ name: 'tenders.index' })
}

/** @param {object} user */
function askDelete(user) {
  deleteError.value = ''
  deletingUser.value = user
}

async function confirmDelete() {
  deleting.value = true
  deleteError.value = ''
  try {
    await deleteUser(deletingUser.value.id)
    users.value = users.value.filter((user) => user.id !== deletingUser.value.id)
    notify(`Usuario ${deletingUser.value.name} eliminado.`)
    deletingUser.value = null
  } catch (e) {
    deleteError.value = Object.values(e.errors ?? {})[0] ?? e.message
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="Usuarios" subtitle="Administre quién accede al módulo y qué puede hacer.">
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openForm()">
          <i class="bi bi-person-plus me-2" aria-hidden="true"></i>Nuevo usuario
        </button>
      </template>
    </PageHeader>

    <section class="card data-card">
      <div v-if="error" class="alert alert-danger m-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>{{ error }}
        <button type="button" class="btn btn-sm btn-outline-danger ms-2" @click="load">Reintentar</button>
      </div>

      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
          <thead>
            <tr>
              <th scope="col">Nombre</th>
              <th scope="col">Correo</th>
              <th scope="col">Rol</th>
              <th scope="col">Creado</th>
              <th scope="col" class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody v-if="loading">
            <tr v-for="n in 4" :key="n" class="placeholder-glow" aria-hidden="true">
              <td v-for="c in 5" :key="c"><span class="placeholder col-8 rounded"></span></td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr v-for="user in users" :key="user.id" :data-test="`user-row-${user.id}`">
              <td class="fw-semibold">
                <span class="d-inline-flex align-items-center gap-2">
                  {{ user.name }}
                  <span v-if="isSelf(user)" class="badge rounded-pill text-bg-light border fw-medium">Usted</span>
                </span>
              </td>
              <td class="text-body-secondary">
                <span class="text-truncate d-block cell-email" :title="user.email">{{ user.email }}</span>
              </td>
              <td><RoleBadge :role="user.role" /></td>
              <td class="text-nowrap">{{ formatDate(user.created_at) }}</td>
              <td class="text-end text-nowrap">
                <button
                  type="button"
                  class="btn btn-sm btn-light me-1"
                  :aria-label="`Editar ${user.email}`"
                  @click="openForm(user)"
                >
                  <i class="bi bi-pencil-square" aria-hidden="true"></i>
                  <span class="d-none d-md-inline ms-1">Editar</span>
                </button>
                <button
                  type="button"
                  class="btn btn-sm btn-light text-danger"
                  :aria-label="`Eliminar ${user.email}`"
                  :title="isSelf(user) ? 'No puede eliminar su propio usuario.' : 'Eliminar usuario'"
                  :disabled="isSelf(user)"
                  @click="askDelete(user)"
                >
                  <i class="bi bi-trash3" aria-hidden="true"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <EmptyState
          v-if="!loading && total === 0"
          icon="bi-people"
          title="Aún no hay usuarios"
          description="Cree el primer usuario para darle acceso al módulo."
        />
      </div>

      <div v-if="!loading && !error && total > 0" class="card-footer bg-transparent">
        <p class="small text-body-secondary mb-0">{{ total }} {{ total === 1 ? 'usuario' : 'usuarios' }}</p>
      </div>
    </section>

    <UserFormModal
      :show="formOpen"
      :user="editingUser"
      :saving="saving"
      :server-errors="formErrors"
      @save="save"
      @close="closeForm"
    />

    <AppModal
      :show="deletingUser !== null"
      title="Eliminar usuario"
      icon="bi-person-x"
      title-id="delete-user-title"
      @close="deletingUser = null"
    >
      <p class="mb-0">
        ¿Eliminar a <strong>{{ deletingUser?.name }}</strong> ({{ deletingUser?.email }})? Perderá el acceso de
        inmediato y esta acción no se puede deshacer.
      </p>
      <div v-if="deleteError" class="alert alert-danger py-2 mt-3 mb-0" role="alert">{{ deleteError }}</div>
      <template #footer>
        <button type="button" class="btn btn-outline-secondary" @click="deletingUser = null">Cancelar</button>
        <button type="button" class="btn btn-danger" :disabled="deleting" @click="confirmDelete">
          <span v-if="deleting" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
          Eliminar
        </button>
      </template>
    </AppModal>
  </div>
</template>
