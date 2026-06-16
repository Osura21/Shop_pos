<template>
  <div class="mv-list">
    <input class="mv-miniInput" v-model="search" placeholder="Search models..." />

    <label v-for="m in filtered" :key="m.id" class="mv-check">
      <input class="mv-check__in" type="checkbox" :value="String(m.id)" v-model="form.model" @change="apply" />
      <span class="mv-check__box">
        <i class="fa-solid fa-check mv-check__icon"></i>
      </span>
      <span class="mv-check__txt">{{ m.title }}</span>
      <span class="mv-check__count" v-if="m.vehicles_count">
        {{ m.vehicles_count }}
      </span>
    </label>

    <div v-if="!filtered.length" class="mv-smallEmpty">No models</div>
  </div>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
  name: "Models",
  data() {
    return {
      search: "",
      form: { model: [] },
    };
  },
  computed: {
    list() {
      return this.$page.props.models || [];
    },
    filtered() {
      const s = this.search.trim().toLowerCase();
      if (!s) return this.list;
      return this.list.filter((x) => (x.title || "").toLowerCase().includes(s));
    },
  },
  mounted() {
    this.setFormData();
  },
  methods: {
    setFormData() {
      const q = this.$page.props.requestQuery || {};
      if (q.hasOwnProperty("model")) this.form.model = Array.isArray(q.model) ? q.model.map(String) : [String(q.model)];
    },
    apply() {
      const q = this.$page.props.requestQuery || {};
      router.get(window.location.pathname, { ...q, page: 1, model: this.form.model }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => this.setFormData(),
      });
    },
  },
};
</script>

<style scoped>
.mv-list { 
  display: grid; 
  gap: 10px; 
  max-height: 300px;
  overflow-y: auto;
  padding-right: 4px;
}

.mv-list::-webkit-scrollbar {
  width: 6px;
}

.mv-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.mv-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.mv-list::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

.mv-miniInput {
  border-radius: 12px;
  border: 1px solid rgba(0,0,0,0.12);
  padding: 10px 12px;
  font-weight: 800;
  font-size: 13px;
  outline: none;
  width: 100%;
}
.mv-miniInput:focus {
  border-color: rgba(92,45,128,0.45);
  box-shadow: 0 0 0 0.2rem rgba(92,45,128,0.12);
}

.mv-check { 
  display: grid; 
  grid-template-columns: 20px 1fr auto; 
  gap: 12px; 
  align-items: center; 
  cursor: pointer;
  transition: all 0.2s ease;
}
.mv-check__in { 
  display: none; 
}

.mv-check__box {
  width: 20px; 
  height: 20px; 
  border-radius: 6px;
  border: 2px solid rgba(0,0,0,0.18);
  background: #fff;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.mv-check__icon {
  color: white;
  font-size: 10px;
  opacity: 0;
  transform: scale(0.5);
  transition: all 0.2s ease;
}

.mv-check__in:checked + .mv-check__box {
  border-color: #5c2d80;
  background: linear-gradient(135deg, #332e78, #5c2d80);
  box-shadow: 0 6px 14px rgba(92,45,128,0.22);
}

.mv-check__in:checked + .mv-check__box .mv-check__icon {
  opacity: 1;
  transform: scale(1);
}

.mv-check__txt { 
  font-weight: 900; 
  font-size: 13px; 
  color: #111827; 
}

.mv-check__count {
  font-weight: 900;
  font-size: 12px;
  color: #6b7280;
  background: #e5e7eb;
  padding: 4px 8px;
  border-radius: 20px;
  min-width: 30px;
  text-align: center;
}

.mv-smallEmpty { 
  font-weight: 800; 
  font-size: 12px; 
  color: #6b7280; 
  padding: 12px 0;
  text-align: center;
  grid-column: 1 / -1;
}

@media (max-width: 576px) {
  .mv-check__txt {
    font-size: 14px;
  }
  
  .mv-check__box {
    width: 22px;
    height: 22px;
  }
  
  .mv-check__icon {
    font-size: 11px;
  }
  
  .mv-list {
    max-height: 50vh;
  }
}
</style>