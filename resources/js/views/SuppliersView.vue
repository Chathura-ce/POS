<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePosStore } from '../stores/usePosStore';
import SupplierModal from '../components/SupplierModal.vue';

const store = usePosStore();

const search = ref('');
const showSupplierModal = ref(false);
const editingSupplier = ref(null);

const filteredSuppliers = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return store.suppliers;
    return store.suppliers.filter(s =>
        s.name.toLowerCase().includes(q) ||
        (s.phone && s.phone.includes(q))
    );
});

function openAdd() {
    editingSupplier.value = null;
    showSupplierModal.value = true;
}

function openEdit(supplier) {
    editingSupplier.value = supplier;
    showSupplierModal.value = true;
}

async function saveSupplier(supplier) {
    await store.saveSupplier(supplier);
    showSupplierModal.value = false;
    editingSupplier.value = null;
}

async function paySupplier(supplier) {
    const amount = prompt('Enter payment amount:', parseFloat(supplier.balance).toFixed(2));
    if (!amount || isNaN(amount) || parseFloat(amount) <= 0) return;
    await store.paySupplier(supplier.id, parseFloat(amount));
}

function closeModal() {
    showSupplierModal.value = false;
    editingSupplier.value = null;
}

onMounted(async () => {
    await store.loadProcurement();
});
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100">
        <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-5 shrink-0">
            <h1 class="text-xl font-bold tracking-tight">Suppliers</h1>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="store.isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ store.isOnline ? 'Online' : 'Offline' }}
                </span>
                <button @click="openAdd"
                    class="px-4 py-1.5 rounded-lg bg-blue-600 text-white font-bold text-xs
                           hover:bg-blue-700 transition-all cursor-pointer">
                    + Add Supplier
                </button>
            </div>
        </header>

        <div class="p-4">
            <input v-model="search" type="text" placeholder="Search by name or phone..."
                class="w-full max-w-md px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
        </div>

        <div v-if="filteredSuppliers.length === 0"
            class="flex-1 flex items-center justify-center text-gray-400 text-sm">
            No suppliers found
        </div>

        <div v-else class="flex-1 overflow-y-auto px-4 pb-4">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Balance</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="supplier in filteredSuppliers" :key="supplier.id"
                            class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ supplier.name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ supplier.phone || '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="font-semibold"
                                    :class="parseFloat(supplier.balance) > 0 ? 'text-red-600' : 'text-gray-700'">
                                    ${{ parseFloat(supplier.balance).toFixed(2) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button v-if="parseFloat(supplier.balance) > 0"
                                    @click="paySupplier(supplier)"
                                    class="px-3 py-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-xs font-medium text-emerald-700 transition-colors cursor-pointer">
                                    Pay
                                </button>
                                <button @click="openEdit(supplier)"
                                    class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs font-medium transition-colors cursor-pointer">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <SupplierModal v-if="showSupplierModal"
        :supplier="editingSupplier"
        @save="saveSupplier"
        @close="closeModal" />
</template>
