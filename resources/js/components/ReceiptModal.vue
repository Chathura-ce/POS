<script setup>
import { computed } from 'vue';

const props = defineProps({
    sale: Object,
});

const emit = defineEmits(['close']);

const truncatedId = computed(() =>
    props.sale?.id ? props.sale.id.split('-').pop().toUpperCase() : ''
);

const dateStr = computed(() => {
    if (!props.sale?.created_at) return '';
    const d = new Date(props.sale.created_at);
    return d.toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
});

function print() {
    window.print();
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 print:bg-white"
        @click.self="emit('close')">
        <div class="w-full max-w-sm mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden print:shadow-none print:rounded-none print:max-w-full print:mx-0">
            <div class="receipt p-6 space-y-4">
                <div class="text-center border-b border-gray-300 pb-4">
                    <h2 class="text-lg font-bold tracking-tight">Local POS</h2>
                    <p class="text-xs text-gray-500">123 Main Street, City</p>
                    <p class="text-xs text-gray-500">Tel: (555) 123-4567</p>
                </div>

                <div class="text-xs text-gray-500 space-y-0.5">
                    <div class="flex justify-between">
                        <span>Date:</span>
                        <span>{{ dateStr }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Receipt #:</span>
                        <span class="font-mono">{{ truncatedId }}</span>
                    </div>
                </div>

                <div class="border-t border-b border-gray-300 py-2 space-y-1.5">
                    <div class="flex justify-between text-xs font-semibold text-gray-500 pb-1">
                        <span class="flex-1">Item</span>
                        <span class="w-16 text-right">Qty</span>
                        <span class="w-20 text-right">Price</span>
                    </div>
                    <div v-for="item in sale.items" :key="item.id"
                        class="flex justify-between text-sm">
                        <span class="flex-1 truncate">{{ item.name }}</span>
                        <span class="w-16 text-right text-xs text-gray-400">{{ item.quantity }}</span>
                        <span class="w-20 text-right">${{ item.line_total.toFixed(2) }}</span>
                    </div>
                </div>

                <div class="text-sm space-y-1">
                    <div class="flex justify-between text-gray-500">
                        <span>Subtotal</span>
                        <span>${{ sale.subtotal.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>Tax</span>
                        <span>${{ sale.tax.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-base border-t border-gray-300 pt-1">
                        <span>Total</span>
                        <span>${{ sale.total.toFixed(2) }}</span>
                    </div>
                </div>

                <div class="text-xs text-gray-500 border-t border-gray-300 pt-3 space-y-0.5">
                    <div class="flex justify-between">
                        <span>Payment</span>
                        <span class="capitalize font-medium">{{ sale.payment_type }}</span>
                    </div>
                    <div v-if="sale.payment_type === 'cash'" class="flex justify-between">
                        <span>Tendered</span>
                        <span>${{ (sale.amount_tendered || sale.total).toFixed(2) }}</span>
                    </div>
                    <div v-if="sale.payment_type === 'cash' && (sale.change || 0) > 0" class="flex justify-between">
                        <span>Change</span>
                        <span>${{ (sale.change || 0).toFixed(2) }}</span>
                    </div>
                </div>

                <div class="text-center text-xs text-gray-400 pt-2 border-t border-gray-300">
                    <p>Thank you for your purchase!</p>
                </div>
            </div>

            <div class="p-5 border-t border-gray-200 flex gap-3 print:hidden">
                <button @click="print"
                    class="flex-1 py-3 rounded-xl bg-blue-600 text-white font-bold text-sm
                           hover:bg-blue-700 transition-all cursor-pointer">
                    Print Receipt
                </button>
                <button @click="emit('close')"
                    class="flex-1 py-3 rounded-xl bg-gray-100 text-gray-700 font-bold text-sm
                           hover:bg-gray-200 transition-all cursor-pointer">
                    Next Customer
                </button>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .receipt,
    .receipt * {
        visibility: visible;
    }
    .receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
