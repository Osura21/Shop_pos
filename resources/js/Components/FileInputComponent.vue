<template>
  <div :class="parentCls">
    <div
      class="fi"
      :class="{ 'is-dragover': dragOver, 'is-disabled': disabled }"
      :style="{
        '--accent': accentColor,
        '--w': width + 'px',
        '--h': height + 'px'
      }"
      role="button"
      tabindex="0"
      :aria-label="ariaLabel"
      @click="openPicker"
      @keydown.enter.prevent="openPicker"
      @keydown.space.prevent="openPicker"
      @dragenter.prevent="onDragEnter"
      @dragover.prevent="onDragOver"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
    >
      <img
        v-if="currentPreview"
        :src="currentPreview"
        class="fi-img"
        alt="Preview"
        @error="onPreviewError"
      />

      <div v-else class="fi-empty">
        <div class="fi-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M4 16V8a2 2 0 0 1 2-2h3l1.2-1.7A2 2 0 0 1 11.8 4h.4a2 2 0 0 1 1.6.8L15 6h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/>
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 10.5a3 3 0 1 0 0 6a3 3 0 0 0 0-6Z"/>
          </svg>
        </div>
        <div class="fi-title">{{ placeholderText }}</div>
        <div class="fi-sub">Click / Drop</div>
      </div>

      <div v-if="(currentPreview || file) && !disabled" class="fi-actions">
        <button type="button" class="fi-btn" @click.stop="openPicker" title="Change" aria-label="Change">
          ✎
        </button>
        <button type="button" class="fi-btn danger" @click.stop="clearAll" title="Remove" aria-label="Remove">
          ✕
        </button>
      </div>
    </div>

    <div v-if="rejections.length" class="mt-2 d-grid gap-1">
      <div v-for="(r, i) in rejections" :key="i" class="alert alert-danger py-1 px-2 mb-0" role="alert">
        {{ r }}
      </div>
    </div>

    <input
      ref="fileInput"
      type="file"
      class="visually-hidden"
      :id="id"
      :required="isRequired"
      :accept="accept"
      :disabled="disabled"
      @change="onChange"
    />
  </div>
</template>

<script>
export default {
  name: "FileInputComponent",
  emits: ["update:modelValue", "change", "clear", "invalid"],
  props: {
    id: { type: String, required: true },
    modelValue: { default: null },
    accept: { type: String, default: "image/*" },
    initialUrls: { type: [String, Array], default: "" },
    prvImage: { type: String, default: "" },
    maxSizeMB: { type: Number, default: 10 },
    parentCls: { type: String, default: "" },
    isRequired: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },

    // ✅ compact sizing
    width: { type: Number, default: 160 },
    height: { type: Number, default: 160 },

    accentColor: { type: String, default: "#5C2D80" },
    placeholderText: { type: String, default: "Main image" },
    ariaLabel: { type: String, default: "Upload image" },
  },

  data() {
    return { dragOver: false, file: null, objectUrl: null, previewUrl: "", rejections: [], previewBroken: false };
  },

  computed: {
    _initialUrl() {
      if (Array.isArray(this.initialUrls) && this.initialUrls.length) return this.initialUrls[0] || "";
      if (typeof this.initialUrls === "string" && this.initialUrls) return this.initialUrls;
      if (this.prvImage) return this.prvImage;
      return "";
    },
    currentPreview() {
      if (this.previewBroken) return "";
      return this.previewUrl || this._initialUrl || "";
    },
  },

  mounted() { this.syncFromModel(this.modelValue); },
  beforeUnmount() { this.cleanupObjectUrl(); },

  watch: { modelValue(val) { this.syncFromModel(val); } },

  methods: {
    openPicker() { if (!this.disabled) this.$refs.fileInput?.click(); },
    onDragEnter() { if (!this.disabled) this.dragOver = true; },
    onDragOver() { if (!this.disabled) this.dragOver = true; },
    onDragLeave() { this.dragOver = false; },

    onChange(e) {
      if (this.disabled) return;
      const list = e?.target?.files;
      if (!list?.length) return;
      this.setFileFromUser(list[list.length - 1]);
      this.dragOver = false;
    },

    onDrop(e) {
      if (this.disabled) return;
      const dt = e.dataTransfer;
      if (dt?.files?.length) this.setFileFromUser(dt.files[dt.files.length - 1]);
      this.dragOver = false;
    },

    clearAll() {
      const input = this.$refs.fileInput;
      if (input) input.value = "";
      this.previewBroken = false;
      this.rejections = [];
      this.setFile(null, { emitUpdate: true });
      this.$emit("clear");
    },

    syncFromModel(val) {
      if (val && typeof val === "object" && "name" in val) this.setFile(val, { emitUpdate: false });
      else this.setFile(null, { emitUpdate: false });
    },

    setFileFromUser(file) {
      this.previewBroken = false;
      this.rejections = [];
      if (!file) return this.setFile(null, { emitUpdate: true });

      const { errors } = this.validateFile(file);
      if (errors.length) {
        const input = this.$refs.fileInput;
        if (input) input.value = "";
        this.cleanupObjectUrl();
        this.file = null;
        this.previewUrl = "";
        this.rejections = errors;
        this.$emit("update:modelValue", null);
        this.$emit("invalid", errors);
        return;
      }

      this.setFile(file, { emitUpdate: true });
      this.$emit("change", file);
    },

    setFile(file, { emitUpdate } = { emitUpdate: true }) {
      this.file = file || null;
      this.rebuildPreview();
      if (emitUpdate) this.$emit("update:modelValue", this.file || null);
    },

    rebuildPreview() {
      this.cleanupObjectUrl();
      if (!this.file) return (this.previewUrl = "");
      if ((this.file.type || "").startsWith("image/")) {
        this.objectUrl = URL.createObjectURL(this.file);
        this.previewUrl = this.objectUrl;
      } else {
        this.previewUrl = "";
      }
    },

    cleanupObjectUrl() {
      if (this.objectUrl) URL.revokeObjectURL(this.objectUrl);
      this.objectUrl = null;
    },

    onPreviewError() { this.previewBroken = true; },

    validateFile(file) {
      const maxBytes = this.maxSizeMB * 1024 * 1024;
      const errs = [];
      if (file.size > maxBytes) errs.push(`${file.name} is larger than ${this.maxSizeMB}MB.`);
      return { errors: errs };
    },
  },
};
</script>

<style scoped>
.fi{
  --accent:#5C2D80;
  --w:160px;
  --h:160px;

  width: var(--w);
  height: var(--h);
  border-radius: 14px;
  border: 1px solid rgba(92,45,128,.18);
  background: linear-gradient(180deg, rgba(92,45,128,.05), rgba(92,45,128,.015));
  position: relative;
  overflow: hidden;
  cursor: pointer;
  transition: box-shadow .15s ease, transform .15s ease, border-color .15s ease;
}

.fi:hover{
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(0,0,0,.06);
  border-color: rgba(92,45,128,.35);
}

.fi.is-dragover{
  border-color: var(--accent);
  box-shadow: 0 0 0 .22rem rgba(92,45,128,.18);
}

.fi.is-disabled{ opacity:.6; cursor:not-allowed; }

.fi-img{ width:100%; height:100%; object-fit:cover; display:block; }

.fi-empty{
  width:100%; height:100%;
  display:flex; flex-direction:column;
  align-items:center; justify-content:center;
  text-align:center;
  gap:6px;
  color:#6b7280;
  padding: 10px;
}

.fi-icon{
  width: 34px; height:34px;
  border-radius: 9999px;
  display:grid; place-items:center;
  background: rgba(92,45,128,.10);
  color: var(--accent);
}
.fi-icon svg{ width:18px; height:18px; }

.fi-title{ font-weight:700; color:#111827; font-size:.9rem; }
.fi-sub{ font-size:.8rem; color:#6b7280; }

.fi-actions{
  position:absolute;
  top: 8px; right: 8px;
  display:flex; gap:6px;
}

.fi-btn{
  width: 28px; height: 28px;
  border-radius: 9999px;
  border: 0;
  background: rgba(0,0,0,.55);
  color:#fff;
  display:grid; place-items:center;
  cursor:pointer;
  transition: transform .12s ease, background .12s ease;
  font-weight: 800;
}
.fi-btn:hover{ transform: translateY(-1px); background: rgba(0,0,0,.75); }
.fi-btn.danger{ background: rgba(220,38,38,.75); }
.fi-btn.danger:hover{ background: rgba(220,38,38,.9); }
</style>
