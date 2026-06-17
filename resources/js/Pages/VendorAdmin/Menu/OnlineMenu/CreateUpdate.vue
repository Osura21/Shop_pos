<template>

  <Head :title="isEdit ? 'Update Online Menu' : 'Create Online Menu'" />

  <div class="form-container">
    <!-- Gradient Background -->
    <div class="gradient-overlay  gradientOverlay"></div>

    <!-- Form Wrapper -->
    <div class="form-wrapper formWrapper">
      <!-- Header Section -->
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              {{ isEdit ? 'Update Online Menu' : 'Create Online Menu' }}
            </h1>
            <p class="header-subtitle">
              Configure online ordering menu visibility and settings.
            </p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('vendor.online-menus.index')" class="btn btn-ghost">
            Cancel
            </Link>
            <button class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Online Menu' : 'Create Online Menu') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submit" class="form-content">
        <div v-if="form.errors.general" class="alert alert-danger">
          {{ form.errors.general }}
        </div>

        <div class="form-grid">
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Online Menu Information</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <Input id="name" label="Menu Name" v-model="form.name" placeholder="Online Menu Name"
                      :error="form.errors.name" />
                  </div>

                  <div class="col-12 col-md-6">
                    <MultiSelectInput label="Branches" v-model="form.branch_ids" :options="branches" labelKey="name" valueKey="id"
                      placeholder="Select Branch" :error="form.errors.branch_ids" />
                  </div>

                  <div class="col-12 col-md-6">
                    <Input id="slug" label="Slug / URL" v-model="form.slug" placeholder="online-menu-slug"
                      :error="form.errors.slug" />
                  </div>

                  <div class="col-12 col-md-6">
                    <SelectInput label="Linked Menu" id="branch.id" v-model="form.menu_id" :options="menus"
                      valueKey="id" labelKey="name" placeholder="Select Menu" :error="form.errors.menu_id" />
                  </div>

                  <div class="col-12">
                    <div class="form-check form-switch mt-2">
                      <input id="is_active" class="form-check-input" type="checkbox" v-model="form.is_active">
                      <label class="form-check-label" for="is_active">Active for Online Ordering</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import Input from '@/Components/InputField.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import MultiSelectInput from '@/Components/MultiSelectInput.vue'

export default {
  name: 'OnlineMenuCreateUpdate',
  layout: VendorAdminLayout,
  components: { Head, Link, Input, SelectInput, MultiSelectInput },

  props: {
    onlineMenu: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
    menus: { type: Array, default: () => [] },
  },

  data() {
    return {
      form: useForm({
        name: this.onlineMenu?.name ?? '',
        branch_ids: this.onlineMenu?.branch_ids ?? [],
        menu_id: this.onlineMenu?.menu_id ?? '',
        slug: this.onlineMenu?.slug ?? '',
        is_active: !!(this.onlineMenu?.is_active ?? true),
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.onlineMenu?.id
    },
  },

  watch: {
    'form.name'(value) {
      if (!this.isEdit && !this.form.slug) {
        this.form.slug = this.slugify(value)
      }
    },
  },

  methods: {
    slugify(value) {
      return String(value || '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
    },

    nullable(value) {
      return value === '' || value === undefined ? null : value
    },

    normalizedPayload(data) {
      return {
        ...data,
        branch_ids: data.branch_ids ?? [],
        menu_id: this.nullable(data.menu_id),
        slug: this.slugify(data.slug || data.name),
        is_active: data.is_active ? 1 : 0,
      }
    },

    submit() {
      const requestOptions = {
        preserveScroll: true,
        onSuccess: () => router.visit(route('vendor.online-menus.index')),
        onError: (errors) => {
          const message =
            errors?.general ||
            Object.values(errors)?.flat()?.[0] ||
            'Something went wrong.'

          alertError(message)
        }
      }

      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...this.normalizedPayload(data),
            _method: 'PUT',
          }))
          .post(route('vendor.online-menus.update', this.onlineMenu.id), requestOptions)

        return
      }

      this.form
        .transform((data) => this.normalizedPayload(data))
        .post(route('vendor.online-menus.store'), requestOptions)
    },
  },
}
</script>

<style scoped>

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 40px;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.error-text {
  font-size: 12px;
  color: #dc2626;
  margin-top: 4px;
}

.spinner-icon {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 640px) {
  .form-container {
    padding: 24px 16px;
  }

  .header-title {
    font-size: 24px;
  }

  .form-card {
    border-radius: 12px;
  }

  .card-header {
    padding: 20px 16px;
  }
}
</style>
