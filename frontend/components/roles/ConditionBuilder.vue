<template>
  <div class="condition-builder">
    <div class="mb-4">
      <h3 class="text-lg font-medium text-gray-900">條件限制</h3>
      <p class="mt-1 text-sm text-gray-500">設定角色的存取範圍限制（選填）</p>
    </div>

    <!-- Conditions list -->
    <div class="space-y-4">
      <div
        v-for="(condition, index) in conditions"
        :key="index"
        :data-testid="`condition-row-${index}`"
        class="border border-gray-200 rounded-lg p-4 bg-gray-50"
      >
        <div class="grid grid-cols-12 gap-4">
          <!-- Condition Type -->
          <div class="col-span-12 sm:col-span-3">
            <label :for="`condition-type-${index}`" class="block text-sm font-medium text-gray-700 mb-1">
              條件類型
            </label>
            <select
              :id="`condition-type-${index}`"
              v-model="condition.condition_type"
              :data-testid="`condition-type-select-${index}`"
              @change="handleConditionTypeChange(index)"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="">選擇類型</option>
              <option value="department">部門</option>
              <option value="region">區域</option>
              <option value="customer_group">客戶分群</option>
              <option value="order_status">訂單狀態</option>
              <option value="amount_range">金額範圍</option>
            </select>
          </div>

          <!-- Operator -->
          <div class="col-span-12 sm:col-span-3">
            <label :for="`condition-operator-${index}`" class="block text-sm font-medium text-gray-700 mb-1">
              運算子
            </label>
            <select
              :id="`condition-operator-${index}`"
              v-model="condition.operator"
              :data-testid="`condition-operator-select-${index}`"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="">選擇運算子</option>
              <option value="equals">等於</option>
              <option value="not_equals">不等於</option>
              <option value="in">包含於</option>
              <option value="not_in">不包含於</option>
              <option v-if="isNumericType(condition.condition_type)" value="greater_than">大於</option>
              <option v-if="isNumericType(condition.condition_type)" value="less_than">小於</option>
              <option v-if="isNumericType(condition.condition_type)" value="between">介於</option>
            </select>
          </div>

          <!-- Value -->
          <div class="col-span-12 sm:col-span-5">
            <label :for="`condition-value-${index}`" class="block text-sm font-medium text-gray-700 mb-1">
              條件值
            </label>
            <input
              :id="`condition-value-${index}`"
              v-model="condition.valueInput"
              :data-testid="`condition-value-input-${index}`"
              type="text"
              :placeholder="getValuePlaceholder(condition.condition_type, condition.operator)"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
            <p class="mt-1 text-xs text-gray-500">
              {{ getValueHint(condition.operator) }}
            </p>
          </div>

          <!-- Remove button -->
          <div class="col-span-12 sm:col-span-1 flex items-end">
            <button
              type="button"
              :data-testid="`remove-condition-button-${index}`"
              @click="removeCondition(index)"
              class="w-full sm:w-auto px-3 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-md hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              移除
            </button>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="conditions.length === 0" class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
        <p class="text-sm text-gray-500">尚未設定任何條件限制</p>
        <p class="text-xs text-gray-400 mt-1">點擊下方按鈕新增條件</p>
      </div>

      <!-- Add button -->
      <div>
        <button
          type="button"
          data-testid="add-condition-button"
          @click="addCondition"
          class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-md hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          + 新增條件
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { ConditionRule } from '~/stores/roles'

interface ConditionInput {
  condition_type: string
  operator: string
  valueInput: string
  field_name?: string
}

interface Props {
  modelValue: ConditionRule[]
}

interface Emits {
  (e: 'update:modelValue', value: ConditionRule[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const conditions = ref<ConditionInput[]>([])

// Watch for external changes
watch(
  () => props.modelValue,
  (newValue) => {
    conditions.value = newValue.map(rule => ({
      condition_type: rule.condition_type,
      operator: rule.operator,
      valueInput: formatValueForInput(rule.condition_value, rule.operator),
      field_name: rule.field_name,
    }))
  },
  { immediate: true }
)

// Emit changes
watch(
  conditions,
  (newConditions) => {
    const rules: ConditionRule[] = newConditions
      .filter(c => c.condition_type && c.operator && c.valueInput)
      .map(c => ({
        condition_type: c.condition_type as any,
        operator: c.operator as any,
        condition_value: parseValueInput(c.valueInput, c.operator),
        field_name: c.field_name || c.condition_type,
      }))

    emit('update:modelValue', rules)
  },
  { deep: true }
)

/**
 * Add a new condition
 */
const addCondition = () => {
  conditions.value.push({
    condition_type: '',
    operator: '',
    valueInput: '',
  })
}

/**
 * Remove a condition
 */
const removeCondition = (index: number) => {
  conditions.value.splice(index, 1)
}

/**
 * Handle condition type change
 */
const handleConditionTypeChange = (index: number) => {
  // Reset operator if it's not compatible with the new type
  const condition = conditions.value[index]
  const numericOperators = ['greater_than', 'less_than', 'between']

  if (!isNumericType(condition.condition_type) && numericOperators.includes(condition.operator)) {
    condition.operator = ''
  }
}

/**
 * Check if condition type is numeric
 */
const isNumericType = (type: string): boolean => {
  return type === 'amount_range'
}

/**
 * Get value placeholder
 */
const getValuePlaceholder = (type: string, operator: string): string => {
  if (operator === 'between') {
    return '例如: 1000,5000'
  }
  if (operator === 'in' || operator === 'not_in') {
    return '例如: 華東,華南,華北'
  }

  switch (type) {
    case 'department':
      return '例如: 業務部'
    case 'region':
      return '例如: 華東'
    case 'customer_group':
      return '例如: VIP'
    case 'order_status':
      return '例如: 已完成'
    case 'amount_range':
      return '例如: 10000'
    default:
      return '請輸入條件值'
  }
}

/**
 * Get value hint
 */
const getValueHint = (operator: string): string => {
  if (operator === 'between') {
    return '使用逗號分隔最小值和最大值'
  }
  if (operator === 'in' || operator === 'not_in') {
    return '使用逗號分隔多個值'
  }
  return ''
}

/**
 * Parse value input to condition value object
 */
const parseValueInput = (input: string, operator: string): any => {
  if (!input) return null

  if (operator === 'between') {
    const parts = input.split(',').map(s => s.trim())
    if (parts.length === 2) {
      return { min: parts[0], max: parts[1] }
    }
  } else if (operator === 'in' || operator === 'not_in') {
    const values = input.split(',').map(s => s.trim()).filter(Boolean)
    return { values }
  } else {
    return { value: input.trim() }
  }

  return { value: input }
}

/**
 * Format condition value for input display
 */
const formatValueForInput = (value: any, operator: string): string => {
  if (!value) return ''

  if (operator === 'between') {
    if (value.min && value.max) {
      return `${value.min},${value.max}`
    }
  } else if (operator === 'in' || operator === 'not_in') {
    if (value.values && Array.isArray(value.values)) {
      return value.values.join(',')
    }
  } else if (value.value) {
    return value.value
  }

  return ''
}
</script>

<style scoped>
.condition-builder {
  @apply w-full;
}
</style>
