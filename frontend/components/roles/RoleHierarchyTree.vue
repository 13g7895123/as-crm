<template>
  <div class="role-hierarchy-tree">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">角色階層結構</h3>

      <!-- Tree View -->
      <div v-if="!loading && hierarchyTree.length > 0" class="space-y-2">
        <RoleNode
          v-for="node in hierarchyTree"
          :key="node.id"
          :node="node"
          :current-role-id="currentRoleId"
          :selected-parent-id="selectedParentId"
          :expanded-nodes="expandedNodes"
          @select="handleSelect"
          @toggle="handleToggle"
        />
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading && hierarchyTree.length === 0" class="text-center py-8 text-gray-500">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <p class="text-sm">尚無角色階層</p>
      </div>

      <!-- Loading State -->
      <div v-else class="flex items-center justify-center py-12">
        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent"></div>
        <p class="ml-3 text-sm text-gray-500">載入角色階層中...</p>
      </div>

      <!-- Actions -->
      <div v-if="!loading && hierarchyTree.length > 0" class="mt-6 pt-6 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            <span v-if="selectedParentId">
              已選擇父角色: <strong>{{ getSelectedParentName() }}</strong>
            </span>
            <span v-else>請選擇父角色(點擊角色名稱)</span>
          </div>

          <div class="flex space-x-2">
            <button
              v-if="selectedParentId"
              @click="clearSelection"
              class="btn-secondary"
            >
              清除選擇
            </button>
            <button
              v-if="allowNoParent"
              @click="selectNone"
              class="btn-secondary"
            >
              設為頂層角色
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Hierarchy Info -->
    <div v-if="currentRoleInfo && !loading" class="mt-4 bg-blue-50 rounded-lg border border-blue-200 p-4">
      <h4 class="text-sm font-medium text-blue-900 mb-2">目前角色資訊</h4>
      <div class="text-sm text-blue-800 space-y-1">
        <div v-if="currentRoleInfo.parent_id">
          <strong>父角色:</strong> {{ getParentName(currentRoleInfo.parent_id) }}
        </div>
        <div v-else>
          <strong>父角色:</strong> 無 (頂層角色)
        </div>
        <div v-if="currentRoleInfo.ancestors && currentRoleInfo.ancestors.length > 0">
          <strong>所有上層角色:</strong> {{ getAncestorNames().join(' → ') }}
        </div>
        <div v-if="currentRoleInfo.children && currentRoleInfo.children.length > 0">
          <strong>直屬子角色數:</strong> {{ currentRoleInfo.children.length }}
        </div>
        <div v-if="currentRoleInfo.descendants && currentRoleInfo.descendants.length > 0">
          <strong>所有下層角色數:</strong> {{ currentRoleInfo.descendants.length }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, defineComponent, h } from 'vue'
import { useRuntimeConfig } from '#app'

interface RoleNode {
  id: number
  name: string
  display_name: string
  description?: string
  children?: RoleNode[]
  parent_id?: number | null
}

interface RoleHierarchyInfo {
  role: any
  parent_id: number | null
  ancestors: any[]
  children: number[]
  descendants: any[]
  depth: number
}

interface Props {
  currentRoleId?: number | null  // The role being edited (should not be selectable as parent)
  selectedParentId?: number | null  // Initially selected parent
  allowNoParent?: boolean  // Allow selecting "no parent" (top-level role)
}

interface Emits {
  (e: 'parent-selected', parentId: number | null): void
}

const props = withDefaults(defineProps<Props>(), {
  currentRoleId: null,
  selectedParentId: null,
  allowNoParent: true,
})

const emit = defineEmits<Emits>()
const config = useRuntimeConfig()

const loading = ref(true)
const hierarchyTree = ref<RoleNode[]>([])
const currentRoleInfo = ref<RoleHierarchyInfo | null>(null)
const expandedNodes = ref<Set<number>>(new Set())
const selectedParentId = ref<number | null>(props.selectedParentId)

// Watch for prop changes
watch(() => props.selectedParentId, (newValue) => {
  selectedParentId.value = newValue
})

/**
 * Load hierarchy tree
 */
const loadHierarchy = async () => {
  loading.value = true

  try {
    const apiBaseUrl = config.public.apiBaseUrl

    // Fetch hierarchy tree from API
    const response = await fetch(`${apiBaseUrl}/roles/hierarchy`)
    const data = await response.json()

    hierarchyTree.value = data.data || []

    // Load current role info if provided
    if (props.currentRoleId) {
      const roleResponse = await fetch(`${apiBaseUrl}/roles/${props.currentRoleId}/hierarchy`)
      const roleData = await roleResponse.json()
      currentRoleInfo.value = roleData.data || null

      // Auto-expand path to current role
      if (currentRoleInfo.value?.ancestors) {
        currentRoleInfo.value.ancestors.forEach((ancestor: any) => {
          expandedNodes.value.add(ancestor.id)
        })
      }
    }
  } catch (error: any) {
    console.error('Failed to load hierarchy:', error)
  } finally {
    loading.value = false
  }
}

/**
 * Handle node selection
 */
const handleSelect = (nodeId: number) => {
  // Prevent selecting current role as its own parent
  if (nodeId === props.currentRoleId) {
    return
  }

  // Prevent selecting descendant as parent (would create circular reference)
  if (currentRoleInfo.value?.descendants) {
    const descendantIds = currentRoleInfo.value.descendants.map((d: any) => d.id)
    if (descendantIds.includes(nodeId)) {
      console.warn('Cannot select descendant role as parent')
      return
    }
  }

  selectedParentId.value = nodeId
  emit('parent-selected', nodeId)
}

/**
 * Handle node toggle (expand/collapse)
 */
const handleToggle = (nodeId: number) => {
  if (expandedNodes.value.has(nodeId)) {
    expandedNodes.value.delete(nodeId)
  } else {
    expandedNodes.value.add(nodeId)
  }
}

/**
 * Clear selection
 */
const clearSelection = () => {
  selectedParentId.value = null
  emit('parent-selected', null)
}

/**
 * Select none (top-level role)
 */
const selectNone = () => {
  selectedParentId.value = null
  emit('parent-selected', null)
}

/**
 * Get selected parent name
 */
const getSelectedParentName = (): string => {
  if (!selectedParentId.value) return ''

  const findNode = (nodes: RoleNode[]): RoleNode | null => {
    for (const node of nodes) {
      if (node.id === selectedParentId.value) return node
      if (node.children) {
        const found = findNode(node.children)
        if (found) return found
      }
    }
    return null
  }

  const node = findNode(hierarchyTree.value)
  return node?.display_name || node?.name || ''
}

/**
 * Get parent name by ID
 */
const getParentName = (parentId: number): string => {
  const findNode = (nodes: RoleNode[]): RoleNode | null => {
    for (const node of nodes) {
      if (node.id === parentId) return node
      if (node.children) {
        const found = findNode(node.children)
        if (found) return found
      }
    }
    return null
  }

  const node = findNode(hierarchyTree.value)
  return node?.display_name || node?.name || `ID: ${parentId}`
}

/**
 * Get ancestor names
 */
const getAncestorNames = (): string[] => {
  if (!currentRoleInfo.value?.ancestors) return []
  return currentRoleInfo.value.ancestors.map((a: any) => a.display_name || a.name)
}

/**
 * Role Node Component (Recursive)
 */
const RoleNode: any = defineComponent({
  name: 'RoleNode',
  props: {
    node: {
      type: Object as () => RoleNode,
      required: true,
    },
    currentRoleId: {
      type: [Number, null] as unknown as () => number | null,
      default: null,
    },
    selectedParentId: {
      type: [Number, null] as unknown as () => number | null,
      default: null,
    },
    expandedNodes: {
      type: Object as unknown as () => Set<number>,
      required: true,
    },
    level: {
      type: Number,
      default: 0,
    },
  },
  emits: ['select', 'toggle'],
  setup(props, { emit }) {
    const isExpanded = computed(() => props.expandedNodes.has(props.node.id))
    const hasChildren = computed(() => props.node.children && props.node.children.length > 0)
    const isCurrentRole = computed(() => props.node.id === props.currentRoleId)
    const isSelected = computed(() => props.node.id === props.selectedParentId)
    const isDisabled = computed(() => isCurrentRole.value)

    // Calculate indent padding based on level (Tailwind-safe)
    const indentStyle = computed(() => ({
      paddingLeft: `${props.level * 1}rem`
    }))

    const handleClick = () => {
      if (!isDisabled.value) {
        emit('select', props.node.id)
      }
    }

    const handleToggle = () => {
      if (hasChildren.value) {
        emit('toggle', props.node.id)
      }
    }

    return () => {
      return h('div', { class: 'role-node' }, [
        // Node row
        h('div', {
          class: [
            'flex items-center py-2 px-3 rounded-md cursor-pointer transition-colors',
            isSelected.value ? 'bg-indigo-50 border-l-4 border-indigo-600' : '',
            isCurrentRole.value ? 'bg-gray-100 opacity-60 cursor-not-allowed' : 'hover:bg-gray-50',
          ].filter(Boolean).join(' '),
          style: indentStyle.value,
          onClick: handleClick,
        }, [
          // Expand/collapse icon
          h('div', {
            class: 'mr-2 w-4 h-4 flex items-center justify-center',
            onClick: (e: Event) => {
              e.stopPropagation()
              handleToggle()
            },
          }, [
            hasChildren.value
              ? h('svg', {
                  class: `w-4 h-4 text-gray-500 transition-transform ${isExpanded.value ? 'transform rotate-90' : ''}`,
                  fill: 'none',
                  stroke: 'currentColor',
                  viewBox: '0 0 24 24',
                }, [
                  h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': '2',
                    d: 'M9 5l7 7-7 7',
                  }),
                ])
              : null,
          ]),

          // Role icon
          h('svg', {
            class: `w-5 h-5 mr-2 ${isCurrentRole.value ? 'text-gray-400' : 'text-gray-600'}`,
            fill: 'none',
            stroke: 'currentColor',
            viewBox: '0 0 24 24',
          }, [
            h('path', {
              'stroke-linecap': 'round',
              'stroke-linejoin': 'round',
              'stroke-width': '2',
              d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            }),
          ]),

          // Role name
          h('div', { class: 'flex-1' }, [
            h('div', {
              class: `text-sm font-medium ${isCurrentRole.value ? 'text-gray-500' : 'text-gray-900'}`,
            }, props.node.display_name || props.node.name),
            props.node.description
              ? h('div', { class: 'text-xs text-gray-500' }, props.node.description)
              : null,
          ]),

          // Current role badge
          isCurrentRole.value
            ? h('span', {
                class: 'ml-2 px-2 py-0.5 text-xs font-medium bg-gray-200 text-gray-600 rounded',
              }, '目前角色')
            : null,

          // Selected badge
          isSelected.value
            ? h('span', {
                class: 'ml-2 px-2 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 rounded',
              }, '已選擇')
            : null,
        ]),

        // Children
        hasChildren.value && isExpanded.value
          ? h('div', { class: 'ml-4 border-l-2 border-gray-200' },
              props.node.children!.map((child: RoleNode) =>
                h(RoleNode, {
                  key: child.id,
                  node: child,
                  currentRoleId: props.currentRoleId,
                  selectedParentId: props.selectedParentId,
                  expandedNodes: props.expandedNodes,
                  level: props.level + 1,
                  onSelect: (id: number) => emit('select', id),
                  onToggle: (id: number) => emit('toggle', id),
                })
              )
            )
          : null,
      ])
    }
  },
})

// Load on mount
onMounted(() => {
  loadHierarchy()
})
</script>

<style scoped>
.role-hierarchy-tree {
  @apply w-full;
}

.role-node {
  @apply select-none;
}
</style>
