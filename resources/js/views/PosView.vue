<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePosStore } from '../stores/usePosStore';
import db from '../db';
import axios from 'axios';
import PaymentModal from '../components/PaymentModal.vue';
import ReceiptModal from '../components/ReceiptModal.vue';
import { useBarcodeScanner } from '../composables/useBarcodeScanner';

const store = usePosStore();

const activeCategory = ref('all');
const showPaymentModal = ref(false);
const showReceiptModal = ref(false);
const lastSale = ref(null);
const search = ref('');
const customerSearch = ref('');
const showMobileCart = ref(false);

const filteredProducts = computed(() => {
    let list = store.products.filter(p => p.is_active);
    if (activeCategory.value !== 'all') {
        list = list.filter(p => p.category_id === activeCategory.value);
    }
    const q = search.value.toLowerCase().trim();
    if (q) {
        list = list.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.sku.toLowerCase().includes(q)
        );
    }
    return list;
});

let categories = ref([]);

async function loadCategories() {
    categories.value = await db.categories.toArray();
}

function increment(item) {
    item.quantity += 1;
    item.line_total = item.quantity * item.unit_price;
}

function decrement(item) {
    if (item.quantity > 1) {
        item.quantity -= 1;
        item.line_total = item.quantity * item.unit_price;
    } else {
        store.removeFromCart(item.product_id);
    }
}

const filteredCustomers = computed(() => {
    const q = customerSearch.value.toLowerCase().trim();
    if (!q) return store.customers;
    return store.customers.filter(c =>
        c.name.toLowerCase().includes(q) ||
        (c.phone && c.phone.includes(q))
    );
});

async function handlePayment(paymentDetails) {
    if (paymentDetails.paymentType === 'credit' && !store.activeCustomer) {
        alert('Please select a customer for credit sales.');
        return;
    }
    const sale = await store.checkout(paymentDetails);
    lastSale.value = sale;
    showPaymentModal.value = false;
    showMobileCart.value = false;
    showReceiptModal.value = true;
}

function closeReceipt() {
    showReceiptModal.value = false;
    lastSale.value = null;
}

function onBarcodeScanned(code) {
    const product = store.products.find(p =>
        p.barcode === code || p.sku === code
    );
    if (product) {
        store.addToCart(product);
    } else {
        alert(`Barcode "${code}" not found in system.`);
    }
}

useBarcodeScanner(onBarcodeScanned);

onMounted(async () => {
    await store.loadProducts();
    await loadCategories();
});
</script>

<template>
    <div class="h-[calc(100vh-3rem)] overflow-hidden flex flex-col bg-gray-100">
        <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-5 shrink-0">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold tracking-tight">POS</h1>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="store.isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ store.isOnline ? 'Online' : 'Offline' }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                <button @click="store.syncData"
                    class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 font-medium transition-colors cursor-pointer">
                    Sync
                </button>
            </div>
        </header>

        <div class="flex-1 flex overflow-hidden">
            <div class="flex-[7] min-w-0 flex flex-col overflow-hidden">
                <div class="flex-none bg-white border-b border-gray-200 px-5 py-3 space-y-3">
                    <input v-model="search" type="text" placeholder="Search by name or SKU..."
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />

                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <button @click="activeCategory = 'all'"
                            class="shrink-0 px-4 py-2 rounded-full text-xs font-medium transition-colors cursor-pointer"
                            :class="activeCategory === 'all'
                                ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            All
                        </button>
                        <button v-for="cat in categories" :key="cat.id" @click="activeCategory = cat.id"
                            class="shrink-0 px-4 py-2 rounded-full text-xs font-medium transition-colors cursor-pointer"
                            :class="activeCategory === cat.id
                                ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                            {{ cat.name }}
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-5">
                    <div v-if="filteredProducts.length === 0"
                        class="flex items-center justify-center h-full">
                        <div class="py-12 flex flex-col items-center text-gray-400">
                            <span class="text-5xl mb-3">📦</span>
                            <p class="text-sm font-medium">No products found</p>
                            <p class="text-xs mt-1">Try a different search term</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                        <button v-for="product in filteredProducts" :key="product.id"
                            @click="store.addToCart(product)"
                            class="bg-white rounded-xl border border-gray-200 p-3 text-left
                                   hover:shadow-md hover:border-blue-300 transition-all
                                   active:scale-95 cursor-pointer flex flex-col">
                            <div class="aspect-square bg-gradient-to-br from-blue-50 to-indigo-100 rounded-lg mb-2
                                        flex items-center justify-center text-2xl">
                                📦
                            </div>
                            <span class="text-sm font-semibold truncate">{{ product.name }}</span>
                            <span class="text-xs text-gray-400 truncate">{{ product.sku }}</span>
                            <div class="mt-1 flex items-center justify-between">
                                <span class="text-sm font-bold text-emerald-600">
                                    ${{ parseFloat(product.sell_price).toFixed(2) }}
                                </span>
                                <span class="text-xs"
                                    :class="product.stock_quantity > 5 ? 'text-green-600' : 'text-red-500'">
                                    {{ product.stock_quantity }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="hidden lg:flex flex-[3] min-w-0 bg-white border-l border-gray-200 flex-col h-full">

                <div class="shrink-0 p-3 border-b border-gray-200 space-y-2">
                    <h2 class="text-base font-bold">Customer</h2>
                    <div v-if="!store.activeCustomer">
                        <input v-model="customerSearch" type="text" placeholder="Search customer..."
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                        <div v-if="customerSearch && filteredCustomers.length > 0"
                            class="mt-1 border border-gray-200 rounded-lg overflow-hidden max-h-32 overflow-y-auto">
                            <button v-for="c in filteredCustomers" :key="c.id"
                                @click="store.setActiveCustomer(c); customerSearch = ''"
                                class="w-full text-left px-3 py-2 text-xs hover:bg-blue-50 transition-colors cursor-pointer">
                                <span class="font-medium">{{ c.name }}</span>
                                <span v-if="parseFloat(c.credit_balance) > 0" class="text-red-500 ml-2">
                                    (${{ parseFloat(c.credit_balance).toFixed(2) }})
                                </span>
                            </button>
                        </div>
                    </div>
                    <div v-else
                        class="flex items-center justify-between bg-blue-50 rounded-lg px-3 py-2">
                        <div class="text-sm">
                            <span class="font-semibold">{{ store.activeCustomer.name }}</span>
                            <span v-if="parseFloat(store.activeCustomer.credit_balance) > 0"
                                class="text-red-600 text-xs ml-2">
                                Credit: ${{ parseFloat(store.activeCustomer.credit_balance).toFixed(2) }}
                            </span>
                        </div>
                        <button @click="store.clearActiveCustomer()"
                            class="text-red-400 hover:text-red-600 text-lg leading-none cursor-pointer">&times;</button>
                    </div>
                </div>

                <div class="shrink-0 p-3 border-b border-gray-200">
                    <h2 class="text-base font-bold">Current Sale</h2>
                </div>

                <div v-if="store.cart.length === 0"
                    class="flex-1 min-h-0 flex items-center justify-center text-gray-400 text-sm">
                    <div class="py-12 flex flex-col items-center text-gray-400">
                        <span class="text-5xl mb-3">🛒</span>
                        <p class="text-sm font-medium">Cart is empty</p>
                        <p class="text-xs mt-1">Tap a product to add it</p>
                    </div>
                </div>

                <div v-else class="flex-1 min-h-0 overflow-y-auto p-4 space-y-2">
                    <div v-for="item in store.cart" :key="item.product_id"
                        class="flex items-center gap-2 bg-gray-50 rounded-lg p-2.5">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ item.name }}</p>
                            <p class="text-xs text-gray-400">${{ item.unit_price.toFixed(2) }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button @click="decrement(item)"
                                class="w-7 h-7 rounded bg-gray-200 hover:bg-gray-300 text-xs font-bold
                                       flex items-center justify-center cursor-pointer">−</button>
                            <span class="w-6 text-center text-sm font-semibold">{{ item.quantity }}</span>
                            <button @click="increment(item)"
                                class="w-7 h-7 rounded bg-gray-200 hover:bg-gray-300 text-xs font-bold
                                       flex items-center justify-center cursor-pointer">+</button>
                        </div>
                        <span class="text-sm font-bold w-14 text-right">${{ item.line_total.toFixed(2) }}</span>
                        <button @click="store.removeFromCart(item.product_id)"
                            class="text-red-400 hover:text-red-600 text-lg leading-none cursor-pointer">×</button>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-200 p-4 space-y-3">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal</span>
                            <span>${{ store.cartSubtotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Tax ({{ (store.taxRate * 100).toFixed(0) }}%)</span>
                            <span>${{ store.cartTax.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold pt-1 border-t border-gray-200">
                            <span>Total</span>
                            <span>${{ store.cartTotal.toFixed(2) }}</span>
                        </div>
                    </div>

                    <button @click="showPaymentModal = true"
                        class="w-full py-3 rounded-xl bg-emerald-600 text-white font-bold text-sm
                               hover:bg-emerald-700 active:scale-[0.98] transition-all cursor-pointer
                               disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="store.cart.length === 0">
                        Pay ${{ store.cartTotal.toFixed(2) }}
                    </button>
                </div>
            </div>
        </div>

        <button v-if="store.cart.length > 0"
            @click="showMobileCart = true"
            class="fixed bottom-5 right-5 z-30 lg:hidden flex items-center gap-2
                   bg-blue-600 text-white px-5 py-3 rounded-full
                   shadow-lg hover:bg-blue-700 active:scale-95 transition-all cursor-pointer">
            <span class="text-lg">🛒</span>
            <span class="font-bold text-sm">View Cart ({{ store.cart.length }})</span>
        </button>

        <div v-if="showMobileCart"
            class="fixed inset-0 z-50 lg:hidden flex flex-col bg-black/50"
            @click.self="showMobileCart = false">
            <div class="mt-auto bg-white rounded-t-3xl shadow-2xl flex flex-col max-h-[85vh]"
                @click.stop>
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 shrink-0">
                    <div>
                        <h2 class="text-lg font-bold">Current Sale</h2>
                        <p class="text-xs text-gray-500">{{ store.cart.length }} item(s)</p>
                    </div>
                    <button @click="showMobileCart = false"
                        class="text-2xl text-gray-400 hover:text-gray-600 cursor-pointer">&times;</button>
                </div>

                <div class="p-3 border-b border-gray-200 space-y-2 shrink-0">
                    <h2 class="text-sm font-bold">Customer</h2>
                    <div v-if="!store.activeCustomer">
                        <input v-model="customerSearch" type="text" placeholder="Search customer..."
                            class="w-full px-3 py-2.5 rounded-lg border border-gray-300 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                        <div v-if="customerSearch && filteredCustomers.length > 0"
                            class="mt-1 border border-gray-200 rounded-lg overflow-hidden max-h-32 overflow-y-auto">
                            <button v-for="c in filteredCustomers" :key="c.id"
                                @click="store.setActiveCustomer(c); customerSearch = ''"
                                class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 transition-colors cursor-pointer">
                                <span class="font-medium">{{ c.name }}</span>
                                <span v-if="parseFloat(c.credit_balance) > 0" class="text-red-500 ml-2">
                                    (${{ parseFloat(c.credit_balance).toFixed(2) }})
                                </span>
                            </button>
                        </div>
                    </div>
                    <div v-else
                        class="flex items-center justify-between bg-blue-50 rounded-lg px-3 py-2">
                        <div class="text-sm">
                            <span class="font-semibold">{{ store.activeCustomer.name }}</span>
                            <span v-if="parseFloat(store.activeCustomer.credit_balance) > 0"
                                class="text-red-600 text-xs ml-2">
                                Credit: ${{ parseFloat(store.activeCustomer.credit_balance).toFixed(2) }}
                            </span>
                        </div>
                        <button @click="store.clearActiveCustomer()"
                            class="text-red-400 hover:text-red-600 text-lg leading-none cursor-pointer">&times;</button>
                    </div>
                </div>

                <div v-if="store.cart.length === 0"
                    class="flex-1 flex items-center justify-center">
                    <div class="py-12 flex flex-col items-center text-gray-400">
                        <span class="text-5xl mb-3">🛒</span>
                        <p class="text-sm font-medium">Cart is empty</p>
                    </div>
                </div>

                <div v-else class="flex-1 overflow-y-auto p-4 space-y-2">
                    <div v-for="item in store.cart" :key="item.product_id"
                        class="flex items-center gap-2 bg-gray-50 rounded-xl p-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate">{{ item.name }}</p>
                            <p class="text-xs text-gray-400">${{ item.unit_price.toFixed(2) }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button @click="decrement(item)"
                                class="w-10 h-10 rounded-lg bg-gray-200 hover:bg-gray-300 text-base font-bold
                                       flex items-center justify-center cursor-pointer">−</button>
                            <span class="w-8 text-center text-base font-bold">{{ item.quantity }}</span>
                            <button @click="increment(item)"
                                class="w-10 h-10 rounded-lg bg-gray-200 hover:bg-gray-300 text-base font-bold
                                       flex items-center justify-center cursor-pointer">+</button>
                        </div>
                        <span class="text-sm font-bold w-16 text-right">${{ item.line_total.toFixed(2) }}</span>
                        <button @click="store.removeFromCart(item.product_id)"
                            class="text-red-400 hover:text-red-600 text-xl leading-none cursor-pointer">×</button>
                    </div>
                </div>

                <div class="border-t border-gray-200 p-4 space-y-3 shrink-0">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal</span>
                            <span>${{ store.cartSubtotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Tax ({{ (store.taxRate * 100).toFixed(0) }}%)</span>
                            <span>${{ store.cartTax.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold pt-1 border-t border-gray-200">
                            <span>Total</span>
                            <span>${{ store.cartTotal.toFixed(2) }}</span>
                        </div>
                    </div>

                    <button @click="showPaymentModal = true"
                        class="w-full py-4 rounded-xl bg-emerald-600 text-white font-bold text-base
                               hover:bg-emerald-700 active:scale-[0.98] transition-all cursor-pointer
                               disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="store.cart.length === 0">
                        Pay ${{ store.cartTotal.toFixed(2) }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <PaymentModal v-if="showPaymentModal"
        :cart-subtotal="store.cartSubtotal"
        :cart-tax="store.cartTax"
        :cart-total="store.cartTotal"
        @process-payment="handlePayment"
        @close="showPaymentModal = false" />

    <ReceiptModal v-if="showReceiptModal && lastSale"
        :sale="lastSale"
        @close="closeReceipt" />
</template>
