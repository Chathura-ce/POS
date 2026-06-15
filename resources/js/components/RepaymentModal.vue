<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    customer: Object,
});

const emit = defineEmits(['confirm', 'close']);

const amount = ref(0);

const maxAmount = computed(() =>
    Math.max(0, parseFloat(props.customer?.credit_balance) || 0)
);

const isValid = computed(() =>
    amount.value > 0 && amount.value <= maxAmount.value
);

function submit() {
    if (!isValid.value) return;
    emit('confirm', { customerId: props.customer.id, amount: amount.value });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="emit('close')">
        <div class="w-full max-w-sm mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold">Receive Payment</h2>
                <button @click="emit('close')"
                    class="text-2xl leading-none text-gray-400 hover:text-gray-600 cursor-pointer">&times;</button>
            </div>

            <div class="p-5 space-y-4">
                <div class="text-sm">
                    <p class="font-medium">{{ customer.name }}</p>
                    <p class="text-red-600 font-semibold mt-1">
                        Outstanding: ${{ maxAmount.toFixed(2) }}
                    </p>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Payment Amount ($)</label>
                    <input v-model.number="amount" type="number" min="0.01" :max="maxAmount" step="0.01"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                    <p v-if="amount > maxAmount" class="text-xs text-red-500 mt-1">
                        Amount cannot exceed outstanding balance
                    </p>
                </div>

                <div class="flex gap-2">
                    <button v-for="preset in [maxAmount, maxAmount / 2, 10].filter(v => v > 0 && v <= maxAmount)"
                        :key="preset" @click="amount = preset"
                        class="flex-1 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs font-bold transition-colors cursor-pointer">
                        ${{ preset.toFixed(2) }}
                    </button>
                </div>
            </div>

            <div class="p-5 pt-0 flex gap-3">
                <button @click="emit('close')"
                    class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-semibold text-sm
                           hover:bg-gray-200 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button @click="submit" :disabled="!isValid"
                    class="flex-1 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold text-sm
                           hover:bg-emerald-700 active:scale-[0.98] transition-all cursor-pointer
                           disabled:opacity-40 disabled:cursor-not-allowed">
                    Confirm Payment
                </button>
            </div>
        </div>
    </div>
</template>
