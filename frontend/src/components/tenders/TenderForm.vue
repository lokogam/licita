<script setup>
/**
 * Tender form: "Información básica" and "Cronograma" sections.
 * Extra sections (documents) are injected through the "extra" slot.
 */
import { computed, watch } from 'vue'
import FormField from '@/components/base/FormField.vue'
import ActivityAutocomplete from '@/components/tenders/ActivityAutocomplete.vue'
import { useTenderForm } from '@/composables/useTenderForm'
import { CURRENCIES, LIMITS, sanitizeBudget } from '@/utils/validators'

const props = defineProps({
  initial: { type: Object, default: null },
  submitLabel: { type: String, required: true },
  submitting: { type: Boolean, default: false },
  serverErrors: { type: Object, default: () => ({}) },
  extraValid: { type: Boolean, default: true },
})

const emit = defineEmits(['submit', 'cancel'])

const { form, isValid, touch, touchAll, visibleError, setServerErrors, payload } = useTenderForm(props.initial)

watch(() => props.serverErrors, setServerErrors, { immediate: true })

const canSubmit = computed(() => isValid.value && props.extraValid && !props.submitting)

/** @param {Event} event */
function onBudgetInput(event) {
  const clean = sanitizeBudget(event.target.value)
  form.budget = clean
  if (event.target.value !== clean) event.target.value = clean
}

function onSubmit() {
  touchAll()
  if (canSubmit.value) emit('submit', payload())
}

const fieldClass = (field) => ({ 'is-invalid': Boolean(visibleError(field)) })
const describedBy = (field) => (visibleError(field) ? `${field}-error` : undefined)
</script>

<template>
  <form novalidate @submit.prevent="onSubmit">
    <section class="card form-section mb-4" aria-labelledby="section-basic">
      <div class="card-body">
        <div class="form-section-header">
          <span class="form-section-step" aria-hidden="true">1</span>
          <div>
            <h2 id="section-basic" class="h5 mb-0">Información básica</h2>
            <p class="text-body-secondary small mb-0">Qué se va a contratar y con qué presupuesto.</p>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12">
            <FormField
              label="Objeto"
              input-id="subject"
              required
              :error="visibleError('subject')"
              :count="form.subject.length"
              :max="LIMITS.subject"
            >
              <input
                id="subject"
                v-model="form.subject"
                type="text"
                class="form-control"
                :class="fieldClass('subject')"
                :maxlength="LIMITS.subject"
                :aria-invalid="Boolean(visibleError('subject'))"
                :aria-describedby="describedBy('subject')"
                placeholder="Ej. Compra de equipos de cómputo para el área comercial"
                @blur="touch('subject')"
              />
            </FormField>
          </div>

          <div class="col-12">
            <FormField
              label="Descripción / Alcance"
              input-id="description"
              required
              :error="visibleError('description')"
              :count="form.description.length"
              :max="LIMITS.description"
            >
              <textarea
                id="description"
                v-model="form.description"
                rows="4"
                class="form-control"
                :class="fieldClass('description')"
                :maxlength="LIMITS.description"
                :aria-invalid="Boolean(visibleError('description'))"
                :aria-describedby="describedBy('description')"
                placeholder="Alcance, cantidades y condiciones principales"
                @blur="touch('description')"
              ></textarea>
            </FormField>
          </div>

          <div class="col-md-4">
            <FormField label="Moneda" input-id="currency" required :error="visibleError('currency')">
              <select
                id="currency"
                v-model="form.currency"
                class="form-select"
                :class="fieldClass('currency')"
                :aria-invalid="Boolean(visibleError('currency'))"
                :aria-describedby="describedBy('currency')"
                @blur="touch('currency')"
                @change="touch('currency')"
              >
                <option value="" disabled>Seleccione…</option>
                <option v-for="currency in CURRENCIES" :key="currency" :value="currency">{{ currency }}</option>
              </select>
            </FormField>
          </div>

          <div class="col-md-8">
            <FormField
              label="Presupuesto"
              input-id="budget"
              required
              hint="Solo números y punto decimal, máximo 2 decimales."
              :error="visibleError('budget')"
            >
              <div class="input-group has-validation">
                <span class="input-group-text fw-semibold">{{ form.currency || '$' }}</span>
                <input
                  id="budget"
                  type="text"
                  inputmode="decimal"
                  class="form-control text-end font-tabular"
                  :class="fieldClass('budget')"
                  :value="form.budget"
                  :aria-invalid="Boolean(visibleError('budget'))"
                  :aria-describedby="describedBy('budget')"
                  placeholder="0.00"
                  @input="onBudgetInput"
                  @blur="touch('budget')"
                />
              </div>
            </FormField>
          </div>

          <div class="col-12">
            <FormField label="Actividad" input-id="activity" required :error="visibleError('activity_id')">
              <ActivityAutocomplete
                v-model="form.activity"
                input-id="activity"
                :invalid="Boolean(visibleError('activity_id'))"
                :described-by="describedBy('activity_id')"
                @blur="touch('activity_id')"
                @update:model-value="touch('activity_id')"
              />
            </FormField>
          </div>
        </div>
      </div>
    </section>

    <section class="card form-section mb-4" aria-labelledby="section-schedule">
      <div class="card-body">
        <div class="form-section-header">
          <span class="form-section-step" aria-hidden="true">2</span>
          <div>
            <h2 id="section-schedule" class="h5 mb-0">Cronograma</h2>
            <p class="text-body-secondary small mb-0">Ventana en la que los proveedores pueden presentar ofertas.</p>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-sm-6 col-lg-3">
            <FormField label="Fecha de inicio" input-id="start_date" required :error="visibleError('start_date')">
              <input
                id="start_date"
                v-model="form.start_date"
                type="date"
                class="form-control"
                :class="fieldClass('start_date')"
                :aria-invalid="Boolean(visibleError('start_date'))"
                :aria-describedby="describedBy('start_date')"
                @blur="touch('start_date')"
              />
            </FormField>
          </div>
          <div class="col-sm-6 col-lg-3">
            <FormField label="Hora de inicio" input-id="start_time" required :error="visibleError('start_time')">
              <input
                id="start_time"
                v-model="form.start_time"
                type="time"
                step="60"
                class="form-control"
                :class="fieldClass('start_time')"
                :aria-invalid="Boolean(visibleError('start_time'))"
                :aria-describedby="describedBy('start_time')"
                @blur="touch('start_time')"
              />
            </FormField>
          </div>
          <div class="col-sm-6 col-lg-3">
            <FormField label="Fecha de cierre" input-id="closing_date" required :error="visibleError('closing_date')">
              <input
                id="closing_date"
                v-model="form.closing_date"
                type="date"
                class="form-control"
                :class="fieldClass('closing_date')"
                :min="form.start_date || undefined"
                :aria-invalid="Boolean(visibleError('closing_date'))"
                :aria-describedby="describedBy('closing_date')"
                @blur="touch('closing_date')"
              />
            </FormField>
          </div>
          <div class="col-sm-6 col-lg-3">
            <FormField label="Hora de cierre" input-id="closing_time" required :error="visibleError('closing_time')">
              <input
                id="closing_time"
                v-model="form.closing_time"
                type="time"
                step="60"
                class="form-control"
                :class="fieldClass('closing_time')"
                :aria-invalid="Boolean(visibleError('closing_time'))"
                :aria-describedby="describedBy('closing_time')"
                @blur="touch('closing_time')"
              />
            </FormField>
          </div>
        </div>
      </div>
    </section>

    <slot name="extra" />

    <div class="form-actions">
      <p class="small text-body-secondary mb-0 d-none d-md-block">
        <span class="text-danger">*</span> Campos obligatorios. Guardar se habilita cuando todo es válido.
      </p>
      <div class="d-flex gap-2 ms-auto">
        <button type="button" class="btn btn-outline-secondary" @click="emit('cancel')">Cancelar</button>
        <button type="submit" class="btn btn-primary px-4" :disabled="!canSubmit">
          <span v-if="submitting" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
          {{ submitLabel }}
        </button>
      </div>
    </div>
  </form>
</template>
