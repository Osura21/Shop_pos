<template>

  <MiniSettingsNav :activeItem="'logo'" size="auto" :hideOnDesktop="true" />

  <Head title="Brand Logo Settings" />

  <div class="form-container">
    <div class="gradient-overlay gradientOverlay"></div>

    <div class="form-wrapper formWrapper">
      <div class="form-header formHeader">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h1 class="header-title">
              <i class="bi bi-image me-2 text-warning"></i>
              Logo Settings
            </h1>

            <p class="header-subtitle">
              Upload and manage your business logo used across invoices, receipts, and customer-facing pages.
            </p>
          </div>

          <button class="btn btn-primary-modern" :disabled="form.processing" @click="submit">
            <span v-if="form.processing" class="spinner-icon"></span>
            {{ form.processing ? 'Updating...' : 'Update Logo' }}
          </button>
        </div>
      </div>

      <form class="form-content" @submit.prevent="submit">
        <div class="form-grid">
          <MiniSettingsNav :activeItem="'logo'" size="auto" :hideOnMobile="true" />

          <div class="form-card">
            <div class="card-accent-line"></div>

            <div class="card-header">
              <h2 class="card-title cardTitle">Logo Management</h2>
            </div>

            <div class="card-body formCardBody">
              <div class="logo-grid">
                <!-- LOGO ITEM -->
                <div class="asset-row">
                  <div class="logo-preview-wrapper">
                    <img v-if="previewImage" :src="previewImage" class="logo-preview" alt="Logo" />
                    <div v-else class="logo-placeholder">
                      <i class="bi bi-image"></i>
                    </div>
                  </div>

                  <div class="logo-upload-content">
                    <h5 class="upload-title">Upload Business Logo</h5>

                    <p class="upload-description">
                      Recommended size: 512x512 PNG or JPG
                    </p>

                    <div class="action-row">
                      <label class="upload-btn">
                        <i class="bi bi-upload me-2"></i>
                        Choose Logo
                        <input type="file" accept="image/*" hidden @change="handleFileUpload('logo', $event)" />
                      </label>

                      <button v-if="previewImage" type="button" class="remove-btn" @click="removeFile('logo')">
                        <i class="bi bi-trash3 me-2"></i>
                        Remove
                      </button>
                    </div>

                    <div v-if="form.errors.logo" class="error-text">
                      {{ form.errors.logo }}
                    </div>
                  </div>
                </div>


                <!-- FAVICON ITEM -->
                <div class="asset-row">
                  <div class="logo-preview-wrapper">
                    <img v-if="previewFavicon" :src="previewFavicon" class="logo-preview" alt="Favicon" />
                    <div v-else class="logo-placeholder">
                      <i class="bi bi-image"></i>
                    </div>
                  </div>

                  <div class="logo-upload-content">
                    <h5 class="upload-title">Upload Business Favicon</h5>

                    <p class="upload-description">
                      Recommended: 32×32 or 64×64 ICO, PNG or SVG
                    </p>

                    <div class="action-row">
                      <label class="upload-btn">
                        <i class="bi bi-upload me-2"></i>
                        Choose Favicon
                        <input type="file" accept="image/*" hidden @change="handleFileUpload('favicon', $event)" />
                      </label>

                      <button v-if="previewFavicon" type="button" class="remove-btn" @click="removeFile('favicon')">
                        <i class="bi bi-trash3 me-2"></i>
                        Remove
                      </button>
                    </div>

                    <div v-if="form.errors.favicon" class="error-text">
                      {{ form.errors.favicon }}
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
import { Head, useForm } from '@inertiajs/vue3'
import VendorAdminLayout from '@/Layouts/VendorAdminLayout.vue'
import { error as alertError, success as alertSuccess } from "@/Utils/modernAlert";
import MiniSettingsNav from "@/Components/MiniSettingsNav.vue"

export default {
  name: 'LogoSettings',
  layout: VendorAdminLayout,

  components: { Head, MiniSettingsNav },

  props: {
    setting: {
      type: Object,
      default: () => ({}),
    },
  },

  data() {
    return {
      previewImage: this.setting?.logo_url || null,
      previewFavicon:
      this.setting?.favicon_url && !this.setting?.favicon_url.includes('default-favicon.png')
        ? this.setting?.favicon_url
        : null,

      form: useForm({
        logo: null,
        favicon: null,
        remove_logo: false,
        remove_favicon: false,
      }),
    }
  },
  computed: {
    flash() {
      return this.$page.props.flash;
    },
  },
  beforeUnmount() {

    if (this.previewImage?.startsWith('blob:')) {
      URL.revokeObjectURL(this.previewImage)
    }

    if (this.previewFavicon?.startsWith('blob:')) {
      URL.revokeObjectURL(this.previewFavicon)
    }
  },

  methods: {

    handleFileUpload(type, e) {
      const file = e.target.files?.[0]
      if (!file) return

      if (type === 'logo') {
        this.form.logo = file
        this.form.remove_logo = false

        this.previewImage = URL.createObjectURL(file)
      }

      if (type === 'favicon') {
        this.form.favicon = file
        this.form.remove_favicon = false

        this.previewFavicon = URL.createObjectURL(file)
      }
    },

    removeFile(type) {
      if (type === 'logo') {
        this.form.logo = null
        this.form.remove_logo = true

        if (this.previewImage?.startsWith('blob:')) {
          URL.revokeObjectURL(this.previewImage)
        }

        this.previewImage = null
      }

      if (type === 'favicon') {
        this.form.favicon = null
        this.form.remove_favicon = true

        if (this.previewFavicon?.startsWith('blob:')) {
          URL.revokeObjectURL(this.previewFavicon)
        }

        this.previewFavicon = null
      }
    },

    submit() {
      this.form.post(route('vendor.settings.logo.update'), {
        preserveScroll: true,
        forceFormData: true,
        // onSuccess: () => {
        //   this.showSuccessToast(
        //     this.$page?.props?.flash?.success ||
        //     'Brand assets updated successfully.'
        //   )
        // },
      })
    }
  },
  watch: {
    flash: {
      handler(flash) {
        if (flash?.message) {
          alertSuccess(flash.message);
        }

        if (flash?.error) {
          alertError(flash.error);
        }
      },
      immediate: true,
      deep: true
    }
  },
}
</script>

<style scoped>
.form-container {
  overflow-x: hidden;
}

.form-grid {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 40px;
  width: 100%;
  max-width: 100%;
}


.form-card {
  width: 100%;
  min-width: 0;
}


.logo-upload-content {
  flex: 1;
  min-width: 0;
}

/* 
   IMAGE PREVIEW
 */

.logo-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: start;
  gap: 24px;
  width: 100%;
  max-width: 100%;
}

.asset-row {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 16px;
  padding: 18px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #fff;
  width: 100%;
  flex-wrap: wrap;
}

.logo-preview-wrapper {
  flex: 0 0 auto;
  width: 100%;
  display: flex;
  justify-content: flex-start;
  align-items: center;
}


.logo-preview,
.logo-placeholder {
  width: 140px;
  height: 140px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  object-fit: cover;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-upload-content {
  flex: 1;
  min-width: 240px;
  display: flex;
  flex-direction: column;
}


.action-row {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 10px;
}

/* 
   TEXT STYLES
 */
.upload-title {
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
}

.upload-description {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

/* 
   BUTTONS
 */
.upload-btn,
.remove-btn {
  min-height: 44px;
  padding: 0 18px;
  border-radius: 12px;
  border: none;

  font-weight: 700;
  display: inline-flex;
  align-items: center;

  cursor: pointer;
  transition: all 0.2s ease;

  white-space: nowrap;
}

.upload-btn {
  background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
  color: #ffffff;
}

.upload-btn:hover {
  transform: translateY(-1px);
}

.remove-btn {
  background: #fff1f2;
  color: #e11d48;
}

.remove-btn:hover {
  background: #ffe4e6;
}

/* INPUTS*/
.form-control-modern {
  width: 100%;
  min-height: 46px;

  border: 1px solid #d9e0e8;
  border-radius: 12px;

  padding: 0 14px;
  color: #334155;

  outline: none;
  box-sizing: border-box;
}

.form-control-modern:focus {
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.13);
}

/* SWITCH*/
.switch-row {
  min-height: 46px;
  display: flex;
  align-items: center;
  gap: 10px;

  padding: 0 14px;
  border: 1px solid #d9e0e8;
  border-radius: 12px;

  font-weight: 800;
  color: #334155;
}

/* ERROR + LOADING */
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

/* SWEETALERT TOAST */
:deep(.logo-swal-toast) {
  border-radius: 12px;
  padding: 12px 14px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
}

:deep(.logo-swal-toast__title) {
  font-size: 14px;
  font-weight: 800;
  color: #0f172a;
}

@media (max-width: 1066px) {

  .logo-grid {
    grid-template-columns: 1fr;
  }
}

/*RESPONSIVE DESIGN */
@media (max-width: 846px) {

  .logo-preview,
  .logo-placeholder {
    width: 110px;
    height: 110px;
  }

  .logo-upload-content {
    width: 100%;
  }

  .upload-btn,
  .remove-btn {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 768px) {

  .form-grid,
  .logo-grid {
    grid-template-columns: 1fr;
  }

  .asset-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .logo-preview-wrapper {
    width: 100%;
    justify-content: flex-start;
  }

  .logo-preview,
  .logo-placeholder {
    width: 110px;
    height: 110px;
  }

  .logo-upload-content {
    width: 100%;
    min-width: 0;
  }

  .upload-btn,
  .remove-btn {
    width: 100%;
    justify-content: center;
  }
}

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

@media (max-width: 480px) {

  .logo-preview,
  .logo-placeholder {
    width: 90px;
    height: 90px;
  }

  .asset-row {
    padding: 14px;
  }

  .upload-title {
    font-size: 16px;
  }

  .upload-description {
    font-size: 13px;
  }
}
</style>
