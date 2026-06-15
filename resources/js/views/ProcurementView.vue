<script setup>
import { ref, onMounted } from 'vue';
import { usePosStore } from '../stores/usePosStore';
import POBuilderModal from '../components/POBuilderModal.vue';

const store = usePosStore();
const showPOModal = ref(false);

async function receivePO(poId) {
    await store.receivePO(poId);
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

onMounted(async () => {
    await store.loadProcurement();
});
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100">
        <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-5 shrink-0">
            <h1 class="text-xl font-bold tracking-tight">Procurement</h1>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="store.isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ store.isOnline ? 'Online' : 'Offline' }}
                </span>
                <button @click="showPOModal = true"
                    class="px-4 py-1.5 rounded-lg bg-blue-600 text-white font-bold text-xs
                           hover:bg-blue-700 transition-all cursor-pointer">
                    + New PO
                </button>
            </div>
        </header>

        <div v-if="store.purchaseOrders.length === 0"
            class="flex-1 flex items-center justify-center text-gray-400 text-sm">
            No purchase orders yet
        </div>

        <div v-else class="flex-1 overflow-y-auto p-4">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="po in store.purchaseOrders" :key="po.id"
                            class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(po.created_at) }}</td>
                            <td class="px-4 py-3 font-medium">
                                {{ store.suppliers.find(s => s.id === po.supplier_id)?.name || 'Unknown' }}
                            </td>
                            <td class="px-4 py-3">{{ (po.items || []).length }}</td>
                            <td class="px-4 py-3 font-semibold">${{ parseFloat(po.total_amount).toFixed(2) }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                    :class="po.status === 'received'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-amber-100 text-amber-700'">
                                    {{ po.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="po.status === 'pending'"
                                    @click="receivePO(po.id)"
                                    class="px-3 py-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-xs font-medium text-emerald-700 transition-colors cursor-pointer">
                                    Receive
                                </button>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <POBuilderModal v-if="showPOModal" @close="showPOModal = false" />
</template>
