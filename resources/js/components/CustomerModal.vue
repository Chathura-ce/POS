<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    customer: Object,
});

const emit = defineEmits(['save', 'close']);

const form = ref({
    name: '',
    phone: '',
    credit_balance: 0,
    ...(props.customer ? {
        id: props.customer.id,
        name: props.customer.name || '',
        phone: props.customer.phone || '',
        credit_balance: parseFloat(props.customer.credit_balance) || 0,
    } : { id: undefined }),
});

const isEditing = computed(() => !!props.customer);

function submit() {
    if (!form.value.name) return;
    emit('save', {
        ...form.value,
        credit_balance: parseFloat(form.value.credit_balance) || 0,
    });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="emit('close')">
        <div class="w-full max-w-md mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold">{{ isEditing ? 'Edit Customer' : 'Add Customer' }}</h2>
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
                    <label class="text-xs font-medium text-gray-500 block mb-1">Phone</label>
                    <input v-model="form.phone" type="tel"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500 block mb-1">Credit Balance ($)</label>
                    <input v-model.number="form.credit_balance" type="number" min="0" step="0.01"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500" />
                    <p class="text-xs text-gray-400 mt-1">Current outstanding credit amount</p>
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
                        {{ isEditing ? 'Update' : 'Add' }} Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
