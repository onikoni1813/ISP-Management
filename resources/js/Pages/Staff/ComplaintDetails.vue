<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    complaint: Object,
});

const statusForm = useForm({
    status: props.complaint.status,
    resolution_note: props.complaint.resolution_note || '',
});

const submitStatus = () => {
    statusForm.post(route('complaints.status', props.complaint.id), {
        preserveScroll: true,
    });
};

const commentForm = useForm({
    comment: '',
});

const submitComment = () => {
    commentForm.post(route('complaints.comment', props.complaint.id), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
        }
    });
};

const statusColors = {
    open: 'bg-rose-500/20 text-rose-400 border-rose-500/30',
    assigned: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
    in_progress: 'bg-brand-sky/20 text-brand-sky border-brand-sky/30',
    resolved: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
    closed: 'bg-slate-700/50 text-slate-300 border-slate-600',
};

const priorityColors = {
    urgent: 'text-rose-400 bg-rose-500/10 border-rose-500/30',
    high: 'text-amber-400 bg-amber-500/10 border-amber-500/30',
    normal: 'text-brand-sky bg-brand-sky/10 border-brand-sky/30',
    low: 'text-slate-400 bg-slate-500/10 border-slate-500/30',
};
</script>

<template>
    <Head :title="`Ticket #${complaint.complaint_number} - Staff Portal`" />

    <StaffLayout>
        <!-- Background decorative glows -->
        <div class="fixed inset-0 pointer-events-none z-[-1] overflow-hidden">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-brand-sky/10 blur-[120px]"></div>
            <div class="absolute top-[40%] -right-[20%] w-[60%] h-[60%] rounded-full bg-brand-orange/5 blur-[120px]"></div>
        </div>

        <div class="space-y-5 animate-fade-in-up max-w-4xl mx-auto">
            <!-- Top Navigation & Title Bar -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('staff.dashboard', { tab: 'complaints' })"
                    class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white bg-[#0B1E36]/80 px-3 py-2 rounded-xl border border-white/10 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>ড্যাশবোর্ডে ফিরুন</span>
                </Link>

                <div class="flex items-center gap-2">
                    <span :class="['px-2.5 py-1 rounded-full text-xs font-bold uppercase border', priorityColors[complaint.priority] || 'border-white/10 text-slate-300']">
                        {{ complaint.priority }}
                    </span>
                    <span :class="['px-2.5 py-1 rounded-full text-xs font-bold uppercase border', statusColors[complaint.status] || 'border-white/10 text-slate-300']">
                        {{ complaint.status }}
                    </span>
                </div>
            </div>

            <!-- Header Card -->
            <div class="rounded-3xl border border-white/10 bg-[#071527]/80 backdrop-blur-xl p-5 shadow-xl">
                <div class="flex items-center justify-between text-xs text-slate-400 mb-1 font-mono">
                    <span>Ticket #{{ complaint.complaint_number }}</span>
                    <span>{{ formatDateTime(complaint.created_at) }}</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-white leading-snug">{{ complaint.subject }}</h1>
                <p class="text-sm text-slate-300 mt-3 whitespace-pre-line leading-relaxed bg-[#0B1E36]/50 p-3.5 rounded-2xl border border-white/5">
                    {{ complaint.description }}
                </p>
                <div v-if="complaint.resolution_note" class="mt-3 p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-xs">
                    <div class="font-bold text-emerald-400 mb-1">সমাধান নোট (Resolution Note):</div>
                    <p class="text-slate-200">{{ complaint.resolution_note }}</p>
                </div>
            </div>

            <!-- Customer Details Card with Direct Call -->
            <div class="rounded-3xl border border-white/10 bg-[#071527]/80 backdrop-blur-xl p-5 shadow-xl space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">গ্রাহকের বিবরণ (Customer Details)</h2>
                    <Link 
                        v-if="complaint.customer?.id" 
                        :href="route('staff.customer-details', complaint.customer.id)"
                        class="text-xs text-brand-sky hover:underline font-bold"
                    >
                        প্রোফাইল দেখুন →
                    </Link>
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-[#0B1E36]/50 p-4 rounded-2xl border border-white/5">
                    <div>
                        <div class="text-base font-black text-white">{{ complaint.customer?.name }}</div>
                        <div class="text-xs font-mono text-slate-400">{{ complaint.customer?.customer_code }}</div>
                    </div>

                    <div v-if="complaint.customer?.primary_contact?.phone" class="flex items-center gap-2">
                        <a
                            :href="`tel:${complaint.customer.primary_contact.phone}`"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-emerald-600/30 transition-all active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <span>কল করুন ({{ complaint.customer.primary_contact.phone }})</span>
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-1">
                    <div class="bg-[#0B1E36]/30 p-3 rounded-xl border border-white/5">
                        <span class="text-slate-400 block mb-1">ঠিকানা / লোকেশন:</span>
                        <span class="text-slate-200 font-medium">{{ complaint.customer?.installation_address?.full_address || 'N/A' }}</span>
                    </div>
                    <div class="bg-[#0B1E36]/30 p-3 rounded-xl border border-white/5">
                        <span class="text-slate-400 block mb-1">PPPoE ইউজারনেম & প্যাকেজ:</span>
                        <span class="font-mono text-brand-cyan font-bold block">{{ complaint.connection?.pppoe_credential?.username || 'N/A' }}</span>
                        <span class="text-slate-300">{{ complaint.connection?.current_package?.name || 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Update Status Action Card -->
            <div class="rounded-3xl border border-white/10 bg-[#071527]/80 backdrop-blur-xl p-5 shadow-xl space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">স্ট্যাটাস আপডেট করুন (Update Ticket Status)</h2>
                
                <form @submit.prevent="submitStatus" class="space-y-3">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button
                            type="button"
                            v-for="st in ['assigned', 'in_progress', 'resolved', 'closed']"
                            :key="st"
                            @click="statusForm.status = st"
                            :class="[
                                'py-2 px-3 rounded-xl text-xs font-bold uppercase border transition-all',
                                statusForm.status === st
                                    ? 'bg-brand-sky text-white border-brand-sky shadow-lg shadow-brand-sky/20'
                                    : 'bg-[#0B1E36]/60 border-white/10 text-slate-400 hover:text-white'
                            ]"
                        >
                            {{ st.replace('_', ' ') }}
                        </button>
                    </div>

                    <div v-if="statusForm.status === 'resolved' || statusForm.status === 'closed'">
                        <label class="block text-xs text-slate-400 mb-1 font-semibold">সমাধানের বিবরণ / রেজোলিউশন নোট:</label>
                        <textarea
                            v-model="statusForm.resolution_note"
                            rows="2"
                            placeholder="কী সমাধান করা হয়েছে লিখুন..."
                            class="w-full rounded-2xl border-white/10 bg-[#0B1E36] p-3 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:ring-0"
                            required
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        :disabled="statusForm.processing"
                        class="w-full py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition-all disabled:opacity-50"
                    >
                        {{ statusForm.processing ? 'সংরক্ষণ হচ্ছে...' : 'স্ট্যাটাস আপডেট সেভ করুন' }}
                    </button>
                </form>
            </div>

            <!-- Technician Comments / Field Work Log -->
            <div class="rounded-3xl border border-white/10 bg-[#071527]/80 backdrop-blur-xl p-5 shadow-xl space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">কাজের অগ্রগতি ও মন্তব্য (Work Log & Notes)</h2>

                <div v-if="complaint.comments?.length > 0" class="space-y-3">
                    <div
                        v-for="c in complaint.comments"
                        :key="c.id"
                        class="p-3.5 rounded-2xl bg-[#0B1E36]/60 border border-white/5 space-y-1"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-brand-sky">{{ c.user?.name }}</span>
                            <span class="text-slate-500 font-mono text-[10px]">{{ formatDateTime(c.created_at) }}</span>
                        </div>
                        <p class="text-xs text-slate-200 whitespace-pre-line">{{ c.comment }}</p>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-500 text-center py-4 bg-[#0B1E36]/30 rounded-2xl">
                    এখনো কোনো কাজের নোট যোগ করা হয়নি।
                </div>

                <form @submit.prevent="submitComment" class="flex gap-2 pt-2">
                    <input
                        v-model="commentForm.comment"
                        type="text"
                        required
                        placeholder="কাজের কোনো নোট বা প্রগ্রেস লিখুন..."
                        class="flex-1 rounded-2xl border-white/10 bg-[#0B1E36] px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-brand-sky focus:ring-0"
                    />
                    <button
                        type="submit"
                        :disabled="commentForm.processing"
                        class="rounded-2xl bg-brand-sky hover:bg-brand-sky/90 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-brand-sky/20 transition-all disabled:opacity-50"
                    >
                        যোগ করুন
                    </button>
                </form>
            </div>
        </div>
    </StaffLayout>
</template>
