<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    cartSubtotal: Number,
    cartTax: Number,
    cartTotal: Number,
});

const emit = defineEmits(['process-payment', 'close']);

const paymentType = ref('cash');
const amountTendered = ref(0);

const changeDue = computed(() =>
    Math.round((amountTendered.value - props.cartTotal) * 100) / 100
);

const canComplete = computed(() => {
    if (paymentType.value === 'cash') {
        return amountTendered.value >= props.cartTotal && amountTendered.value > 0;
    }
    return true;
});

const quickAmounts = computed(() => {
    const total = props.cartTotal;
    const amounts = [];
    for (const bill of [5, 10, 20, 50]) {
        const next = Math.ceil(total / bill) * bill;
        if (!amounts.includes(next)) amounts.push(next);
    }
    return amounts.filter(a => a > total);
});

function selectQuick(amount) {
    amountTendered.value = amount;
}

function completeSale() {
    if (!canComplete.value) return;
    emit('process-payment', {
        paymentType: paymentType.value,
        amountTendered: paymentType.value === 'cash' ? amountTendered.value : props.cartTotal,
        changeDue: paymentType.value === 'cash' ? changeDue.value : 0,
    });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="emit('close')">
        <div class="w-full max-w-lg mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold">Complete Payment</h2>
                <button @click="emit('close')"
                    class="text-2xl leading-none text-gray-400 hover:text-gray-600 cursor-pointer">&times;</button>
            </div>

            <div class="p-5 space-y-5">
                <div class="text-center">
                    <span class="text-3xl font-bold">${{ cartTotal.toFixed(2) }}</span>
                    <div class="text-sm text-gray-400 mt-1">
                        Subtotal ${{ cartSubtotal.toFixed(2) }} + Tax ${{ cartTax.toFixed(2) }}
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Payment Method</label>
                    <div class="grid grid-cols-3 gap-3">
                        <button v-for="method in ['cash', 'card', 'credit']" :key="method"
                            @click="paymentType = method"
                            class="py-3 rounded-xl font-bold text-sm uppercase tracking-wider transition-all cursor-pointer"
                            :class="paymentType === method
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            {{ method }}
                        </button>
                    </div>
                </div>

                <div v-if="paymentType === 'cash'">
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Amount Tendered</label>
                    <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-4 py-3 border border-gray-200">
                        <span class="text-lg font-bold text-gray-400">$</span>
                        <input type="number" v-model.number="amountTendered" min="0" step="0.01"
                            class="flex-1 bg-transparent text-2xl font-bold outline-none"
                            placeholder="0.00" />
                    </div>

                    <div class="flex gap-2 mt-3">
                        <button v-for="amt in quickAmounts" :key="amt"
                            @click="selectQuick(amt)"
                            class="flex-1 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 font-bold text-sm transition-colors cursor-pointer">
                            ${{ amt.toFixed(2) }}
                        </button>
                    </div>

                    <div v-if="amountTendered > 0"
                        class="mt-4 p-4 rounded-xl text-center text-2xl font-bold"
                        :class="changeDue >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'">
                        <div class="text-xs font-medium uppercase tracking-wide mb-1">
                            {{ changeDue >= 0 ? 'Change Due' : 'Short' }}
                        </div>
                        ${{ Math.abs(changeDue).toFixed(2) }}
                    </div>
                </div>

                <div v-if="paymentType === 'card'" class="p-4 bg-gray-50 rounded-xl text-center text-sm text-gray-500">
                    Insert / tap card at the terminal
                </div>

                <div v-if="paymentType === 'credit'" class="p-4 bg-amber-50 rounded-xl text-center text-sm text-amber-700">
                    Credit sale — customer will pay later
                </div>
            </div>

            <div class="p-5 pt-0">
                <button @click="completeSale"
                    :disabled="!canComplete"
                    class="w-full py-4 rounded-xl bg-emerald-600 text-white font-bold text-lg
                           hover:bg-emerald-700 active:scale-[0.98] transition-all cursor-pointer
                           disabled:opacity-40 disabled:cursor-not-allowed">
                    Complete Sale
                </button>
            </div>
        </div>
    </div>
</template>
