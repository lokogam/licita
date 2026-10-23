<script setup>
/**
 * Create / edit user modal. Without `user` it creates one; with `user` it edits it
 * (an empty password keeps the current one).
 */
import { computed, reactive, ref, watch } from 'vue'
import AppModal from '@/components/base/AppModal.vue'
import FormField from '@/components/base/FormField.vue'
import { LIMITS, ROLES, validateUser } from '@/utils/validators'

const props = defineProps({
  show: { type: Boolean, default: false },
  user: { type: Object, default: null },
  saving: { type: Boolean, default: false },
  serverErrors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['save', 'close'])

const FIELDS = ['name', 'email', 'role', 'password']

const editing = computed(() => props.user !== null)
const draft = reactive({ name: '', email: '', role: '', password: '' })
const touched = reactive({ name: false, email: false, role: false, password: false })
const dismissed = ref({})
const showPassword = ref(false)

const errors = computed(() => validateUser(draft, { requirePassword: !editing.value }))
const isValid = computed(() => Object.keys(errors.value).length === 0)

/** @param {string} field */
function visibleError(field) {
  if (props.serverErrors[field] && !dismissed.value[field]) return props.serverErrors[field]
  return touched[field] ? (errors.value[field] ?? '') : ''
}

watch(
  () => props.show,
  (visible) => {
    if (!visible) return
    Object.assign(draft, {
      name: props.user?.name ?? '',
      email: props.user?.email ?? '',
      role: props.user?.role?.value ?? '',
      password: '',
    })
    FIELDS.forEach((field) => (touched[field] = false))
    showPassword.value = false
  },
  { immediate: true },
)

// New server errors are shown again even if the user had edited that field before.
watch(
  () => props.serverErrors,
  () => (dismissed.value = {}),
)

/** @param {string} field */
function onInput(field) {
  if (props.serverErrors[field]) dismissed.value = { ...dismissed.value, [field]: true }
}

function submit() {
  FIELDS.forEach((field) => (touched[field] = true))
  if (!isValid.value) return

  const payload = { name: draft.name.trim(), email: draft.email.trim(), role: draft.role }
  if (draft.password !== '') payload.password = draft.password
  emit('save', payload)
}
</script>

<template>
  <AppModal
    :show="show"
    :title="editing ? 'Editar usuario' : 'Nuevo usuario'"
    :icon="editing ? 'bi-person-gear' : 'bi-person-plus'"
    title-id="user-modal-title"
    @close="emit('close')"
  >
    <form id="user-form" class="d-grid gap-3" novalidate @submit.prevent="submit">
      <FormField
        label="Nombre"
        input-id="user-name"
        required
        :error="visibleError('name')"
        :count="draft.name.length"
        :max="LIMITS.userName"
      >
        <input
          id="user-name"
          v-model="draft.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': visibleError('name') }"
          :aria-invalid="visibleError('name') ? 'true' : 'false'"
          :maxlength="LIMITS.userName"
          autocomplete="off"
          placeholder="Ej. Laura Martínez"
          @input="onInput('name')"
          @blur="touched.name = true"
        />
      </FormField>

      <FormField label="Correo electrónico" input-id="user-email" required :error="visibleError('email')">
        <input
          id="user-email"
          v-model="draft.email"
          type="email"
          class="form-control"
          :class="{ 'is-invalid': visibleError('email') }"
          :aria-invalid="visibleError('email') ? 'true' : 'false'"
          :maxlength="LIMITS.email"
          autocomplete="off"
          placeholder="nombre@empresa.com"
          @input="onInput('email')"
          @blur="touched.email = true"
        />
      </FormField>

      <FormField label="Rol" input-id="user-role" required :error="visibleError('role')">
        <select
          id="user-role"
          v-model="draft.role"
          class="form-select"
          :class="{ 'is-invalid': visibleError('role') }"
          :aria-invalid="visibleError('role') ? 'true' : 'false'"
          @change="onInput('role')"
          @blur="touched.role = true"
        >
          <option value="" disabled>Seleccione un rol</option>
          <option v-for="role in ROLES" :key="role.value" :value="role.value">{{ role.label }}</option>
        </select>
      </FormField>

      <FormField
        label="Contraseña"
        input-id="user-password"
        :required="!editing"
        :error="visibleError('password')"
        :hint="
          editing
            ? `Dejar en blanco para conservar la actual. Mínimo ${LIMITS.passwordMin} caracteres.`
            : `Mínimo ${LIMITS.passwordMin} caracteres.`
        "
      >
        <div class="password-field">
          <input
            id="user-password"
            v-model="draft.password"
            :type="showPassword ? 'text' : 'password'"
            class="form-control"
            :class="{ 'is-invalid': visibleError('password') }"
            :aria-invalid="visibleError('password') ? 'true' : 'false'"
            :maxlength="LIMITS.passwordMax"
            autocomplete="new-password"
            @input="onInput('password')"
            @blur="touched.password = true"
          />
          <button
            type="button"
            class="password-toggle"
            :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            :aria-pressed="showPassword"
            @click="showPassword = !showPassword"
          >
            <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
          </button>
        </div>
      </FormField>
    </form>

    <template #footer>
      <button type="button" class="btn btn-outline-secondary" @click="emit('close')">Cancelar</button>
      <button type="submit" form="user-form" class="btn btn-primary" :disabled="!isValid || saving">
        <span v-if="saving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
        {{ editing ? 'Guardar cambios' : 'Crear usuario' }}
      </button>
    </template>
  </AppModal>
</template>
