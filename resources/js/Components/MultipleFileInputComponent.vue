<template>
  <div :class="parentCls">
    <div class="mf" :class="{ 'is-dragover': dragOver }"
      @dragover.prevent="dragOver = true"
      @dragleave.prevent="dragOver = false"
      @drop.prevent="onDrop"
    >
      <div class="mf-grid" :style="{ '--tile': tileSize + 'px', '--accent': accentColor }">
        <!-- Add tile -->
        <button v-if="canAddMore" type="button" class="mf-tile mf-add"
          @click="openPicker"
          @keydown.enter.prevent="openPicker"
          @keydown.space.prevent="openPicker"
          aria-label="Add images"
        >
          <div class="mf-plus">+</div>
          <div class="mf-count">{{ totalCount }}/{{ maxFiles }}</div>
        </button>

        <!-- Existing (initial) -->
        <div v-for="(u, i) in initialLocal" :key="'init-' + i + '-' + u" class="mf-tile">
          <img :src="u" class="mf-img" alt="Image" />
          <button type="button" class="mf-remove" @click.stop="removeInitial(i)" title="Remove">✕</button>
        </div>

        <!-- New (selected) -->
        <div v-for="(u, i) in previewUrls" :key="'new-' + i + '-' + u" class="mf-tile">
          <img :src="u" class="mf-img" alt="Image" />
          <button type="button" class="mf-remove" @click.stop="removeNew(i)" title="Remove">✕</button>
        </div>
      </div>

      <div v-if="rejections.length" class="mt-2 d-grid gap-2">
        <div v-for="(r, i) in rejections" :key="i" class="alert alert-danger py-1 px-2 mb-0" role="alert">
          {{ r }}
        </div>
      </div>
    </div>

    <input
      ref="fileInput"
      type="file"
      class="visually-hidden"
      :id="id"
      multiple
      :required="isRequired"
      :accept="accept"
      @change="onChange"
    />
  </div>
</template>

<script>
export default {
  name: "MultipleFileInputComponent",
  emits: ["update:modelValue", "change", "invalid", "remove-initial"],
  props: {
    id: { type: String, required: true },
    accept: { type: String, default: "image/*" },
    initialUrls: { type: [String, Array], default: "" },
    modelValue: { type: Array, default: () => [] },

    maxFiles: { type: Number, default: 12 },
    maxSizeMB: { type: Number, default: 10 },

    parentCls: { type: String, default: "" },
    isRequired: { type: Boolean, default: false },

    // ✅ compact tiles
    tileSize: { type: Number, default: 96 },
    accentColor: { type: String, default: "#5C2D80" },
  },

  data() {
    return { dragOver: false, filesList: [], objectUrls: [], previewUrls: [], rejections: [], initialLocal: [] };
  },

  computed: {
    totalCount() { return this.initialLocal.length + this.filesList.length; },
    canAddMore() { return this.totalCount < this.maxFiles; },
  },

  mounted() { this.hydrateInitial(); this.syncFromModel(this.modelValue); },
  beforeUnmount() { this.cleanupObjectUrls(); },

  watch: {
    modelValue(val) { this.syncFromModel(val); },
    initialUrls() { this.hydrateInitial(); },
  },

  methods: {
    hydrateInitial() {
      const arr = Array.isArray(this.initialUrls) ? this.initialUrls : (this.initialUrls ? [this.initialUrls] : []);
      this.initialLocal = arr.filter(Boolean);
    },

    openPicker() { this.$refs.fileInput?.click(); },
    onChange(e) { this.addFiles(e.target.files); this.dragOver = false; if (this.$refs.fileInput) this.$refs.fileInput.value = ""; },
    onDrop(e) { this.addFiles(e.dataTransfer?.files); this.dragOver = false; },

    addFiles(fileList) {
      if (!fileList?.length) return;

      const incoming = Array.from(fileList);
      const { valid, errors } = this.validateFiles(incoming);

      const availableSlots = Math.max(0, this.maxFiles - this.totalCount);
      const trimmed = valid.slice(0, availableSlots);
      if (valid.length > trimmed.length) errors.push(`You can select up to ${this.maxFiles} images.`);

      this.rejections = errors;
      if (errors.length) this.$emit("invalid", errors);
      if (!trimmed.length) return;

      this.filesList = [...this.filesList, ...trimmed];
      this.rebuildPreviews();
      this.emitModel();
      this.$emit("change", this.filesList);
    },

    removeInitial(index) {
      const removedUrl = this.initialLocal[index];
      this.initialLocal.splice(index, 1);
      this.$emit("remove-initial", removedUrl, index); // ✅ parent can map index -> media id
    },

    removeNew(index) {
      this.filesList.splice(index, 1);
      this.rebuildPreviews();
      this.emitModel();
    },

    emitModel() { this.$emit("update:modelValue", this.filesList); },

    rebuildPreviews() {
      this.cleanupObjectUrls();
      this.previewUrls = this.filesList.map((f) => this.makeUrl(f));
    },

    makeUrl(file) {
      const url = URL.createObjectURL(file);
      this.objectUrls.push(url);
      return url;
    },

    cleanupObjectUrls() {
      for (const u of this.objectUrls) URL.revokeObjectURL(u);
      this.objectUrls = [];
    },

    syncFromModel(val) {
      const arr = Array.isArray(val) ? val : (val ? [val] : []);
      this.filesList = arr.filter((f) => f && typeof f === "object" && "name" in f);
      this.rebuildPreviews();
    },

    validateFiles(files) {
      const maxBytes = this.maxSizeMB * 1024 * 1024;
      const out = [];
      const errs = [];

      for (const f of files) {
        if (f.size > maxBytes) { errs.push(`${f.name} is larger than ${this.maxSizeMB}MB.`); continue; }
        if (!String(f.type || "").startsWith("image/")) { errs.push(`${f.name} is not an image.`); continue; }
        out.push(f);
      }

      return { valid: out, errors: errs };
    },
  },
};
</script>

<style scoped>
.mf{
  border: 1px solid rgba(20,20,20,.08);
  border-radius: 14px;
  padding: 12px;
  background: #fff;
  
}

.mf.is-dragover{
  border-color: rgba(92,45,128,.35);
  box-shadow: 0 0 0 .22rem rgba(92,45,128,.16);
}

.mf-grid{
  --tile: 96px;
  --accent: #5C2D80;

  display: grid;
  grid-template-columns: repeat(auto-fill, var(--tile));
  gap: 10px;
  justify-content: start; /* keeps tiles small */
}

.mf-tile{
  width: var(--tile);
  height: var(--tile);
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(20,20,20,.08);
  background: #f8fafc;
}

.mf-img{ width:100%; height:100%; object-fit:cover; display:block; }

.mf-add{
  border: 2px dashed rgba(92,45,128,.35);
  background: linear-gradient(180deg, rgba(92,45,128,.05), rgba(92,45,128,.015));
  cursor: pointer;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap: 4px;
  transition: transform .12s ease, border-color .12s ease, box-shadow .12s ease;
}

.mf-add:hover{
  transform: translateY(-1px);
  border-color: rgba(92,45,128,.65);
  box-shadow: 0 10px 18px rgba(0,0,0,.06);
}

.mf-plus{
  width: 34px; height: 34px;
  border-radius: 9999px;
  display:grid; place-items:center;
  background: rgba(92,45,128,.10);
  color: var(--accent);
  font-size: 22px;
  font-weight: 900;
}

.mf-count{ font-size:.78rem; color:#6b7280; font-weight:600; }

.mf-remove{
  position:absolute;
  top: 6px;
  right: 6px;
  width: 26px;
  height: 26px;
  border: 0;
  border-radius: 9999px;
  background: rgba(0,0,0,.65);
  color: #fff;
  display:grid; place-items:center;
  cursor:pointer;
  transition: transform .12s ease, background .12s ease;
}
.mf-remove:hover{ transform: translateY(-1px); background: rgba(0,0,0,.82); }
</style>
