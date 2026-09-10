<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    complaint: Object,
    staffUsers: Array,
});

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
            <Link :href="route('admin.complaints.index')" class="text-xs font-semibold text-slate-400 hover:text-white">
                ← Back to Tickets
            </Link>
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
                                <span class="text-slate-500 font-mono">{{ c.created_at }}</span>
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
    </AdminLayout>
</template>
