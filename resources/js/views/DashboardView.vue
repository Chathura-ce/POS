<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePosStore } from '../stores/usePosStore';
import db from '../db';

const store = usePosStore();

const todaySales = ref([]);
const products = ref([]);

const todayStr = new Date().toISOString().slice(0, 10);

const totalSalesToday = computed(() =>
    todaySales.value
        .filter(s => s.status !== 'refunded')
        .reduce((sum, s) => sum + parseFloat(s.total || 0), 0)
);

const totalCashToday = computed(() =>
    todaySales.value
        .filter(s => s.status !== 'refunded' && s.payment_type === 'cash')
        .reduce((sum, s) => sum + parseFloat(s.total || 0), 0)
);

const totalCreditToday = computed(() =>
    todaySales.value
        .filter(s => s.status !== 'refunded' && s.payment_type === 'credit')
        .reduce((sum, s) => sum + parseFloat(s.total || 0), 0)
);

const totalCardToday = computed(() =>
    todaySales.value
        .filter(s => s.status !== 'refunded' && s.payment_type === 'card')
        .reduce((sum, s) => sum + parseFloat(s.total || 0), 0)
);

const saleCountToday = computed(() =>
    todaySales.value.filter(s => s.status !== 'refunded').length
);

const lowStockProducts = computed(() =>
    products.value.filter(p => p.is_active && p.stock_quantity < 5)
);

async function loadDashboard() {
    products.value = await db.products.toArray();

    const allSales = await db.offline_sales.toArray();
    todaySales.value = allSales.filter(s =>
        s.created_at && s.created_at.slice(0, 10) === todayStr
    );
}

onMounted(loadDashboard);
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100">
        <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-5 shrink-0">
            <h1 class="text-xl font-bold tracking-tight">Dashboard</h1>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="store.isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ store.isOnline ? 'Online' : 'Offline' }}
                </span>
                <button @click="loadDashboard"
                    class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs font-medium transition-colors cursor-pointer">
                    Refresh
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Sales Today</p>
                    <p class="text-2xl font-bold mt-1">${{ totalSalesToday.toFixed(2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ saleCountToday }} transactions</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Cash</p>
                    <p class="text-2xl font-bold mt-1 text-emerald-600">${{ totalCashToday.toFixed(2) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Card</p>
                    <p class="text-2xl font-bold mt-1 text-blue-600">${{ totalCardToday.toFixed(2) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Credit Issued</p>
                    <p class="text-2xl font-bold mt-1 text-red-600">${{ totalCreditToday.toFixed(2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h2 class="text-sm font-bold mb-3">Low Stock Alerts</h2>
                <div v-if="lowStockProducts.length === 0" class="text-sm text-gray-400">
                    All products have sufficient stock.
                </div>
                <div v-else class="space-y-2">
                    <div v-for="product in lowStockProducts" :key="product.id"
                        class="flex items-center justify-between bg-red-50 rounded-lg px-3 py-2">
                        <div class="text-sm">
                            <span class="font-medium">{{ product.name }}</span>
                            <span class="text-gray-400 text-xs ml-2">{{ product.sku }}</span>
                        </div>
                        <span class="text-sm font-bold text-red-600">{{ product.stock_quantity }} left</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
