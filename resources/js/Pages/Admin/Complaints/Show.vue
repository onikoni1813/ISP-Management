<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatDateTime } from '@/Utils/date';

const props = defineProps({
    complaint: Object,
    staffUsers: Array,
});

const deleteModal = ref(false);
const isDeleting = ref(false);

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(route('admin.complaints.destroy', props.complaint.id), {
        onFinish: () => {
            isDeleting.value = false;
            deleteModal.value = false;
        }
    });
};

const assignForm = useForm({
    assigned_to: props.complaint.assigned_to || '',
});

const submitAssign = () => {
    assignForm.post(route('admin.complaints.assign', props.complaint.id));
};

const statusForm = useForm({
    status: props.complaint.status,
    resolution_note: props.complaint.resolution_note || '',
});

const submitStatus = () => {
    statusForm.post(route('complaints.status', props.complaint.id));
};

const commentForm = useForm({
    comment: '',
});

const submitComment = () => {
    commentForm.post(route('complaints.comment', props.complaint.id), {
        onSuccess: () => {
            commentForm.reset();
        }
    });
};
</script>

<template>
    <Head :title="`Ticket #${complaint.complaint_number} - Support`" />

    <AdminLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-bold text-indigo-400">{{ complaint.complaint_number }}</span>
                    <span class="rounded bg-slate-800 border border-slate-700 px-2 py-0.5 text-xs font-bold uppercase text-slate-300">
                        {{ complaint.status }}
                    </span>
                </div>
                <h1 class="text-2xl font-black text-white mt-1">{{ complaint.subject }}</h1>
                <p class="text-xs text-slate-400 mt-0.5">Reported by {{ complaint.customer?.name }} ({{ complaint.customer?.customer_code }})</p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    @click="deleteModal = true"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-500/30 bg-rose-950/20 hover:bg-rose-900/40 text-rose-400 hover:text-white px-3 py-1.5 text-xs font-bold transition shadow-sm"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Delete Ticket</span>
                </button>
                <Link :href="route('admin.complaints.index')" class="text-xs font-semibold text-slate-400 hover:text-white">
                    ← Back to Tickets
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Description & Comments -->
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm">
                    <h2 class="text-xs font-bold uppercase text-slate-400 mb-2">Complaint Description</h2>
                    <p class="text-sm text-slate-200 whitespace-pre-line leading-relaxed">{{ complaint.description }}</p>
                    <div v-if="complaint.resolution_note" class="mt-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs">
                        <div class="font-bold text-emerald-400 mb-1">Resolution Note:</div>
                        <p class="text-slate-200">{{ complaint.resolution_note }}</p>
                    </div>
                </div>

                <!-- Comments / Work Log -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4">
                    <h2 class="text-xs font-bold uppercase text-slate-400">Technician Comments & Work Log</h2>
                    <div class="divide-y divide-slate-800/80">
                        <div v-for="c in complaint.comments" :key="c.id" class="py-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-white">{{ c.user?.name }}</span>
                                <span class="text-slate-500 font-mono">{{ formatDateTime(c.created_at) }}</span>
                            </div>
                            <p class="text-xs text-slate-300 mt-1">{{ c.comment }}</p>
                        </div>
                    </div>

                    <form @submit.prevent="submitComment" class="pt-2 flex gap-2">
                        <input
                            v-model="commentForm.comment"
                            type="text"
                            required
                            placeholder="Add comment or progress update..."
                            class="flex-1 rounded-xl border-slate-800 bg-slate-950 p-2.5 text-xs text-white"
                        />
                        <button type="submit" :disabled="commentForm.processing" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white">
                            Post
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Assignment & Status Action -->
            <div class="space-y-6">
                <!-- Assign Staff -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-sm space-y-3">
                    <h2 class="text-xs font-bold uppercase text-slate-400">Assign Technician</h2>
                    <form @submit.prevent="submitAssign" class="space-y-3">
                        <select v-model="assignForm.assigned_to" class="w-full rounded-xl border-slate-800 bg-slate-950 p-2.5 text-xs text-white">
                            <option value="">-- Unassigned --</option>
                            <option v-for="s in staffUsers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <button type="submit" :disabled="assignForm.processing" class="w-full rounded-xl bg-indigo-600 p-2.5 text-xs font-bold text-white">
                            Update Assignment
                        </button>
                    </form>
                </div>

                <!-- Update Status -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-sm space-y-3">
                    <h2 class="text-xs font-bold uppercase text-slate-400">Update Status</h2>
                    <form @submit.prevent="submitStatus" class="space-y-3">
                        <select v-model="statusForm.status" class="w-full rounded-xl border-slate-800 bg-slate-950 p-2.5 text-xs text-white capitalize">
                            <option value="open">Open</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <textarea
                            v-if="statusForm.status === 'resolved' || statusForm.status === 'closed'"
                            v-model="statusForm.resolution_note"
                            rows="2"
                            placeholder="Resolution notes / action taken..."
                            class="w-full rounded-xl border-slate-800 bg-slate-950 p-2 text-xs text-white"
                        ></textarea>
                        <button type="submit" :disabled="statusForm.processing" class="w-full rounded-xl bg-emerald-600 p-2.5 text-xs font-bold text-white">
                            Save Status
                        </button>
                    </form>
                </div>

                <!-- Customer Details Card -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-sm text-xs space-y-2">
                    <h2 class="font-bold uppercase text-slate-400">Customer Summary</h2>
                    <div>
                        <span class="text-slate-500">Phone:</span>
                        <a :href="`tel:${complaint.customer?.primary_contact?.phone}`" class="text-emerald-400 font-mono font-bold ml-1">
                            {{ complaint.customer?.primary_contact?.phone }}
                        </a>
                    </div>
                    <div>
                        <span class="text-slate-500">Address:</span>
                        <span class="text-slate-300 ml-1">{{ complaint.customer?.installation_address?.full_address }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">PPPoE User:</span>
                        <span class="font-mono text-indigo-400 font-bold ml-1">{{ complaint.connection?.pppoe_credential?.username || 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Ticket Modal -->
        <div v-if="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="deleteModal = false"></div>
            <div class="relative w-full max-w-md rounded-3xl border border-rose-500/30 bg-slate-900 p-6 shadow-2xl space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-white">Delete Ticket</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Are you sure you want to permanently delete ticket <strong class="text-brand-sky font-mono">{{ complaint.complaint_number }}</strong>? This action cannot be undone.
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button
                        @click="deleteModal = false"
                        :disabled="isDeleting"
                        class="flex-1 rounded-xl border border-slate-700 bg-slate-800 py-2.5 text-xs font-bold text-slate-300 hover:bg-slate-700 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmDelete"
                        :disabled="isDeleting"
                        class="flex-1 rounded-xl bg-rose-600 hover:bg-rose-500 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-600/30 transition flex items-center justify-center gap-1.5"
                    >
                        <svg v-if="isDeleting" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Delete Ticket
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
