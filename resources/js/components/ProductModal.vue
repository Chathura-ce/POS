<script setup>
import { ref, onMounted } from 'vue';
import db from '../db';

const props = defineProps({
    product: Object,
});

const emit = defineEmits(['save', 'close']);

const categories = ref([]);

const form = ref({
    name: '',
    category_id: '',
    sku: '',
    barcode: '',
    cost_price: 0,
    sell_price: 0,
    stock_quantity: 0,
    is_active: 1,
    ...(props.product ? {
        id: props.product.id,
        name: props.product.name,
        category_id: props.product.category_id,
        sku: props.product.sku,
        barcode: props.product.barcode || '',
        cost_price: parseFloat(props.product.cost_price) || 0,
        sell_price: parseFloat(props.product.sell_price) || 0,
        stock_quantity: props.product.stock_quantity || 0,
        is_active: props.product.is_active !== undefined ? (props.product.is_active ? 1 : 0) : 1,
    } : { id: undefined }),
});

const isEditing = computed(() => !!props.product);

import { computed } from 'vue';

function submit() {
    if (!form.value.name || !form.value.category_id || !form.value.sku) return;
    emit('save', {
        ...form.value,
        cost_price: parseFloat(form.value.cost_price) || 0,
        sell_price: parseFloat(form.value.sell_price) || 0,
        stock_quantity: parseInt(form.value.stock_quantity) || 0,
        is_active: form.value.is_active ? true : false,
    });
}

onMounted(async () => {
    categories.value = await db.categories.toArray();
    if (!form.value.category_id && categories.value.length) {
        form.value.category_id = categories.value[0].id;
    }
});
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="emit('close')">
        <div class="w-full max-w-md mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold">{{ isEditing ? 'Edit Product' : 'Add Product' }}</h2>
                <button @click="emit('close')"
                    class="text-2xl leading-none text-gray-400 hover:text-gray-600 cursor-pointer">&times;</button>
            </div>

            <form @submit.prevent="submit" class="p-5 space-y-4">
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Name</label>
                    <input v-model="form.name" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Category</label>
                    <select v-model="form.category_id" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500">
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">SKU</label>
                        <input v-model="form.sku" required
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Barcode</label>
                        <input v-model="form.barcode"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Cost Price ($)</label>
                        <input v-model.number="form.cost_price" type="number" min="0" step="0.01"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Sell Price ($)</label>
                        <input v-model.number="form.sell_price" type="number" min="0" step="0.01" required
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Stock Quantity</label>
                    <input v-model.number="form.stock_quantity" type="number" min="0" step="1"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_active" v-model="form.is_active" :true-value="1" :false-value="0"
                        class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                    <label for="is_active" class="text-sm text-gray-600">Active</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="emit('close')"
                        class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-semibold text-sm
                               hover:bg-gray-200 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-sm
                               hover:bg-blue-700 active:scale-[0.98] transition-all cursor-pointer">
                        {{ isEditing ? 'Update' : 'Add' }} Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
