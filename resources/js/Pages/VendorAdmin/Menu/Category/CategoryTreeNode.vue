<template>
  <div class="category-node">
    <div
      class="category-node__row"
      :class="{ 'category-node__row--active': node.id === selectedId }"
      @click="$emit('select', node.id)"
    >
      <button
        v-if="hasChildren"
        type="button"
        class="category-node__toggle"
        @click.stop="$emit('toggle', node.id)"
      >
        <i class="bi" :class="isExpanded ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
      </button>

      <span v-else class="category-node__toggle-spacer"></span>

      <i class="bi bi-folder2-open category-node__folder"></i>

      <span class="category-node__label">{{ node.name }}</span>

      <span
        v-if="!node.is_active"
        class="category-node__status"
      >
        Inactive
      </span>
    </div>

    <div
      v-if="hasChildren && isExpanded"
      class="category-node__children"
    >
      <CategoryTreeNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :selected-id="selectedId"
        :expanded-ids="expandedIds"
        @select="$emit('select', $event)"
        @toggle="$emit('toggle', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({
  name: 'CategoryTreeNode',
})

const props = defineProps({
  node: {
    type: Object,
    required: true,
  },
  selectedId: {
    type: [Number, String, null],
    default: null,
  },
  expandedIds: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['select', 'toggle'])

const hasChildren = computed(() => Array.isArray(props.node.children) && props.node.children.length > 0)
const isExpanded = computed(() => props.expandedIds.includes(props.node.id))
</script>

<style scoped>
/* Root node */
.category-node {
  display: block;
  margin: 3px 0;
}

/* Row */
.category-node__row {
  display: flex;
  align-items: center;
  gap: 10px;
  border-radius: 10px;
  padding: 6px 10px;
  cursor: pointer;
  color: #475569;
  transition: all 0.2s ease;
  position: relative;
}

/* Hover */
.category-node__row:hover {
  color: #f97316;
}

/* Active */
.category-node__row--active {
  color: #ea580c;
  font-weight: 600;
}

.category-node__row--active::before {
  content: "";
  position: absolute;
  left: -8px;
  top: 6px;
  bottom: 6px;
  width: 3px;
  border-radius: 10px;
  background: linear-gradient(180deg, #f97316, #fb923c);
}

/* Toggle button */
.category-node__toggle {
  width: 20px;
  height: 20px;
  border: none;
  background: transparent;
  color: #94a3b8;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.category-node__toggle:hover {
  background: rgba(148, 163, 184, 0.15);
  color: #334155;
}

/* Spacer */
.category-node__toggle-spacer {
  width: 20px;
  height: 20px;
  display: inline-block;
}

/* Folder icon */
.category-node__folder {
  color: #f59e0b;
  font-size: 16px;
  transition: transform 0.2s ease;
}

.category-node__row:hover .category-node__folder {
  transform: scale(1.1);
}

/* Label */
.category-node__label {
  font-size: 14px;
  line-height: 1.3;
  flex: 1;
  white-space: nowrap;
  text-overflow: ellipsis;
}

/* Status badge */
.category-node__status {
  font-size: 11px;
  padding: 3px 8px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #64748b;
  font-weight: 600;
}

/* Children container */
.category-node__children {
  margin-left: 20px;
  padding-left: 12px;
  border-left: 1px dashed rgba(148, 163, 184, 0.3);
  position: relative;
}

/* Subtle vertical guide improvement */
.category-node__children::before {
  content: "";
  position: absolute;
  left: -1px;
  top: 0;
  bottom: 0;
  width: 1px;
  background: linear-gradient(to bottom, rgba(148,163,184,0.4), transparent);
}

</style>