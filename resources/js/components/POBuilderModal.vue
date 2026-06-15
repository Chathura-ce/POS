<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePosStore } from '../stores/usePosStore';

const emit = defineEmits(['close']);
const store = usePosStore();

const supplierId = ref('');
const items = ref([]);
const selectedProductId = ref('');
const selectedQty = ref(1);
const selectedCost = ref(0);

const availableProducts = computed(() =>
    store.products.filter(p => p.pur_cost || p.sell_price)
);

const suppliers = computed(() => store.suppliers);

const totalAmount = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity * item.unit_cost, 0)
);

function addItem() {
    const product = store.products.find(p => p.id === selectedProductId.value);
    if (!product || !selectedQty.value || selectedQty.value <= 0) return;

    const existing = items.value.find(i => i.product_id === product.id);
    if (existing) {
        existing.quantity += selectedQty.value;
    } else {
        items.value.push({
            id: crypto.randomUUID(),
            product_id: product.id,
            name: product.name,
            quantity: selectedQty.value,
            unit_cost: selectedCost.value || parseFloat(product.pur_cost || product.sell_price),
        });
    }

    selectedProductId.value = '';
    selectedQty.value = 1;
    selectedCost.value = 0;
}

function removeItem(productId) {
    items.value = items.value.filter(i => i.product_id !== productId);
}

async function createPO() {
    if (!supplierId.value || items.value.length === 0) return;
    await store.createPO({
        supplier_id: supplierId.value,
        total_amount: totalAmount.value,
        items: items.value.map(i => ({
            product_id: i.product_id,
            quantity: i.quantity,
            unit_cost: i.unit_cost,
        })),
    });
    emit('close');
}

onMounted(async () => {
    await store.loadProducts();
    await store.loadProcurement();
});
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="emit('close')">
        <div class="w-full max-w-lg mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="p-5 border-b border-gray-200 flex items-center justify-between shrink-0">
                <h2 class="text-lg font-bold">New Purchase Order</h2>
                <button @click="emit('close')"
                    class="text-2xl leading-none text-gray-400 hover:text-gray-600 cursor-pointer">&times;</button>
            </div>

            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Supplier</label>
                    <select v-model="supplierId" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                        <option value="" disabled>Select supplier…</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <label class="text-xs font-medium text-gray-500 block mb-2">Add Items</label>
                    <div class="flex gap-2">
                        <select v-model="selectedProductId"
                            class="flex-1 px-3 py-2 rounded-lg border border-gray-300 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                            <option value="" disabled>Select product…</option>
                            <option v-for="p in availableProducts" :key="p.id" :value="p.id">
                                {{ p.name }} (${{ parseFloat(p.pur_cost || p.sell_price).toFixed(2) }})
                            </option>
                        </select>
                        <input v-model.number="selectedQty" type="number" min="1" step="1"
                            placeholder="Qty"
                            class="w-20 px-3 py-2 rounded-lg border border-gray-300 text-sm text-center
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                        <input v-model.number="selectedCost" type="number" min="0" step="0.01"
                            placeholder="Cost"
                            class="w-24 px-3 py-2 rounded-lg border border-gray-300 text-sm text-right
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                        <button @click="addItem"
                            class="px-3 py-2 rounded-lg bg-blue-600 text-white font-bold text-sm
                                   hover:bg-blue-700 transition-all cursor-pointer">
                            +
                        </button>
                    </div>
                </div>

                <div v-if="items.length > 0" class="space-y-2">
                    <div v-for="item in items" :key="item.product_id"
                        class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-2">
                        <div class="flex-1">
                            <span class="text-sm font-medium">{{ item.name }}</span>
                            <span class="text-xs text-gray-500 ml-2">
                                {{ item.quantity }} x ${{ item.unit_cost.toFixed(2) }}
                            </span>
                        </div>
                        <span class="text-sm font-semibold mr-3">
                            ${{ (item.quantity * item.unit_cost).toFixed(2) }}
                        </span>
                        <button @click="removeItem(item.product_id)"
                            class="text-red-400 hover:text-red-600 text-lg leading-none cursor-pointer">&times;</button>
                    </div>
                </div>

                <div v-if="items.length > 0" class="border-t border-gray-200 pt-3 flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-500">Total</span>
                    <span class="text-lg font-bold">${{ totalAmount.toFixed(2) }}</span>
                </div>
            </div>

            <div class="p-5 border-t border-gray-200 flex gap-3 shrink-0">
                <button type="button" @click="emit('close')"
                    class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-semibold text-sm
                           hover:bg-gray-200 transition-all cursor-pointer">
                    Cancel
                </button>
                <button @click="createPO" :disabled="!supplierId || items.length === 0"
                    class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-sm
                           hover:bg-blue-700 active:scale-[0.98] transition-all cursor-pointer
                           disabled:opacity-50">
                    Create PO
                </button>
            </div>
        </div>
    </div>
</template>
