<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePosStore } from '../stores/usePosStore';
import db from '../db';

const store = usePosStore();
const emit = defineEmits(['close']);

const currentShift = ref(null);
const openingCash = ref(0);
const actualCash = ref(0);
const mode = ref('loading');

const expectedCash = computed(() => {
    return parseFloat(currentShift.value?.opening_cash || 0) + (cashSalesTotalTemp.value || 0);
});

const cashSalesTotalTemp = ref(0);

const difference = computed(() => {
    return actualCash.value - expectedCash.value;
});

async function loadShift() {
    mode.value = 'loading';
    const shifts = await db.shifts
        .filter(s => !s.closed_at)
        .toArray();
    const open = shifts[0];
    if (open) {
        currentShift.value = open;
        mode.value = 'close';
        const opened = new Date(open.opened_at);
        const sales = await db.offline_sales
            .where('payment_type')
            .equals('cash')
            .filter(s => new Date(s.created_at) >= opened && s.status !== 'refunded')
            .toArray();
        cashSalesTotalTemp.value = sales.reduce((sum, s) => sum + parseFloat(s.total || 0), 0);
    } else {
        mode.value = 'open';
    }
}

async function openShift() {
    if (!openingCash.value || openingCash.value < 0) return;
    await store.openShift(openingCash.value);
    emit('close');
}

async function closeShift() {
    if (!currentShift.value) return;
    await store.closeShift(currentShift.value.id, actualCash.value);
    emit('close');
}

onMounted(loadShift);
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="emit('close')">
        <div class="w-full max-w-sm mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold">
                    {{ mode === 'open' ? 'Open Shift' : mode === 'close' ? 'Close Shift' : 'Loading...' }}
                </h2>
                <button @click="emit('close')"
                    class="text-2xl leading-none text-gray-400 hover:text-gray-600 cursor-pointer">&times;</button>
            </div>

            <div v-if="mode === 'loading'" class="p-10 text-center text-sm text-gray-400">
                Loading...
            </div>

            <div v-else-if="mode === 'open'" class="p-5 space-y-4">
                <p class="text-sm text-gray-500">Enter the cash amount you're putting in the drawer to start the day.</p>
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Opening Float ($)</label>
                    <input v-model.number="openingCash" type="number" min="0" step="0.01"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                </div>
                <button @click="openShift" :disabled="!openingCash || openingCash < 0"
                    class="w-full py-3 rounded-xl bg-blue-600 text-white font-bold text-sm
                           hover:bg-blue-700 active:scale-[0.98] transition-all cursor-pointer
                           disabled:opacity-40 disabled:cursor-not-allowed">
                    Start Shift
                </button>
            </div>

            <div v-else-if="mode === 'close'" class="p-5 space-y-4">
                <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Opening Float</span>
                        <span class="font-semibold">${{ parseFloat(currentShift.opening_cash).toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Cash Sales (today)</span>
                        <span class="font-semibold">${{ cashSalesTotalTemp.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-1 font-bold">
                        <span>Expected Cash</span>
                        <span class="text-emerald-600">${{ expectedCash.toFixed(2) }}</span>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Actual Cash Counted ($)</label>
                    <input v-model.number="actualCash" type="number" min="0" step="0.01"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                </div>

                <div v-if="actualCash > 0"
                    class="p-3 rounded-xl text-center text-sm font-bold"
                    :class="difference === 0 ? 'bg-emerald-50 text-emerald-700' : difference > 0 ? 'bg-blue-50 text-blue-700' : 'bg-red-50 text-red-700'">
                    {{ difference === 0 ? 'Drawer is balanced' : difference > 0 ? `Over by $${difference.toFixed(2)}` : `Short by $${Math.abs(difference).toFixed(2)}` }}
                </div>

                <button @click="closeShift" :disabled="!actualCash || actualCash < 0"
                    class="w-full py-3 rounded-xl bg-emerald-600 text-white font-bold text-sm
                           hover:bg-emerald-700 active:scale-[0.98] transition-all cursor-pointer
                           disabled:opacity-40 disabled:cursor-not-allowed">
                    Close Shift
                </button>
            </div>
        </div>
    </div>
</template>
