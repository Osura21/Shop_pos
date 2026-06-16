<template>
  <div class="form-container">
    <div class="gradient-overlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">{{ isEdit ? 'Update Food Type' : 'Create Food Type' }}</h1>
            <p class="header-subtitle">Food types appear on the public Choose Food Type slider.</p>
          </div>

          <div class="d-flex gap-2">
            <Link :href="route('food-categories.index')" class="btn btn-ghost">
              Cancel
            </Link>

            <button type="button" class="btn btn-primary-modern" @click="submit" :disabled="form.processing">
              <span v-if="form.processing" class="spinner-icon"></span>
              {{ form.processing ? 'Saving...' : (isEdit ? 'Update Food Type' : 'Create Food Type') }}
            </button>
          </div>
        </div>
      </div>

      <form @submit.prevent="submit" class="form-content">
        <div class="form-grid">
          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Food Type Details</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="row g-3">
                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="foodTypeName" label="Name" v-model="form.name" placeholder="Appetizers"
                      :error="form.errors.name" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="foodTypeSlug" label="Slug" v-model="form.slug" placeholder="appetizers"
                      :error="form.errors.slug" />
                  </div>

                  <div class="col-12 col-md-6 mb-2">
                    <InputField id="foodTypeSortOrder" label="Sort Order" v-model="form.sort_order" type="number"
                      placeholder="0" :error="form.errors.sort_order" />
                  </div>

                  <div class="col-12 col-md-6 mb-2 d-flex align-items-end">
                    <label class="checkbox-wrap">
                      <input v-model="form.is_active" type="checkbox">
                      <span class="checkbox-wrap__box">
                        <i class="bi bi-check"></i>
                      </span>
                      <span class="checkbox-wrap__label">Active</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-column">
            <div class="form-card">
              <div class="card-accent-line"></div>
              <div class="card-header">
                <h2 class="card-title cardTitle">Food Type Image</h2>
              </div>
              <div class="card-body formCardBody">
                <div class="avatar-box" @click="triggerImageSelect">
                  <template v-if="imagePreview">
                    <div class="avatar-placeholder">
                      <div class="avatar-image-wrapper">
                        <img :src="imagePreview" alt="Food type" class="avatar-img" />
                      </div>
                      <div class="fw-semibold mt-2">Upload Image</div>
                      <div class="small text-muted mt-1">JPG or PNG (max 5MB)</div>
                    </div>
                  </template>
                  <template v-else>
                    <div class="avatar-placeholder">
                      <i class="bi bi-image avatar-icon"></i>
                      <div class="fw-semibold mt-2">Upload Image</div>
                      <div class="small text-muted mt-1">JPG or PNG (max 5MB)</div>
                    </div>
                  </template>
                </div>

                <input ref="imageInput" id="foodTypeImage" type="file" class="d-none" accept="image/*"
                  @change="onImageChange" />

                <div class="d-flex gap-2 mt-3">
                  <button type="button" class="btn btn-outline-secondary btn-sm" @click="triggerImageSelect">
                    Upload Image
                  </button>
                  <button v-if="imagePreview" type="button" class="btn btn-outline-danger btn-sm" @click="clearImage">
                    Remove
                  </button>
                </div>

                <div v-if="form.errors.image" class="error-text mt-2">
                  {{ form.errors.image }}
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
import { useForm, Link } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import InputField from '@/Components/InputField.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";

export default {
  name: 'CreateUpdateFoodCategory',
  layout: SuperAdminLayout,

  components: {
    Link,
    InputField,
  },

  props: {
    foodCategory: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      imagePreview: this.foodCategory?.image_url || null,
      slugTouched: !!this.foodCategory?.slug,
      removeImage: false,

      form: useForm({
        name: this.foodCategory?.name ?? '',
        slug: this.foodCategory?.slug ?? '',
        sort_order: this.foodCategory?.sort_order ?? 0,
        is_active: this.foodCategory?.is_active ?? true,
        image: null,
        remove_image: 0,
      }),
    }
  },

  computed: {
    isEdit() {
      return !!this.foodCategory?.id
    },
  },

  watch: {
    'form.name'(value) {
      if (!this.slugTouched) {
        this.form.slug = this.slugify(value)
      }
    },
    'form.slug'() {
      this.slugTouched = true
    },
  },

  beforeUnmount() {
    if (this.imagePreview && String(this.imagePreview).startsWith('blob:')) {
      URL.revokeObjectURL(this.imagePreview)
    }
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

    triggerImageSelect() {
      this.$refs.imageInput?.click()
    },

    onImageChange(e) {
      const file = e.target.files?.[0] || null
      this.form.image = file
      this.form.remove_image = 0
      this.removeImage = false

      if (this.imagePreview && String(this.imagePreview).startsWith('blob:')) {
        URL.revokeObjectURL(this.imagePreview)
      }

      this.imagePreview = file ? URL.createObjectURL(file) : null
    },

    clearImage() {
      if (this.imagePreview && String(this.imagePreview).startsWith('blob:')) {
        URL.revokeObjectURL(this.imagePreview)
      }

      this.imagePreview = null
      this.form.image = null
      this.form.remove_image = 1
      this.removeImage = true

      if (this.$refs.imageInput) {
        this.$refs.imageInput.value = ''
      }
    },

    normalizedPayload(data) {
      return {
        ...data,
        slug: this.slugify(data.slug || data.name),
        sort_order: Number(data.sort_order || 0),
        is_active: data.is_active ? 1 : 0,
        remove_image: this.removeImage ? 1 : 0,
      }
    },

    submit() {
      if (this.isEdit) {
        this.form
          .transform((data) => ({
            ...this.normalizedPayload(data),
            _method: 'put',
          }))
          .post(route('food-categories.update', this.foodCategory.id), {
            preserveScroll: true,
            forceFormData: true, onError: (errors) => {
              const message =
                errors?.general ||
                Object.values(errors)?.flat()?.[0] ||
                'Something went wrong.'

              alertError(message)
            }
          })

        return
      }

      this.form
        .transform((data) => this.normalizedPayload(data))
        .post(route('food-categories.store'), {
          preserveScroll: true,
          forceFormData: true,
          onError: (errors) => {
            const message =
              errors?.general ||
              Object.values(errors)?.flat()?.[0] ||
              'Something went wrong.'

            alertError(message)
          }
        })
    },
  },
}
</script>

<style scoped>
.form-content {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
}

.form-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.avatar-box {
  min-height: 240px;
  border: 2px dashed rgba(242, 140, 0, 0.3);
  border-radius: 16px;
  background: #fcfaf7;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  text-align: center;
  padding: 20px;
  transition: all 0.2s ease;
}

.avatar-box:hover {
  border-color: rgba(242, 140, 0, 0.6);
  background: #fdf6ec;
}

.avatar-placeholder {
  color: #6b7280;
}

.avatar-icon {
  font-size: 68px;
  color: #f28c00;
}

.avatar-image-wrapper {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  margin: 0 auto;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.checkbox-wrap {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  user-select: none;
}

.checkbox-wrap input {
  display: none;
}

.checkbox-wrap__box {
  width: 22px;
  height: 22px;
  border-radius: 7px;
  border: 1px solid #d7dee7;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  color: transparent;
  transition: all .15s ease;
}

.checkbox-wrap input:checked+.checkbox-wrap__box {
  background: #f59e0b;
  border-color: #f59e0b;
  color: #fff;
}

.checkbox-wrap__label {
  font-weight: 700;
  color: #475569;
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

@media (max-width: 1024px) {
  .form-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }
}

@media (max-width: 768px) {
  .form-container {
    padding: 24px 16px;
  }
}
</style>
