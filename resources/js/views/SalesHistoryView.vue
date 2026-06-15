<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePosStore } from '../stores/usePosStore';
import db from '../db';

const store = usePosStore();

const sales = ref([]);
const search = ref('');

const filteredSales = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return sales.value;
    return sales.value.filter(s =>
        s.id.toLowerCase().includes(q) ||
        (s.customer_id && s.customer_id.toLowerCase().includes(q))
    );
});

async function loadSales() {
    sales.value = await db.offline_sales
        .orderBy('created_at')
        .reverse()
        .toArray();
}

async function voidSale(saleId) {
    if (!confirm('Are you sure you want to void this sale? Stock and credit will be reversed.')) return;
    await store.refundSale(saleId);
    await loadSales();
}

onMounted(loadSales);
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100">
        <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-5 shrink-0">
            <h1 class="text-xl font-bold tracking-tight">Sales History</h1>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="store.isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ store.isOnline ? 'Online' : 'Offline' }}
                </span>
                <button @click="loadSales"
                    class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs font-medium transition-colors cursor-pointer">
                    Refresh
                </button>
            </div>
        </header>

        <div class="p-4">
            <input v-model="search" type="text" placeholder="Search by receipt ID..."
                class="w-full max-w-md px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
        </div>

        <div v-if="filteredSales.length === 0"
            class="flex-1 flex items-center justify-center text-gray-400 text-sm">
            No sales found
        </div>

        <div v-else class="flex-1 overflow-y-auto px-4 pb-4">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Date/Time</th>
                            <th class="px-4 py-3">Receipt #</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="sale in filteredSales" :key="sale.id"
                            class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ new Date(sale.created_at).toLocaleString() }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                {{ sale.id.split('-').pop().toUpperCase() }}
                            </td>
                            <td class="px-4 py-3">{{ sale.items?.length || 0 }}</td>
                            <td class="px-4 py-3 font-semibold">${{ parseFloat(sale.total).toFixed(2) }}</td>
                            <td class="px-4 py-3 capitalize">{{ sale.payment_type }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                    :class="sale.status === 'refunded'
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-emerald-100 text-emerald-700'">
                                    {{ sale.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="sale.status !== 'refunded'" @click="voidSale(sale.id)"
                                    class="px-3 py-1.5 rounded-lg bg-red-100 hover:bg-red-200 text-xs font-medium text-red-700 transition-colors cursor-pointer">
                                    Void
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
