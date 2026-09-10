<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    customer: Object,
    complaints: Object,
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
    commentForm.post(route('account.complaints.comment', ticketId), {
        onSuccess: () => {
            commentForm.reset();
            activeReplyTicket.value = null;
        }
    });
};
</script>

<template>
    <Head title="Support Tickets - Pirgacha Internet" />

    <CustomerLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-black text-white">Support & Complaints</h1>
                <p class="text-xs text-slate-400 mt-0.5">Submit connection issues directly to Pirgacha NOC technicians</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="rounded-2xl bg-cyan-600 hover:bg-cyan-500 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-cyan-600/20 transition active:scale-95"
            >
                + New Ticket
            </button>
        </div>

        <!-- Tickets Listing -->
        <div class="space-y-4">
            <div
                v-for="ticket in complaints.data"
                :key="ticket.id"
                class="rounded-3xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-sm space-y-3"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-bold text-cyan-400">#{{ ticket.complaint_number }}</span>
                            <span 
                                :class="[
                                    ticket.status === 'resolved' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                    'rounded-md border px-2 py-0.5 text-[10px] font-extrabold uppercase'
                                ]"
                            >
                                {{ ticket.status }}
                            </span>
                            <span class="text-[10px] text-slate-500 uppercase font-mono">{{ ticket.priority }} priority</span>
                        </div>
                        <h2 class="text-base font-bold text-white mt-1">{{ ticket.subject }}</h2>
                        <p class="text-xs text-slate-300 mt-1">{{ ticket.description }}</p>
                    </div>

                    <div class="text-xs text-slate-500 text-right">
                        {{ ticket.created_at?.split('T')[0] }}
                    </div>
                </div>

                <!-- Comments Thread -->
                <div v-if="ticket.comments?.length > 0" class="pt-3 border-t border-slate-800/60 space-y-2">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Updates & Replies</div>
                    <div v-for="c in ticket.comments" :key="c.id" class="rounded-xl bg-slate-950 p-3 text-xs space-y-1">
                        <div class="flex items-center justify-between text-[10px] text-slate-400 font-medium">
                            <span class="font-bold text-slate-200">{{ c.user?.name || 'Support Agent' }}</span>
                            <span>{{ c.created_at?.split('T')[0] }}</span>
                        </div>
                        <p class="text-slate-300">{{ c.comment }}</p>
                    </div>
                </div>

                <!-- Reply Action -->
                <div class="pt-2 flex justify-end">
                    <button
                        v-if="ticket.status !== 'closed' && activeReplyTicket !== ticket.id"
                        @click="activeReplyTicket = ticket.id"
                        class="text-xs font-bold text-cyan-400 hover:text-cyan-300"
                    >
                        Reply to Support →
                    </button>
                </div>

                <!-- Reply Input -->
                <div v-if="activeReplyTicket === ticket.id" class="pt-2 space-y-2">
                    <textarea
                        v-model="commentForm.comment"
                        rows="2"
                        placeholder="Write your reply or clarification..."
                        class="w-full rounded-2xl border-slate-800 bg-slate-950 p-3 text-xs text-white placeholder-slate-500 focus:border-cyan-500 focus:ring-cyan-500"
                    ></textarea>
                    <div class="flex justify-end gap-2">
                        <button @click="activeReplyTicket = null" class="rounded-xl bg-slate-800 px-3 py-1.5 text-xs text-slate-300">Cancel</button>
                        <button @click="submitComment(ticket.id)" :disabled="commentForm.processing" class="rounded-xl bg-cyan-600 px-3 py-1.5 text-xs font-bold text-white">Send</button>
                    </div>
                </div>
            </div>

            <div v-if="!complaints.data?.length" class="rounded-3xl border border-slate-800 bg-slate-900/40 p-8 text-center text-xs text-slate-500">
                No support complaints logged. Everything running smoothly!
            </div>
        </div>

        <!-- Create Complaint Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Log Support Ticket</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Issue Subject</label>
                    <input v-model="createForm.subject" type="text" placeholder="e.g. Red light on router / Low speed" class="w-full rounded-xl bg-slate-950 border-slate-800 p-3 text-xs text-white" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Severity / Priority</label>
                    <select v-model="createForm.priority" class="w-full rounded-xl bg-slate-950 border-slate-800 p-3 text-xs text-white">
                        <option value="low">Low (General Query)</option>
                        <option value="normal">Normal (Speed fluctuation)</option>
                        <option value="high">High (No Internet / Disconnected)</option>
                        <option value="urgent">Urgent (Physical Optical Fiber Cut)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Description</label>
                    <textarea v-model="createForm.description" rows="3" placeholder="Explain the symptoms you are experiencing..." class="w-full rounded-xl bg-slate-950 border-slate-800 p-3 text-xs text-white"></textarea>
                </div>

                <div class="flex gap-2 pt-2">
                    <button @click="showCreateModal = false" class="flex-1 rounded-xl bg-slate-800 p-3 text-xs font-bold text-slate-300">Cancel</button>
                    <button @click="submitComplaint" :disabled="createForm.processing" class="flex-1 rounded-xl bg-cyan-600 p-3 text-xs font-bold text-white">Submit Ticket</button>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
