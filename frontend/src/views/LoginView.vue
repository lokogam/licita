<script setup>
/**
 * Login screen. On success it returns to the page the user wanted (?redirect=) or to the list.
 */
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FormField from '@/components/base/FormField.vue'
import { useAuth } from '@/composables/useAuth'
import { HOME_ROUTE, safeRedirect } from '@/router/guard'
import { validateLogin } from '@/utils/validators'

/** Only local builds list the seeded demo accounts (the flag is replaced at build time). */
const showDemoUsers = import.meta.env.VITE_SHOW_DEMO_USERS === 'true'

/** Local demo accounts; production builds drop this list entirely. */
const DEMO_USERS = showDemoUsers
  ? [
      { role: 'Administrador', icon: 'bi-shield-lock', email: 'admin@licita.test', password: 'Admin-Demo-2026' },
      { role: 'Editor', icon: 'bi-pencil', email: 'editor@licita.test', password: 'Editor-Demo-2026' },
      { role: 'Lector', icon: 'bi-eye', email: 'lector@licita.test', password: 'Lector-Demo-2026' },
    ]
  : []

const route = useRoute()
const router = useRouter()
const { login } = useAuth()

const form = reactive({ email: '', password: '' })
const touched = reactive({ email: false, password: false })
const serverErrors = ref({})
const alert = ref(null)
const submitting = ref(false)
const showPassword = ref(false)
const demoOpen = ref(false)

const errors = computed(() => validateLogin(form))

/**
 * @param {'email'|'password'} field
 * @returns {string}
 */
function visibleError(field) {
  if (serverErrors.value[field]) return serverErrors.value[field]
  return touched[field] ? (errors.value[field] ?? '') : ''
}

/** @param {'email'|'password'} field */
function onInput(field) {
  if (serverErrors.value[field]) {
    const { [field]: _removed, ...rest } = serverErrors.value
    serverErrors.value = rest
  }
}

/** @param {(typeof DEMO_USERS)[number]} account */
function useDemoAccount(account) {
  form.email = account.email
  form.password = account.password
  serverErrors.value = {}
  alert.value = null
}

async function submit() {
  touched.email = true
  touched.password = true
  if (Object.keys(errors.value).length > 0) return

  submitting.value = true
  alert.value = null
  serverErrors.value = {}
  try {
    await login(form.email, form.password)
    await router.replace(safeRedirect(route.query.redirect) ?? HOME_ROUTE)
  } catch (e) {
    serverErrors.value = e.errors ?? {}
    alert.value = { variant: e.status === 429 ? 'warning' : 'danger', message: e.message }
    form.password = ''
    touched.password = false
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <aside class="login-aside" aria-hidden="true">
      <div class="login-aside-inner">
        <div class="d-flex align-items-center gap-2 mb-5">
          <span class="brand-mark"></span>
          <span class="brand-name text-white">licita</span>
          <span class="brand-divider"></span>
          <span class="brand-module">Licitaciones</span>
        </div>
        <h2 class="login-aside-title">Gestione sus procesos de compra en un solo lugar.</h2>
        <ul class="login-aside-list list-unstyled mb-0">
          <li><i class="bi bi-calendar2-check"></i> Cronogramas con estado automático</li>
          <li><i class="bi bi-folder2-open"></i> Pliegos y anexos siempre a la mano</li>
          <li><i class="bi bi-file-earmark-excel"></i> Reportes en Excel con un clic</li>
        </ul>
      </div>
    </aside>

    <main class="login-main">
      <div class="login-panel">
        <div class="d-flex d-lg-none align-items-center gap-2 mb-4 login-brand-compact">
          <span class="brand-mark" aria-hidden="true"></span>
          <span class="brand-name">licita</span>
          <span class="text-body-secondary">Licitaciones</span>
        </div>

        <h1 class="page-title mb-1">Iniciar sesión</h1>
        <p class="text-body-secondary mb-4">Ingrese con su correo corporativo para continuar.</p>

        <div
          v-if="alert"
          class="alert d-flex align-items-start gap-2 py-2"
          :class="`alert-${alert.variant}`"
          role="alert"
          data-test="login-alert"
        >
          <i
            class="bi mt-1"
            :class="alert.variant === 'warning' ? 'bi-hourglass-split' : 'bi-exclamation-octagon'"
            aria-hidden="true"
          ></i>
          <span>{{ alert.message }}</span>
        </div>

        <form class="d-grid gap-3" novalidate @submit.prevent="submit">
          <FormField label="Correo electrónico" input-id="login-email" :error="visibleError('email')">
            <div class="input-icon">
              <i class="bi bi-envelope" aria-hidden="true"></i>
              <input
                id="login-email"
                v-model="form.email"
                type="email"
                class="form-control form-control-lg"
                :class="{ 'is-invalid': visibleError('email') }"
                :aria-invalid="visibleError('email') ? 'true' : 'false'"
                :aria-describedby="visibleError('email') ? 'login-email-error' : undefined"
                autocomplete="username"
                placeholder="nombre@empresa.com"
                autofocus
                @input="onInput('email')"
                @blur="touched.email = true"
              />
            </div>
          </FormField>

          <FormField label="Contraseña" input-id="login-password" :error="visibleError('password')">
            <div class="input-icon password-field">
              <i class="bi bi-lock" aria-hidden="true"></i>
              <input
                id="login-password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="form-control form-control-lg"
                :class="{ 'is-invalid': visibleError('password') }"
                :aria-invalid="visibleError('password') ? 'true' : 'false'"
                :aria-describedby="visibleError('password') ? 'login-password-error' : undefined"
                autocomplete="current-password"
                placeholder="••••••••••"
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

          <button type="submit" class="btn btn-primary btn-lg mt-2" :disabled="submitting">
            <span v-if="submitting" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
            {{ submitting ? 'Ingresando…' : 'Ingresar' }}
          </button>
        </form>

        <section v-if="showDemoUsers" class="demo-users mt-4" data-test="demo-users">
          <button
            type="button"
            class="demo-users-toggle"
            :aria-expanded="demoOpen"
            aria-controls="demo-users-list"
            @click="demoOpen = !demoOpen"
          >
            <i class="bi bi-person-badge" aria-hidden="true"></i>
            <span class="flex-grow-1 text-start">Usuarios de demostración</span>
            <i class="bi" :class="demoOpen ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
          </button>
          <ul v-if="demoOpen" id="demo-users-list" class="list-unstyled mb-0 mt-2 d-grid gap-2">
            <li v-for="account in DEMO_USERS" :key="account.email">
              <button
                type="button"
                class="demo-user"
                :aria-label="`Usar cuenta ${account.role}`"
                @click="useDemoAccount(account)"
              >
                <i class="bi" :class="account.icon" aria-hidden="true"></i>
                <span class="min-w-0 text-start">
                  <span class="d-block fw-semibold">{{ account.role }}</span>
                  <span class="d-block small text-body-secondary text-truncate">
                    {{ account.email }} · <code>{{ account.password }}</code>
                  </span>
                </span>
              </button>
            </li>
          </ul>
        </section>

        <p class="small text-body-secondary mt-4 mb-0">
          <i class="bi bi-shield-check me-1" aria-hidden="true"></i>¿Olvidó su contraseña? Solicite el cambio a un
          administrador.
        </p>
      </div>
    </main>
  </div>
</template>
