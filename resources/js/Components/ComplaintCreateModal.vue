<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    // Optional preselected customer (from Customer Show or Staff Customer Details)
    customer: {
        type: Object,
        default: null,
    },
    // List of customers for selection if no customer is preselected
    customers: {
        type: Array,
        default: () => [],
    },
    // Staff users list for assignment (Admin mode)
    staffUsers: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'success']);

const customerSearch = ref('');
const selectedCustomer = ref(props.customer || null);
const showCustomerDropdown = ref(false);

watch(() => props.customer, (newVal) => {
    selectedCustomer.value = newVal;
    if (newVal) {
        form.customer_id = newVal.id;
        form.connection_id = newVal.connections?.[0]?.id || null;
    }
}, { immediate: true });

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.customer) {
            selectedCustomer.value = props.customer;
            form.customer_id = props.customer.id;
            form.connection_id = props.customer.connections?.[0]?.id || null;
        } else {
            selectedCustomer.value = null;
            customerSearch.value = '';
            form.customer_id = '';
            form.connection_id = null;
        }
        form.subject = '';
        form.description = '';
        form.priority = 'normal';
        form.assigned_to = '';
        form.clearErrors();
    }
});

const filteredCustomers = computed(() => {
    if (!customerSearch.value || customerSearch.value.length < 1) {
        return props.customers.slice(0, 15);
    }
    const q = customerSearch.value.toLowerCase();
    return props.customers.filter(c =>
        (c.name && c.name.toLowerCase().includes(q)) ||
        (c.customer_code && c.customer_code.toLowerCase().includes(q)) ||
        (c.phone && c.phone.includes(q))
    ).slice(0, 20);
});

const selectCustomer = (c) => {
    selectedCustomer.value = c;
    form.customer_id = c.id;
    form.connection_id = c.connection_id || c.connections?.[0]?.id || null;
    customerSearch.value = '';
    showCustomerDropdown.value = false;
};

const clearCustomerSelection = () => {
    if (props.customer) return; // Prevent clearing if fixed
    selectedCustomer.value = null;
    form.customer_id = '';
    form.connection_id = null;
    customerSearch.value = '';
};

const subjectPresets = [
    { label: '🔴 ইন্টারনেট নেই (No Internet)', val: 'ইন্টারনেট সম্পূর্ণ বন্ধ (No Internet Connection)' },
    { label: '🐢 স্পিড কম (Slow Speed)', val: 'ধীরগতির ইন্টারনেট ও হাই পিং (Slow Internet Speed)' },
    { label: '🚨 লাল বাতি (Red LOS Light)', val: 'ONU/রাউটারে লাল বাতি জ্বলছে (Red LOS Light on ONU)' },
    { label: '✂️ ফাইবার কাটা (Fiber Cut)', val: 'অপটিক্যাল ফাইবার তার কাটা পড়েছে (Fiber Cable Broken)' },
    { label: '📶 ওয়াইফাই সমস্যা (WiFi Issue)', val: 'ওয়াইফাই কানেক্ট হচ্ছে না / রাউটার কনফিগ (WiFi Issue)' },
    { label: '💳 বিল সংক্রান্ত (Billing)', val: 'বিল সংক্রান্ত অভিযোগ / যাচাই (Billing Dispute)' },
];

const setPresetSubject = (preset) => {
    form.subject = preset.val;
};

const form = useForm({
    customer_id: '',
    connection_id: null,
    subject: '',
    description: '',
    priority: 'normal',
    assigned_to: '',
});

const submit = () => {
    if (!form.customer_id) {
        alert('অনুগ্রহ করে একজন গ্রাহক নির্বাচন করুন (Please select a customer)');
        return;
    }

    form.post(route('complaints.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
            form.reset();
        },
    });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
        <div class="relative w-full max-w-xl rounded-3xl border border-white/10 bg-gradient-to-b from-[#0B1E36] to-[#071322] p-5 sm:p-7 shadow-2xl space-y-5 my-8 text-white">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-2xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-xl shadow-[0_0_15px_rgba(244,63,94,0.3)]">
                        🚨
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-white tracking-tight flex items-center gap-2">
                            <span>নতুন কমপ্লেইন তৈরি করুন</span>
                            <span class="rounded-lg bg-rose-500/20 border border-rose-500/30 px-2 py-0.5 text-[10px] font-bold text-rose-300 uppercase">
                                Ticket
                            </span>
                        </h2>
                        <p class="text-xs text-slate-400">গ্রাহকের ইন্টারনেট সমস্যা ও টিকিট দ্রুত লিপিবদ্ধ করুন</p>
                    </div>
                </div>

                <button
                    @click="emit('close')"
                    type="button"
                    class="h-8 w-8 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white flex items-center justify-center text-sm font-bold transition"
                >
                    ✕
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                
                <!-- 1. Customer Selection Field -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>গ্রাহক নির্বাচন (Customer) *</span>
                        <span v-if="selectedCustomer" class="text-[10px] font-mono text-brand-sky font-normal">ID: {{ selectedCustomer.id }}</span>
                    </label>

                    <!-- Case A: Customer is already chosen -->
                    <div v-if="selectedCustomer" class="p-3 rounded-2xl bg-[#071527] border border-brand-sky/40 flex items-center justify-between shadow-inner">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-brand-sky/20 border border-brand-sky/30 flex items-center justify-center text-brand-sky font-black">
                                👤
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white flex items-center gap-2">
                                    <span>{{ selectedCustomer.name }}</span>
                                    <span class="text-xs font-mono text-brand-cyan bg-white/5 px-1.5 py-0.5 rounded">
                                        {{ selectedCustomer.customer_code }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-2 font-mono">
                                    <span>📞 {{ selectedCustomer.phone || selectedCustomer.primary_contact?.phone || 'No phone' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Change button if not hard-locked by parent page -->
                        <button
                            v-if="!props.customer"
                            type="button"
                            @click="clearCustomerSelection"
                            class="text-xs text-rose-400 hover:text-rose-300 underline font-semibold px-2 py-1"
                        >
                            পরিবর্তন
                        </button>
                    </div>

                    <!-- Case B: Searchable dropdown for Customer -->
                    <div v-else class="relative">
                        <input
                            v-model="customerSearch"
                            @focus="showCustomerDropdown = true"
                            type="text"
                            placeholder="গ্রাহকের নাম, কোড (CUST-...) অথবা মোবাইল নম্বর লিখুন..."
                            class="w-full rounded-2xl bg-[#071527] border border-white/10 px-4 py-3 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky transition"
                        />

                        <!-- Customer Search Dropdown Results -->
                        <div
                            v-if="showCustomerDropdown && filteredCustomers.length > 0"
                            class="absolute top-full left-0 right-0 mt-1 max-h-52 overflow-y-auto rounded-2xl border border-white/10 bg-[#071527] p-1.5 shadow-2xl z-30 space-y-1"
                        >
                            <button
                                v-for="c in filteredCustomers"
                                :key="c.id"
                                type="button"
                                @click="selectCustomer(c)"
                                class="w-full text-left p-2.5 rounded-xl hover:bg-white/5 border border-transparent hover:border-white/10 flex items-center justify-between transition group"
                            >
                                <div>
                                    <div class="text-xs font-bold text-white group-hover:text-brand-sky">{{ c.name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ c.customer_code }} • {{ c.phone }}</div>
                                </div>
                                <span class="text-xs text-brand-sky font-bold opacity-0 group-hover:opacity-100 transition">সিলেক্ট করুন →</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="form.errors.customer_id" class="text-xs text-rose-400 mt-1 font-medium">{{ form.errors.customer_id }}</div>
                </div>

                <!-- 2. Quick Subject Presets -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        দ্রুত সমস্যার ধরন (Quick Select)
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                        <button
                            v-for="preset in subjectPresets"
                            :key="preset.val"
                            type="button"
                            @click="setPresetSubject(preset)"
                            :class="[
                                'p-2 rounded-xl text-left text-[11px] font-semibold border transition-all truncate',
                                form.subject === preset.val
                                    ? 'bg-rose-500/20 border-rose-500 text-white shadow-sm'
                                    : 'bg-[#071527]/80 border-white/5 text-slate-300 hover:text-white hover:bg-white/5'
                            ]"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                </div>

                <!-- 3. Subject Field -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                        অভিযোগের বিষয় (Subject) *
                    </label>
                    <input
                        v-model="form.subject"
                        type="text"
                        required
                        placeholder="যেমন: ইন্টারনেট সম্পূর্ণ বন্ধ / রাউটারে লাল বাতি"
                        class="w-full rounded-2xl bg-[#071527] border border-white/10 px-4 py-3 text-xs text-white placeholder-slate-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition"
                    />
                    <div v-if="form.errors.subject" class="text-xs text-rose-400 mt-1 font-medium">{{ form.errors.subject }}</div>
                </div>

                <!-- 4. Priority Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        গুরুত্ব / প্রায়োরিটি (Priority) *
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="p in [
                                { key: 'low', label: 'Low', color: 'border-slate-500 bg-slate-500/20 text-slate-300' },
                                { key: 'normal', label: 'Normal', color: 'border-brand-sky bg-brand-sky/20 text-brand-sky' },
                                { key: 'high', label: 'High', color: 'border-amber-500 bg-amber-500/20 text-amber-400' },
                                { key: 'urgent', label: 'Urgent', color: 'border-rose-500 bg-rose-500/20 text-rose-400' }
                            ]"
                            :key="p.key"
                            type="button"
                            @click="form.priority = p.key"
                            :class="[
                                'py-2 rounded-xl text-center text-xs font-bold border transition-all',
                                form.priority === p.key
                                    ? `${p.color} ring-2 ring-white/20 shadow-md`
                                    : 'border-white/5 bg-[#071527]/80 text-slate-400 hover:text-white'
                            ]"
                        >
                            {{ p.label }}
                        </button>
                    </div>
                </div>

                <!-- 5. Staff Assignment (Optional / Admin Mode) -->
                <div v-if="staffUsers && staffUsers.length > 0">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                        টেকনিশিয়ান এসাইন করুন (Assign Staff / Optional)
                    </label>
                    <select
                        v-model="form.assigned_to"
                        class="w-full rounded-2xl bg-[#071527] border border-white/10 px-4 py-3 text-xs text-white focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                    >
                        <option value="">কোনো স্টাফ নির্ধারিত নয় (Unassigned / Open)</option>
                        <option v-for="staff in staffUsers" :key="staff.id" :value="staff.id">
                            👨‍🔧 {{ staff.name }}
                        </option>
                    </select>
                </div>

                <!-- 6. Detailed Problem Description -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1 flex items-center justify-between">
                        <span>বিস্তারিত বিবরণ (Description)</span>
                        <span class="text-[10px] text-slate-400 font-normal lowercase">(ঐচ্ছিক / optional)</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="গ্রাহকের সমস্যার অতিরিক্ত কোনো বিবরণ থাকলে লিখুন (ঐচ্ছিক)..."
                        class="w-full rounded-2xl bg-[#071527] border border-white/10 px-4 py-3 text-xs text-white placeholder-slate-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition"
                    ></textarea>
                    <div v-if="form.errors.description" class="text-xs text-rose-400 mt-1 font-medium">{{ form.errors.description }}</div>
                </div>

                <!-- Modal Actions Footer -->
                <div class="flex items-center gap-3 pt-3 border-t border-white/10">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="flex-1 py-3 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-slate-300 hover:text-white transition"
                    >
                        বাতিল
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || !form.customer_id || !form.subject || !form.description"
                        class="flex-1 py-3 rounded-2xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-xs font-bold text-white shadow-lg shadow-rose-500/25 transition disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                        <span v-if="form.processing">সংরক্ষণ হচ্ছে...</span>
                        <span v-else>কমপ্লেইন সাবমিট করুন →</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
