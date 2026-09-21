<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { formatDate, formatDateTime } from '@/Utils/date';

const props = defineProps({
    customer: Object,
    canViewPppoePassword: Boolean,
});

const primaryConnection = props.customer.connections?.[0] || null;
const pppoe = primaryConnection?.pppoe_credential || null;

// Reveal PPPoE Password Modal
const revealedPassword = ref(null);
const isRevealing = ref(false);

const revealPassword = async () => {
    if (!pppoe?.id) return;
    isRevealing.value = true;
    try {
        const res = await axios.post(route('admin.pppoe.reveal-password', pppoe.id));
        revealedPassword.value = res.data.password;
    } catch (e) {
        alert('Unauthorized to view password.');
    } finally {
        isRevealing.value = false;
    }
};

import { syncService } from '@/Services/syncService';

// Quick Pay Modal/Form
const showPayModal = ref(false);
const paySuccessNotice = ref('');

const packagePrice = computed(() => {
    return Number(primaryConnection?.current_package?.current_price?.price) || 0;
});

const payForm = useForm({
    amount: packagePrice.value || 500,
    discount: 0,
    payment_method: 'cash',
    notes: 'Field collection',
});

const openPayModal = () => {
    const base = packagePrice.value || 500;
    payForm.discount = 0;
    payForm.amount = base;
    showPayModal.value = true;
};

const handleDiscountChange = () => {
    const base = packagePrice.value || 0;
    const disc = Number(payForm.discount) || 0;
    if (disc > base) {
        payForm.discount = base;
    }
    payForm.amount = Math.max(0, base - Number(payForm.discount || 0));
};

const submitPayment = async () => {
    if (!payForm.amount || payForm.amount <= 0) {
        alert('সঠিক টাকার অঙ্ক লিখুন (Amount must be greater than 0)');
        return;
    }

    if (!navigator.onLine) {
        // Enqueue offline payment mutation
        await syncService.collectPaymentOffline(props.customer, {
            amount: payForm.amount,
            discount: payForm.discount,
            payment_method: payForm.payment_method,
            notes: payForm.notes,
        });
        showPayModal.value = false;
        paySuccessNotice.value = `Payment of ৳${payForm.amount} queued offline. Will sync when online.`;
        setTimeout(() => { paySuccessNotice.value = ''; }, 4000);
        return;
    }

    payForm.post(route('customers.pay', props.customer.id), {
        preserveScroll: true,
        onError: (errors) => {
            const errList = Object.values(errors).flat().join('\n');
            alert('বিল আদায়ে ত্রুটি:\n' + (errList || 'অনুগ্রহ করে পুনরায় চেষ্টা করুন'));
        }
    });
};

// Customer Note & Promise-to-Pay Form
const showNoteModal = ref(false);
const noteForm = useForm({
    note_type: 'promise_to_pay',
    promise_date: '',
    promise_amount: primaryConnection?.current_package?.current_price?.price || '',
    note: '',
});

const submitNote = () => {
    noteForm.post(route('customers.notes.store', props.customer.id), {
        onSuccess: () => {
            showNoteModal.value = false;
            noteForm.reset();
        }
    });
};

const resolveNote = (noteId) => {
    axios.post(route('customers.notes.status', noteId), { status: 'resolved' })
        .then(() => {
            window.location.reload();
        });
};


</script>

<template>
    <Head :title="`${customer.name} - Field Operations`" />

    <StaffLayout>
        <div class="mb-4">
            <Link :href="route('staff.dashboard')" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1">
                ← Back to Dashboard
            </Link>
        </div>

        <!-- Offline Queue Notification Banners -->
        <div v-if="paySuccessNotice" class="mb-4 rounded-2xl border border-brand-orange/40 bg-brand-orange/10 p-3.5 text-xs font-bold text-brand-amber flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-brand-orange animate-pulse"></span>
            <span>{{ paySuccessNotice }}</span>
        </div>

        <!-- Customer Identity Card -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-md mb-4 shadow-xl">
            <div class="flex items-start justify-between">
                <div>
                    <span 
                        :class="[
                            customer.status === 'active' ? 'bg-brand-cyan/15 text-brand-cyan border-brand-cyan/30' : 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                            'inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-extrabold uppercase'
                        ]"
                    >
                        {{ customer.status }}
                    </span>
                    <h1 class="text-xl font-black text-white mt-1.5">{{ customer.name }}</h1>
                    <div class="text-xs font-mono text-brand-sky mt-0.5">{{ customer.customer_code }}</div>
                </div>

                <div class="text-right">
                    <a :href="`tel:${customer.primary_contact?.phone}`" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-sky to-brand-blue px-3.5 py-2 text-xs font-bold text-white shadow-lg shadow-brand-navy/50">
                        📞 Call Customer
                    </a>
                </div>
            </div>

            <!-- Quick Action Buttons on Field -->
            <div class="mt-5 pt-4 border-t border-brand-navy grid grid-cols-2 gap-2.5">
                <button
                    @click="openPayModal"
                    class="rounded-2xl bg-gradient-to-r from-brand-orange to-brand-amber hover:opacity-95 p-3 text-center text-xs font-bold text-white shadow-lg shadow-brand-orange/25 transition"
                >
                    💰 বিল আদায়
                </button>
                <button
                    @click="showNoteModal = true"
                    class="rounded-2xl bg-[#0B1E36] hover:bg-[#0B1E36]/80 border border-brand-sky/40 p-3 text-center text-xs font-bold text-brand-sky shadow-lg transition"
                >
                    📝 নোট / তারিখ যুক্ত করুন
                </button>
            </div>
        </div>

        <!-- Customer Staff Notes & Promise-To-Pay Ledger -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm space-y-3 mb-4 shadow-lg">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">স্টাফ নোট ও বিল প্রতিশ্রুতির রেকর্ড</h2>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-brand-sky/10 text-brand-sky border border-brand-sky/20">
                    {{ customer.customer_notes?.length || 0 }} Notes
                </span>
            </div>

            <div v-if="customer.customer_notes?.length > 0" class="space-y-2.5">
                <div
                    v-for="nt in customer.customer_notes"
                    :key="nt.id"
                    :class="[
                        'p-3.5 rounded-2xl border text-xs transition-all space-y-1.5',
                        nt.status === 'resolved' ? 'bg-slate-900/50 border-slate-800 opacity-60' : 'bg-[#0B1E36]/70 border-white/10'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span :class="[
                                'px-2 py-0.5 rounded-md text-[10px] font-bold uppercase',
                                nt.note_type === 'promise_to_pay' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-brand-sky/20 text-brand-sky border border-brand-sky/30'
                            ]">
                                {{ nt.note_type === 'promise_to_pay' ? 'পরে বিল দেবে' : 'সাধারণ নোট' }}
                            </span>
                            <span class="font-bold text-slate-200">{{ nt.author?.name }}</span>
                        </div>
                        <span class="text-[10px] font-mono text-slate-500">{{ formatDateTime(nt.created_at) }}</span>
                    </div>

                    <p class="text-slate-300 leading-relaxed">{{ nt.note }}</p>

                    <div v-if="nt.promise_date" class="flex items-center justify-between pt-1 border-t border-white/5 text-[11px]">
                        <span class="text-amber-400 font-medium">
                            📅 প্রতিশ্রুত তারিখ: <strong class="font-mono">{{ formatDate(nt.promise_date) }}</strong>
                            <span v-if="nt.promise_amount" class="ml-1 font-mono text-white">(৳{{ nt.promise_amount }})</span>
                        </span>

                        <button
                            v-if="nt.status !== 'resolved'"
                            @click="resolveNote(nt.id)"
                            class="px-2 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-[10px] font-bold border border-emerald-500/30 transition"
                        >
                            ✓ সম্পন্ন হয়েছে
                        </button>
                        <span v-else class="text-[10px] text-emerald-400 font-bold">✓ সম্পন্ন</span>
                    </div>
                </div>
            </div>
            <div v-else class="text-xs text-slate-500 text-center py-4 bg-[#0B1E36]/30 rounded-2xl">
                কোনো নোট যোগ করা হয়নি।
            </div>
        </div>

        <!-- Connection & Package Details -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm space-y-4 mb-4 shadow-lg">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Connection Details</h2>
            
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-400">Package:</span>
                    <div class="text-sm font-bold text-white mt-0.5">{{ primaryConnection?.current_package?.name }}</div>
                </div>
                <div>
                    <span class="text-slate-400">Bandwidth:</span>
                    <div class="text-sm font-bold text-brand-cyan mt-0.5">{{ primaryConnection?.current_package?.speed_mbps }} Mbps</div>
                </div>
                <div>
                    <span class="text-slate-400">Monthly Rate:</span>
                    <div class="text-sm font-bold text-white mt-0.5">৳{{ primaryConnection?.current_package?.current_price?.price }}</div>
                </div>
                <div>
                    <span class="text-slate-400">Expiry Date:</span>
                    <div class="text-sm font-mono font-bold text-brand-orange mt-0.5">{{ formatDate(primaryConnection?.expiry_date) }}</div>
                </div>
            </div>

            <!-- PPPoE Credential for Technician -->
            <div class="mt-4 pt-4 border-t border-brand-navy">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-400">PPPoE Username:</span>
                    <span class="text-xs font-mono font-bold text-white">{{ pppoe?.username || 'None' }}</span>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-slate-400">PPPoE Password:</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono font-bold text-brand-sky">{{ revealedPassword || '••••••••' }}</span>
                        <button
                            v-if="canViewPppoePassword && !revealedPassword"
                            @click="revealPassword"
                            class="text-[10px] font-bold text-brand-orange underline"
                        >
                            Reveal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Info -->
        <div class="rounded-3xl border border-brand-navy bg-[#071527]/90 p-5 backdrop-blur-sm text-xs space-y-2 shadow-lg">
            <h2 class="font-bold uppercase tracking-wider text-slate-400">Location & Area</h2>
            <div>
                <span class="text-slate-400">Area / Zone:</span>
                <span class="text-slate-200 font-semibold ml-1">{{ customer.area?.name }}</span>
            </div>
            <div>
                <span class="text-slate-400">Address:</span>
                <span class="text-slate-300 ml-1">{{ customer.installation_address?.full_address }}</span>
            </div>
        </div>

        <!-- Pay Modal -->
        <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-3xl border border-brand-navy bg-[#071527] p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-white">বিল আদায় (Record Payment)</h3>
                        <p class="text-[11px] text-slate-400">প্যাকেজ রেট স্বয়ংক্রিয়ভাবে ধার্য হয়েছে</p>
                    </div>
                    <button @click="showPayModal = false" class="text-slate-400 hover:text-white text-sm">✕</button>
                </div>

                <!-- Package Info Badge -->
                <div class="p-3 rounded-2xl bg-[#0B1E36]/80 border border-brand-sky/20 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-400">বর্তমান প্যাকেজ:</span>
                        <div class="font-bold text-white mt-0.5">{{ primaryConnection?.current_package?.name || 'Standard' }}</div>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400">নির্ধারিত ফি:</span>
                        <div class="font-mono font-bold text-brand-cyan text-sm mt-0.5">৳{{ packagePrice }}</div>
                    </div>
                </div>

                <!-- Discount Input Field -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs text-slate-400">ছাড় / ডিস্কাউন্ট (BDT)</label>
                        <span class="text-[10px] text-brand-amber">ঐচ্ছিক (Optional)</span>
                    </div>
                    <input
                        v-model.number="payForm.discount"
                        @input="handleDiscountChange"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-3 text-white font-mono focus:border-brand-amber focus:ring-brand-amber text-sm"
                    />
                </div>

                <!-- Final Payable Amount Display (Read-Only / Lock) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs text-slate-400 font-bold">পরিশোধিত অর্থ / মোট আদায় (BDT) *</label>
                        <span class="text-[10px] text-slate-500 font-semibold flex items-center gap-1">
                            🔒 অটো ক্যালকুলেট
                        </span>
                    </div>
                    <div class="relative">
                        <input
                            :value="payForm.amount"
                            type="number"
                            readonly
                            tabindex="-1"
                            class="w-full rounded-xl bg-[#081729] border border-brand-navy/80 p-3.5 text-emerald-400 font-mono text-xl font-black focus:outline-none cursor-not-allowed select-none opacity-95 shadow-inner"
                        />
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-mono font-bold text-slate-500">
                            BDT
                        </span>
                    </div>
                    <div class="mt-1 flex items-center justify-between text-[11px]">
                        <span class="text-slate-400">
                            প্যাকেজ রেট: <strong class="text-white font-mono">৳{{ packagePrice }}</strong>
                        </span>
                        <span v-if="payForm.discount > 0" class="text-brand-amber font-mono font-bold">
                            ছাড়: -৳{{ payForm.discount }}
                        </span>
                    </div>
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-xs text-slate-400 mb-1">পেমেন্ট মেথড (Payment Method)</label>
                    <select v-model="payForm.payment_method" class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-3 text-white focus:border-brand-sky focus:ring-brand-sky text-xs">
                        <option value="cash">নগদ ক্যাশ (Cash in Hand)</option>
                        <option value="bkash">বিকাশ (bKash)</option>
                        <option value="nagad">নগদ (Nagad)</option>
                    </select>
                </div>

                <!-- Note / Reference -->
                <div>
                    <label class="block text-xs text-slate-400 mb-1">মন্তব্য (নোট)</label>
                    <input
                        v-model="payForm.notes"
                        type="text"
                        placeholder="মাঠ পর্যায়ের বিল আদায়"
                        class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-2.5 text-xs text-white focus:border-brand-sky"
                    />
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showPayModal = false" class="flex-1 rounded-xl bg-[#0B1E36] border border-brand-navy p-2.5 text-xs font-semibold text-slate-300 hover:text-white">
                        বাতিল
                    </button>
                    <button
                        type="button"
                        @click="submitPayment"
                        :disabled="payForm.processing"
                        class="flex-1 rounded-xl bg-gradient-to-r from-brand-orange to-brand-amber p-2.5 text-xs font-bold text-white shadow-lg shadow-brand-orange/20 transition disabled:opacity-50 flex items-center justify-center gap-1.5"
                    >
                        <span v-if="payForm.processing">সংরক্ষণ হচ্ছে...</span>
                        <span v-else>আদায় কনফার্ম করুন</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Customer Note & Promise Modal -->
        <div v-if="showNoteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="w-full max-w-sm rounded-3xl border border-brand-navy bg-[#071527] p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">গ্রাহক নোট / বিলের প্রতিশ্রুতি</h3>
                    <button @click="showNoteModal = false" class="text-slate-400 hover:text-white text-sm">✕</button>
                </div>

                <form @submit.prevent="submitNote" class="space-y-3.5">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">নোটের ধরন</label>
                        <select v-model="noteForm.note_type" class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-2.5 text-xs text-white focus:border-brand-sky focus:ring-brand-sky">
                            <option value="promise_to_pay">📅 পরে বিল পরিশোধ করবে (Promise to Pay)</option>
                            <option value="issue_report">⚠️ সমস্যা / কমপ্লেইন সংক্রান্ত</option>
                            <option value="general_remark">💬 সাধারণ মন্তব্য (General Remark)</option>
                        </select>
                    </div>

                    <div v-if="noteForm.note_type === 'promise_to_pay'" class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">বিলের তারিখ *</label>
                            <input v-model="noteForm.promise_date" type="date" required class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-2 text-xs text-white font-mono focus:border-brand-sky" />
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">টাকার অঙ্ক (৳)</label>
                            <input v-model="noteForm.promise_amount" type="number" placeholder="500" class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-2 text-xs text-white font-mono focus:border-brand-sky" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">নোটের বিবরণ / ইউজারের বক্তব্য *</label>
                        <textarea
                            v-model="noteForm.note"
                            required
                            rows="3"
                            placeholder="যেমন: ইউজার জানিয়েছে আগামী ২০ তারিখে বিকাশ বা ক্যাশে বিল পরিশোধ করবেন..."
                            class="w-full rounded-xl bg-[#0B1E36] border-brand-navy p-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky"
                        ></textarea>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="showNoteModal = false" class="flex-1 rounded-xl bg-[#0B1E36] border border-brand-navy p-2.5 text-xs font-semibold text-slate-300 hover:text-white">
                            বাতিল
                        </button>
                        <button type="submit" :disabled="noteForm.processing" class="flex-1 rounded-xl bg-brand-sky hover:bg-brand-sky/90 p-2.5 text-xs font-bold text-white shadow-lg shadow-brand-sky/25">
                            {{ noteForm.processing ? 'সংরক্ষণ...' : 'সংরক্ষণ করুন' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </StaffLayout>
</template>
