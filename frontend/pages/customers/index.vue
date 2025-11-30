<template>
  <LayoutContentArea title="客戶管理" subtitle="管理客戶資料與互動記錄">
    <template #actions>
      <button
        type="button"
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        新增客戶
      </button>
    </template>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">客戶名稱</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">聯絡人</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="customer in customers" :key="customer.id">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ customer.name }}</div>
              <div class="text-sm text-gray-500">{{ customer.code }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ customer.contact }}</div>
              <div class="text-sm text-gray-500">{{ customer.phone }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ customer.email }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex px-2 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full">
                {{ customer.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3">編輯</a>
              <a href="#" class="text-red-600 hover:text-red-900">刪除</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </LayoutContentArea>
</template>

<script setup lang="ts">
import { ref } from 'vue'

definePageMeta({
  title: '客戶管理',
})

const customers = ref([
  { id: 1, name: '台積電', code: 'TSMC', contact: '張先生', phone: '0912-345-678', email: 'contact@tsmc.com', status: '活躍' },
  { id: 2, name: '聯發科', code: 'MTK', contact: '李小姐', phone: '0923-456-789', email: 'contact@mediatek.com', status: '活躍' },
  { id: 3, name: '鴻海精密', code: 'FOXCONN', contact: '王經理', phone: '0934-567-890', email: 'contact@foxconn.com', status: '活躍' },
])
</script>
