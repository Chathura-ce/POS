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
        <nav class="h-12 bg-white border-b border-gray-200 flex items-center px-5 gap-4 shrink-0">
            <button @click="currentView = 'pos'"
                class="text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'pos' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                POS
            </button>
            <button @click="currentView = 'inventory'"
                class="text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'inventory' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Inventory
            </button>
            <button @click="currentView = 'customers'"
                class="text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'customers' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Customers
            </button>
            <button @click="currentView = 'suppliers'"
                class="text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'suppliers' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Suppliers
            </button>
            <button @click="currentView = 'procurement'"
                class="text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'procurement' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Procurement
            </button>
            <button @click="currentView = 'sales'"
                class="text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'sales' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Sales
            </button>
            <button @click="currentView = 'dashboard'"
                class="text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                :class="currentView === 'dashboard' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">
                Dashboard
            </button>
            <div class="flex-1"></div>
            <button @click="showShiftModal = true"
                class="text-xs px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-700 font-medium transition-colors cursor-pointer">
                End of Day
            </button>
            <span class="text-xs text-gray-500">{{ user?.name }}</span>
            <button @click="logout"
                class="text-xs text-red-500 hover:text-red-700 font-medium cursor-pointer">
                Logout
            </button>
        </nav>
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
