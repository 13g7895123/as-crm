<template>
  <LayoutContentArea title="訂單管理" subtitle="處理訂單與追蹤狀態">
    <template #actions>
      <button
        type="button"
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        建立訂單
      </button>
    </template>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">訂單編號</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">客戶</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日期</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="order in orders" :key="order.id">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-indigo-600">{{ order.orderNo }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ order.customer }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">{{ order.amount }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-500">{{ order.date }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="[
                  'inline-flex px-2 text-xs font-semibold leading-5 rounded-full',
                  order.status === '已完成' ? 'text-green-800 bg-green-100' :
                  order.status === '處理中' ? 'text-blue-800 bg-blue-100' : 'text-gray-800 bg-gray-100'
                ]"
              >
                {{ order.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <a href="#" class="text-indigo-600 hover:text-indigo-900">檢視</a>
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
  title: '訂單管理',
})

const orders = ref([
  { id: 1, orderNo: 'ORD-20231001-001', customer: '台積電', amount: 'NT$ 1,500,000', date: '2023-10-01', status: '已完成' },
  { id: 2, orderNo: 'ORD-20231002-002', customer: '聯發科', amount: 'NT$ 800,000', date: '2023-10-02', status: '處理中' },
  { id: 3, orderNo: 'ORD-20231003-003', customer: '鴻海精密', amount: 'NT$ 2,200,000', date: '2023-10-03', status: '待確認' },
])
</script>
