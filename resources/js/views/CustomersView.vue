<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePosStore } from '../stores/usePosStore';
import CustomerModal from '../components/CustomerModal.vue';
import RepaymentModal from '../components/RepaymentModal.vue';

const store = usePosStore();

const search = ref('');
const showCustomerModal = ref(false);
const showRepaymentModal = ref(false);
const editingCustomer = ref(null);
const repayingCustomer = ref(null);

const filteredCustomers = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return store.customers;
    return store.customers.filter(c =>
        c.name.toLowerCase().includes(q) ||
        (c.phone && c.phone.includes(q))
    );
});

function openAdd() {
    editingCustomer.value = null;
    showCustomerModal.value = true;
}

function openEdit(customer) {
    editingCustomer.value = customer;
    showCustomerModal.value = true;
}

async function saveCustomer(customer) {
    await store.saveCustomer(customer);
    showCustomerModal.value = false;
    editingCustomer.value = null;
}

function openRepayment(customer) {
    repayingCustomer.value = customer;
    showRepaymentModal.value = true;
}

async function handleRepayment({ customerId, amount }) {
    await store.settleCredit(customerId, amount);
    showRepaymentModal.value = false;
    repayingCustomer.value = null;
}

function closeRepayment() {
    showRepaymentModal.value = false;
    repayingCustomer.value = null;
}

function closeModal() {
    showCustomerModal.value = false;
    editingCustomer.value = null;
}

onMounted(async () => {
    await store.loadCustomers();
});
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100">
        <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-5 shrink-0">
            <h1 class="text-xl font-bold tracking-tight">Customers</h1>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="store.isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ store.isOnline ? 'Online' : 'Offline' }}
                </span>
                <button @click="openAdd"
                    class="px-4 py-1.5 rounded-lg bg-blue-600 text-white font-bold text-xs
                           hover:bg-blue-700 transition-all cursor-pointer">
                    + Add Customer
                </button>
            </div>
        </header>

        <div class="p-4">
            <input v-model="search" type="text" placeholder="Search by name or phone..."
                class="w-full max-w-md px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
        </div>

        <div v-if="filteredCustomers.length === 0"
            class="flex-1 flex items-center justify-center text-gray-400 text-sm">
            No customers found
        </div>

        <div v-else class="flex-1 overflow-y-auto px-4 pb-4">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Credit Balance</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="customer in filteredCustomers" :key="customer.id"
                            class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ customer.name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ customer.phone || '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="font-semibold"
                                    :class="parseFloat(customer.credit_balance) > 0 ? 'text-red-600' : 'text-gray-700'">
                                    ${{ parseFloat(customer.credit_balance).toFixed(2) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button v-if="parseFloat(customer.credit_balance) > 0"
                                    @click="openRepayment(customer)"
                                    class="px-3 py-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-xs font-medium text-emerald-700 transition-colors cursor-pointer">
                                    Receive Payment
                                </button>
                                <button @click="openEdit(customer)"
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

    <CustomerModal v-if="showCustomerModal"
        :customer="editingCustomer"
        @save="saveCustomer"
        @close="closeModal" />

    <RepaymentModal v-if="showRepaymentModal && repayingCustomer"
        :customer="repayingCustomer"
        @confirm="handleRepayment"
        @close="closeRepayment" />
</template>
