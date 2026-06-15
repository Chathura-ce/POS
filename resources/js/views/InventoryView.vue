<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePosStore } from '../stores/usePosStore';
import ProductModal from '../components/ProductModal.vue';
import StockAdjustModal from '../components/StockAdjustModal.vue';

const store = usePosStore();

const search = ref('');
const showProductModal = ref(false);
const showStockModal = ref(false);
const editingProduct = ref(null);
const adjustingProduct = ref(null);

const filteredProducts = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return store.products;
    return store.products.filter(p =>
        p.name.toLowerCase().includes(q) ||
        p.sku.toLowerCase().includes(q) ||
        (p.barcode && p.barcode.toLowerCase().includes(q))
    );
});

function openAdd() {
    editingProduct.value = null;
    showProductModal.value = true;
}

function openEdit(product) {
    editingProduct.value = product;
    showProductModal.value = true;
}

async function saveProduct(product) {
    await store.saveProduct(product);
    showProductModal.value = false;
    editingProduct.value = null;
}

function openAdjustStock(product) {
    adjustingProduct.value = product;
    showStockModal.value = true;
}

async function handleStockAdjust({ productId, qtyChange, reason }) {
    await store.adjustStock(productId, qtyChange, reason);
    showStockModal.value = false;
    adjustingProduct.value = null;
}

function closeStockModal() {
    showStockModal.value = false;
    adjustingProduct.value = null;
}

function closeModal() {
    showProductModal.value = false;
    editingProduct.value = null;
}

onMounted(async () => {
    await store.loadProducts();
});
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100">
        <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-5 shrink-0">
            <h1 class="text-xl font-bold tracking-tight">Inventory</h1>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="store.isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ store.isOnline ? 'Online' : 'Offline' }}
                </span>
                <button @click="openAdd"
                    class="px-4 py-1.5 rounded-lg bg-blue-600 text-white font-bold text-xs
                           hover:bg-blue-700 transition-all cursor-pointer">
                    + Add Product
                </button>
            </div>
        </header>

        <div class="p-4">
            <input v-model="search" type="text" placeholder="Search by name, SKU or barcode..."
                class="w-full max-w-md px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
        </div>

        <div v-if="filteredProducts.length === 0"
            class="flex-1 flex items-center justify-center text-gray-400 text-sm">
            No products found
        </div>

        <div v-else class="flex-1 overflow-y-auto px-4 pb-4">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Sell Price</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="product in filteredProducts" :key="product.id"
                            class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ product.name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ product.sku }}</td>
                            <td class="px-4 py-3 font-semibold text-emerald-600">
                                ${{ parseFloat(product.sell_price).toFixed(2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium"
                                    :class="product.stock_quantity < 5 ? 'text-red-600' : 'text-gray-700'">
                                    {{ product.stock_quantity }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                    :class="product.is_active
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-gray-100 text-gray-500'">
                                    {{ product.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button @click="openAdjustStock(product)"
                                    class="px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-xs font-medium text-amber-700 transition-colors cursor-pointer">
                                    Stock
                                </button>
                                <button @click="openEdit(product)"
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

    <ProductModal v-if="showProductModal"
        :product="editingProduct"
        @save="saveProduct"
        @close="closeModal" />

    <StockAdjustModal v-if="showStockModal && adjustingProduct"
        :product="adjustingProduct"
        @confirm="handleStockAdjust"
        @close="closeStockModal" />
</template>
