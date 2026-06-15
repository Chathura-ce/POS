<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    product: Object,
});

const emit = defineEmits(['confirm', 'close']);

const changeType = ref('add');
const qty = ref(1);
const reason = ref('Restock');

const qtyChange = computed(() =>
    changeType.value === 'add' ? qty.value : -qty.value
);

const newStock = computed(() =>
    Math.max(0, (props.product?.stock_quantity || 0) + qtyChange.value)
);

const reasons = ['Restock', 'Damaged', 'Stolen', 'Other'];

function submit() {
    if (!qty.value || qty.value < 1) return;
    emit('confirm', {
        productId: props.product.id,
        qtyChange: qtyChange.value,
        reason: reason.value,
    });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="emit('close')">
        <div class="w-full max-w-sm mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold">Adjust Stock</h2>
                <button @click="emit('close')"
                    class="text-2xl leading-none text-gray-400 hover:text-gray-600 cursor-pointer">&times;</button>
            </div>

            <div class="p-5 space-y-4">
                <div class="text-sm">
                    <p class="font-medium">{{ product.name }}</p>
                    <p class="text-gray-500 text-xs">SKU: {{ product.sku }}</p>
                    <p class="text-sm mt-1">
                        Current Stock:
                        <span class="font-semibold" :class="product.stock_quantity < 5 ? 'text-red-600' : 'text-gray-700'">
                            {{ product.stock_quantity }}
                        </span>
                        →
                        <span class="font-semibold" :class="newStock < 5 ? 'text-red-600' : 'text-emerald-600'">
                            {{ newStock }}
                        </span>
                    </p>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button @click="changeType = 'add'"
                            class="py-2 rounded-lg text-sm font-bold transition-colors cursor-pointer"
                            :class="changeType === 'add'
                                ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            + Add Stock
                        </button>
                        <button @click="changeType = 'remove'"
                            class="py-2 rounded-lg text-sm font-bold transition-colors cursor-pointer"
                            :class="changeType === 'remove'
                                ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            − Remove Stock
                        </button>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Quantity</label>
                    <input v-model.number="qty" type="number" min="1" step="1"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Reason</label>
                    <select v-model="reason"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                        <option v-for="r in reasons" :key="r" :value="r">{{ r }}</option>
                    </select>
                </div>
            </div>

            <div class="p-5 pt-0 flex gap-3">
                <button @click="emit('close')"
                    class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-semibold text-sm
                           hover:bg-gray-200 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button @click="submit" :disabled="!qty || qty < 1"
                    class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-sm
                           hover:bg-blue-700 active:scale-[0.98] transition-all cursor-pointer
                           disabled:opacity-40 disabled:cursor-not-allowed">
                    Apply
                </button>
            </div>
        </div>
    </div>
</template>
