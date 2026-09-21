<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { formatDate, formatDateTime } from '@/Utils/date';

const props = defineProps({
    customer: Object,
    complaints: Object,
    nocHotline: String,
});

const showCreateModal = ref(false);
const activeReplyTicket = ref(null);

const createForm = useForm({
    subject: '',
    description: '',
    priority: 'normal',
});

const commentForm = useForm({
    comment: '',
});

const submitComplaint = () => {
    createForm.post(route('account.complaints.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const submitComment = (ticketId) => {
    if (!commentForm.comment.trim()) return;
    commentForm.post(route('account.complaints.comment', ticketId), {
        onSuccess: () => {
            commentForm.reset();
            activeReplyTicket.value = null;
        }
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'resolved':
            return { label: 'Resolved (সমাধান হয়েছে)', class: 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' };
        case 'in_progress':
            return { label: 'In Progress (কাজ চলছে)', class: 'bg-sky-500/15 text-sky-400 border border-sky-500/30' };
        case 'assigned':
            return { label: 'Assigned (টেকনিশিয়ান নিযুক্ত)', class: 'bg-blue-500/15 text-blue-300 border border-blue-500/30' };
        case 'closed':
            return { label: 'Closed (বন্ধ)', class: 'bg-slate-800 text-slate-400 border border-slate-700' };
        default:
            return { label: 'Open (অপেক্ষমান)', class: 'bg-amber-500/15 text-amber-400 border border-amber-500/30' };
    }
};

const getPriorityBadge = (priority) => {
    switch (priority) {
        case 'urgent':
            return { label: 'Urgent', class: 'bg-rose-500/20 text-rose-300 border border-rose-500/40 animate-pulse' };
        case 'high':
            return { label: 'High', class: 'bg-orange-500/20 text-orange-300 border border-orange-500/30' };
        case 'low':
            return { label: 'Low', class: 'bg-slate-800 text-slate-400 border border-slate-700' };
        default:
            return { label: 'Normal', class: 'bg-slate-800 text-slate-300 border border-slate-700' };
    }
};
</script>

<template>
    <Head title="Support & Complaints - Pirgacha Internet" />

    <CustomerLayout>
        <div class="space-y-6">
            <!-- Header Title and Action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-brand-orange animate-pulse"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-orange">24/7 NOC Support Desk</span>
                    </div>
                    <h1 class="text-2xl font-black text-white tracking-tight mt-1">সাপোর্ট ও অভিযোগ (Support & Complaints)</h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        সংযোগ সমস্যা বা কারিগরি যেকোনো সমস্যায় টিকিট তৈরি করুন অথবা হটলাইনে যোগাযোগ করুন
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="showCreateModal = true"
                        class="rounded-2xl bg-gradient-to-r from-brand-orange to-amber-500 hover:from-orange-500 hover:to-amber-400 px-5 py-3 text-xs font-black uppercase tracking-wider text-white shadow-xl shadow-brand-orange/20 transition active:scale-95 flex items-center gap-2 cursor-pointer"
                    >
                        <span>➕</span>
                        <span>নতুন টিকিট তৈরি করুন</span>
                    </button>
                </div>
            </div>

            <!-- Emergency NOC Contact Banner -->
            <div class="rounded-2xl border border-[#17304F] bg-[#071527]/90 p-4 backdrop-blur-md flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-md">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📞</span>
                    <div>
                        <div class="text-xs font-bold text-white">জরুরী কারিগরি সহায়তায় NOC হটলাইন</div>
                        <div class="text-[11px] text-slate-400">অপটিক্যাল ফাইবার কর্তন বা সম্পূর্ণ সংযোগ বিচ্ছিন্ন হলে সরাসরি কল করুন</div>
                    </div>
                </div>
                <a 
                    :href="`tel:${(nocHotline || $page.props.company?.hotline || '01711000000').split('/')[0].trim().replace(/[^0-9+]/g, '')}`"
                    class="flex items-center gap-2 font-mono font-bold text-xs bg-[#040D18] hover:bg-[#0B1E36] border border-[#1E3E66] hover:border-brand-orange px-3.5 py-1.5 rounded-xl text-brand-orange transition cursor-pointer"
                    title="সরাসরি কল করতে ট্যাপ করুন"
                >
                    <span>HOTLINE:</span>
                    <span>{{ nocHotline || $page.props.company?.hotline || '01711-000000 / 01722-000000' }}</span>
                </a>
            </div>

            <!-- Tickets Listing -->
            <div class="space-y-4">
                <div
                    v-for="ticket in complaints.data"
                    :key="ticket.id"
                    class="rounded-3xl border border-[#17304F] bg-[#071527]/95 p-6 backdrop-blur-md space-y-4 shadow-xl hover:border-slate-700 transition"
                >
                    <!-- Ticket Header Details -->
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 pb-3 border-b border-[#17304F]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-mono font-black text-brand-orange">
                                    #{{ ticket.complaint_number }}
                                </span>
                                <span :class="[getStatusBadge(ticket.status).class, 'rounded-lg px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider']">
                                    {{ getStatusBadge(ticket.status).label }}
                                </span>
                                <span :class="[getPriorityBadge(ticket.priority).class, 'rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase font-mono']">
                                    {{ getPriorityBadge(ticket.priority).label }} Priority
                                </span>
                            </div>
                            <h2 class="text-base font-bold text-white mt-1.5">{{ ticket.subject }}</h2>
                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">{{ ticket.description }}</p>
                        </div>

                        <div class="sm:text-right shrink-0">
                            <span class="text-[10px] font-bold uppercase text-slate-500 block">দাখিল তারিখ</span>
                            <span class="text-xs font-mono text-slate-400 font-semibold mt-0.5 block">
                                {{ formatDate(ticket.created_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Resolution Note if Resolved -->
                    <div v-if="ticket.resolution_note" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 space-y-1">
                        <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                            <span>✓</span>
                            <span>সমাধানের বিবরণ (Resolution Note)</span>
                        </div>
                        <p class="text-xs text-emerald-200 leading-relaxed">{{ ticket.resolution_note }}</p>
                    </div>

                    <!-- Comments & Staff Replies Thread -->
                    <div v-if="ticket.comments?.length > 0" class="pt-2 space-y-2.5">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                            <span>💬</span>
                            <span>আলোচনা ও অগ্রগতি (Updates & Replies)</span>
                        </div>
                        <div
                            v-for="c in ticket.comments"
                            :key="c.id"
                            class="rounded-2xl border border-[#1E3A5F] bg-[#040D18] p-3.5 text-xs space-y-1.5 shadow-inner"
                        >
                            <div class="flex items-center justify-between text-[11px] pb-1 border-b border-[#17304F]">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-brand-cyan"></span>
                                    <span class="font-bold text-white">{{ c.user?.name || 'Support Agent' }}</span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-[#0B1E36] text-slate-400 font-mono">NOC</span>
                                </div>
                                <span class="text-[10px] text-slate-500 font-mono">{{ formatDateTime(c.created_at) }}</span>
                            </div>
                            <p class="text-slate-300 leading-relaxed pt-0.5">{{ c.comment }}</p>
                        </div>
                    </div>

                    <!-- Reply Action / Button -->
                    <div class="pt-2 flex justify-end">
                        <button
                            v-if="ticket.status !== 'closed' && activeReplyTicket !== ticket.id"
                            @click="activeReplyTicket = ticket.id"
                            class="rounded-xl border border-[#1E3A5F] bg-[#0B1E36] hover:bg-[#122B4D] hover:border-brand-orange px-3 py-1.5 text-xs font-bold text-brand-orange hover:text-white transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>✍️</span>
                            <span>উত্তর দিন / Reply to Support →</span>
                        </button>
                    </div>

                    <!-- Inline Reply Input Form -->
                    <div v-if="activeReplyTicket === ticket.id" class="pt-2 space-y-2.5 rounded-2xl border border-[#1E3E66] bg-[#040D18] p-4">
                        <div class="text-xs font-bold text-slate-300">সাপোর্ট টিমকে আপনার বার্তা বা অতিরিক্ত তথ্য লিখুন:</div>
                        <textarea
                            v-model="commentForm.comment"
                            rows="2"
                            placeholder="এখানে লিখুন... (যেমন: এখনো সমস্যা হচ্ছে বা সমাধান হয়েছে)"
                            class="w-full rounded-xl border-2 border-[#1E3E66] bg-[#0B1E36] p-3 text-xs text-white placeholder-slate-500 focus:border-brand-orange focus:bg-[#0E2442] focus:ring-2 focus:ring-brand-orange/20 outline-none transition"
                        ></textarea>
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                @click="activeReplyTicket = null"
                                class="rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 px-3.5 py-2 text-xs font-semibold text-slate-300 transition cursor-pointer"
                            >
                                বাতিল
                            </button>
                            <button
                                type="button"
                                @click="submitComment(ticket.id)"
                                :disabled="commentForm.processing || !commentForm.comment.trim()"
                                class="rounded-xl bg-brand-orange hover:bg-orange-600 px-4 py-2 text-xs font-bold text-white transition disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                            >
                                <span v-if="commentForm.processing" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>পাঠিয়ে দিন</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!complaints.data?.length" class="rounded-3xl border border-[#17304F] bg-[#071527]/90 p-12 text-center space-y-3 shadow-xl">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center text-xl mx-auto">
                        ✓
                    </div>
                    <h3 class="text-base font-bold text-white">বর্তমানে কোনো সমস্যা বা অভিযোগ নেই</h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed">
                        আপনার ইন্টারনেট সংযোগ সচল ও স্বাভাবিক রয়েছে। কোনো ধরনের সমস্যা দেখা দিলে উপরের বাটন থেকে টিকিট খুলুন।
                    </p>
                </div>

                <!-- Pagination if multiple pages -->
                <div v-if="complaints.links?.length > 3" class="flex justify-center items-center gap-1 pt-4">
                    <template v-for="(link, i) in complaints.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                link.active 
                                    ? 'bg-brand-orange text-white font-bold' 
                                    : 'bg-[#0B1E36] text-slate-400 hover:text-white hover:bg-[#122B4D]',
                                'px-3 py-1.5 rounded-xl text-xs transition border border-[#1C3A5E]'
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1.5 text-xs text-slate-600 font-medium"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Create Complaint Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="w-full max-w-lg rounded-3xl border border-[#17304F] bg-[#071527] p-6 sm:p-8 shadow-2xl space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-[#17304F]">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-brand-orange block">নতুন সাপোর্ট টিকিট</span>
                        <h3 class="text-base font-black text-white">সমস্যার বিবরণ দিন (Log Ticket)</h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-white text-base cursor-pointer">✕</button>
                </div>

                <!-- Form Validation Alert -->
                <div v-if="createForm.hasErrors" class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 text-xs text-rose-300 space-y-1">
                    <div v-for="(err, key) in createForm.errors" :key="key" class="font-medium">• {{ err }}</div>
                </div>

                <form @submit.prevent="submitComplaint" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            সমস্যার বিষয় (Subject) <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="createForm.subject"
                            type="text"
                            required
                            placeholder="যেমন: রাউটারে লাল বাতি জ্বলছে / স্পিড কম পাওয়া যাচ্ছে"
                            class="w-full rounded-2xl border-2 border-[#1E3E66] bg-[#0B1E36] p-3.5 text-xs text-white placeholder-slate-500 focus:border-brand-orange focus:bg-[#0E2442] focus:ring-2 focus:ring-brand-orange/20 outline-none transition"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            সমস্যার ধরন / অগ্রাধিকার (Severity / Priority)
                        </label>
                        <select
                            v-model="createForm.priority"
                            class="w-full rounded-2xl border-2 border-[#1E3E66] bg-[#0B1E36] p-3.5 text-xs text-white outline-none focus:border-brand-orange cursor-pointer"
                        >
                            <option value="low">Low (সাধারণ তথ্য বা জিজ্ঞাসা)</option>
                            <option value="normal">Normal (সামান্য স্পিড ওঠা-নামা)</option>
                            <option value="high">High (ইন্টারনেট সম্পূর্ণ বন্ধ / নো কানেকশন)</option>
                            <option value="urgent">Urgent (অপটিক্যাল ফাইবার তার কাটা পড়েছে)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            বিস্তারিত লক্ষণ বা বিবরণ (Description) <span class="text-rose-400">*</span>
                        </label>
                        <textarea
                            v-model="createForm.description"
                            rows="4"
                            required
                            placeholder="কখন থেকে সমস্যা শুরু হয়েছে এবং রাউটারের লাইট কী অবস্থায় আছে বিস্তারিত লিখুন..."
                            class="w-full rounded-2xl border-2 border-[#1E3E66] bg-[#0B1E36] p-3.5 text-xs text-white placeholder-slate-500 focus:border-brand-orange focus:bg-[#0E2442] focus:ring-2 focus:ring-brand-orange/20 outline-none transition"
                        ></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="flex-1 rounded-2xl border border-slate-700 bg-slate-800 hover:bg-slate-700 p-3.5 text-xs font-bold text-slate-300 transition cursor-pointer"
                        >
                            বাতিল
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="flex-1 rounded-2xl bg-gradient-to-r from-brand-orange to-amber-500 hover:from-orange-500 hover:to-amber-400 p-3.5 text-xs font-black uppercase tracking-wider text-white shadow-xl shadow-brand-orange/25 transition active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span v-if="createForm.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>{{ createForm.processing ? 'দাখিল হচ্ছে...' : 'টিকিট জমা দিন →' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </CustomerLayout>
</template>
