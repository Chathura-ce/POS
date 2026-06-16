<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePosStore } from './stores/usePosStore';
import PosView from './views/PosView.vue';
import InventoryView from './views/InventoryView.vue';
import CustomersView from './views/CustomersView.vue';
import SuppliersView from './views/SuppliersView.vue';
import ProcurementView from './views/ProcurementView.vue';
import SalesHistoryView from './views/SalesHistoryView.vue';
import DashboardView from './views/DashboardView.vue';
import ShiftModal from './components/ShiftModal.vue';
import db from './db';
import axios from 'axios';

const store = usePosStore();
const token = ref(localStorage.getItem('pos_token'));
const user = ref(null);
const showLogin = ref(!token.value);
const currentView = ref('pos');
const showShiftModal = ref(false);
const mobileMenuOpen = ref(false);
const email = ref('owner@pos.test');
const password = ref('password');
const loginError = ref('');
const loginLoading = ref(false);

if (token.value) {
    axios.defaults.headers.common.Authorization = `Bearer ${token.value}`;
}

function goOnline() {
    store.isOnline = true;
    store.syncData();
    store.pullCatalog();
}

function goOffline() {
    store.isOnline = false;
}

window.addEventListener('online', goOnline);
window.addEventListener('offline', goOffline);

async function login() {
    loginError.value = '';
    loginLoading.value = true;
    try {
        const { data } = await axios.post('/api/login', { email: email.value, password: password.value });
        token.value = data.token;
        user.value = data.user;
        localStorage.setItem('pos_token', data.token);
        localStorage.setItem('pos_user_id', data.user.id);
        axios.defaults.headers.common.Authorization = `Bearer ${data.token}`;
        showLogin.value = false;
        await store.pullCatalog();
    } catch (e) {
        loginError.value = e.response?.data?.message || 'Login failed';
    } finally {
        loginLoading.value = false;
    }
}

async function logout() {
    try { await axios.post('/api/logout'); } catch (e) { /* ignore */ }
    token.value = null;
    user.value = null;
    localStorage.removeItem('pos_token');
    localStorage.removeItem('pos_user_id');
    delete axios.defaults.headers.common.Authorization;
    showLogin.value = true;
}

function navigate(view) {
    currentView.value = view;
    mobileMenuOpen.value = false;
}

onMounted(() => {
    if (token.value) {
        store.loadProducts();
        store.syncData();
        store.pullCatalog();
    }
});

onUnmounted(() => {
    window.removeEventListener('online', goOnline);
    window.removeEventListener('offline', goOffline);
});
</script>

<template>
    <div v-if="showLogin" class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm space-y-6">
            <div class="text-center space-y-1">
                <div class="text-4xl">🏪</div>
                <h1 class="text-xl font-bold">POS System</h1>
                <p class="text-sm text-gray-500">Sign in to continue</p>
            </div>

            <form @submit.prevent="login" class="space-y-4">
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Email</label>
                    <input v-model="email" type="email" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Password</label>
                    <input v-model="password" type="password" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                </div>

                <p v-if="loginError" class="text-red-500 text-xs text-center">{{ loginError }}</p>

                <button type="submit" :disabled="loginLoading"
                    class="w-full py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-sm
                           hover:bg-blue-700 active:scale-[0.98] transition-all cursor-pointer
                           disabled:opacity-50">
                    {{ loginLoading ? 'Signing in...' : 'Sign In' }}
                </button>
            </form>
        </div>
    </div>

    <div v-else class="h-screen flex flex-col">
        <nav class="sticky top-0 z-50 bg-white border-b border-gray-200 h-12 flex items-center px-3 lg:px-5 gap-2 lg:gap-4 shrink-0">
            <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="lg:hidden text-xl px-2 py-1 rounded-lg hover:bg-gray-100 cursor-pointer"
                aria-label="Toggle menu">
                <span v-if="!mobileMenuOpen">☰</span>
                <span v-else class="text-2xl">&times;</span>
            </button>

            <button @click="navigate('pos')"
                class="hidden lg:inline-flex text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'pos' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                POS
            </button>
            <button @click="navigate('inventory')"
                class="hidden lg:inline-flex text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'inventory' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Inventory
            </button>
            <button @click="navigate('customers')"
                class="hidden lg:inline-flex text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'customers' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Customers
            </button>
            <button @click="navigate('suppliers')"
                class="hidden lg:inline-flex text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'suppliers' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Suppliers
            </button>
            <button @click="navigate('procurement')"
                class="hidden lg:inline-flex text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'procurement' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Procurement
            </button>
            <button @click="navigate('sales')"
                class="hidden lg:inline-flex text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'sales' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Sales
            </button>
            <button @click="navigate('dashboard')"
                class="hidden lg:inline-flex text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'dashboard' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Dashboard
            </button>
            <div class="flex-1"></div>
            <button @click="showShiftModal = true"
                class="text-xs px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-700 font-medium transition-colors cursor-pointer shrink-0">
                End of Day
            </button>
            <span class="hidden sm:inline text-xs text-gray-500 truncate max-w-24">{{ user?.name }}</span>
            <button @click="logout"
                class="text-xs text-red-500 hover:text-red-700 font-medium cursor-pointer shrink-0">
                Logout
            </button>
        </nav>

        <div v-if="mobileMenuOpen"
            class="fixed inset-0 z-40 bg-white lg:hidden flex flex-col pt-12">
            <div class="flex-1 overflow-y-auto p-4 space-y-1">
                <button v-for="item in [
                    { key: 'pos', label: '🛒 POS' },
                    { key: 'inventory', label: '📦 Inventory' },
                    { key: 'customers', label: '👥 Customers' },
                    { key: 'suppliers', label: '🏭 Suppliers' },
                    { key: 'procurement', label: '📋 Procurement' },
                    { key: 'sales', label: '💰 Sales' },
                    { key: 'dashboard', label: '📊 Dashboard' },
                ]" :key="item.key" @click="navigate(item.key)"
                    class="w-full text-left px-5 py-4 rounded-xl text-lg font-semibold transition-colors cursor-pointer"
                    :class="currentView === item.key
                        ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100'">
                    {{ item.label }}
                </button>
            </div>
            <div class="p-4 border-t border-gray-200 space-y-2">
                <p class="text-sm text-gray-500 text-center">{{ user?.name }}</p>
                <button @click="logout"
                    class="w-full py-3 rounded-xl bg-red-50 text-red-600 font-semibold text-sm hover:bg-red-100 transition-colors cursor-pointer">
                    Logout
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-hidden">
            <PosView v-if="currentView === 'pos'" :user="user" @logout="logout" />
            <InventoryView v-if="currentView === 'inventory'" />
            <CustomersView v-if="currentView === 'customers'" />
            <SuppliersView v-if="currentView === 'suppliers'" />
            <ProcurementView v-if="currentView === 'procurement'" />
            <SalesHistoryView v-if="currentView === 'sales'" />
            <DashboardView v-if="currentView === 'dashboard'" />
        </div>
    </div>

    <ShiftModal v-if="showShiftModal" @close="showShiftModal = false" />
</template>
