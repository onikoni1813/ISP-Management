<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    notices: Array,
});

const isCreating = ref(false);
const editingNotice = ref(null);

const form = useForm({
    title: '',
    category: 'General',
    content: '',
    published_at: new Date().toISOString().slice(0, 10),
    is_published: true,
});

const categories = ['General', 'Maintenance', 'Expansion', 'System', 'Billing'];

const openCreateModal = () => {
    editingNotice.value = null;
    form.reset();
    form.clearErrors();
    form.category = 'General';
    form.published_at = new Date().toISOString().slice(0, 10);
    form.is_published = true;
    isCreating.value = true;
};

const openEditModal = (notice) => {
    editingNotice.value = notice;
    form.clearErrors();
    form.title = notice.title;
    form.category = notice.category || 'General';
    form.content = notice.content;
    form.published_at = notice.published_at ? notice.published_at.slice(0, 10) : '';
    form.is_published = Boolean(notice.is_published);
    isCreating.value = true;
};

const closeModal = () => {
    isCreating.value = false;
    editingNotice.value = null;
    form.reset();
};

const saveNotice = () => {
    if (editingNotice.value) {
        form.patch(route('admin.cms.notices.update', editingNotice.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.cms.notices.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const togglePublish = (notice) => {
    useForm({}).post(route('admin.cms.notices.toggle', notice.id));
};

const deleteNotice = (notice) => {
    if (confirm(`Are you sure you want to delete notice "${notice.title}"?`)) {
        useForm({}).delete(route('admin.cms.notices.destroy', notice.id));
    }
};
</script>

<template>
    <Head title="Manage Notices - Website CMS" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <Link :href="route('admin.cms.index')" class="hover:text-white">CMS</Link>
                        <span>/</span>
                        <span class="text-slate-200">Notices</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white mt-1">Network Notices & Bulletins</h1>
                    <p class="text-xs text-slate-400">Announce fiber maintenance windows, bandwidth expansions, and billing schedules.</p>
                </div>

                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-600/30 hover:from-rose-500 hover:to-pink-500 transition active:scale-95"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Notice
                </button>
            </div>

            <!-- Notices Table -->
            <div class="rounded-3xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="border-b border-slate-800 bg-slate-950/60 text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
                            <tr>
                                <th class="py-3.5 px-4">Notice Title</th>
                                <th class="py-3.5 px-4">Category</th>
                                <th class="py-3.5 px-4">Date</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-if="notices.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-500">
                                    No notices published yet. Click "New Notice" to broadcast an ISP announcement.
                                </td>
                            </tr>
                            <tr v-for="notice in notices" :key="notice.id" class="hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white">{{ notice.title }}</div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-md mt-0.5">{{ notice.content }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="rounded-full bg-rose-500/10 border border-rose-500/20 px-2.5 py-0.5 text-[10px] font-bold text-rose-300">
                                        {{ notice.category }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-400 font-mono">
                                    {{ notice.published_at ? new Date(notice.published_at).toLocaleDateString() : 'Draft' }}
                                </td>
                                <td class="py-3 px-4">
                                    <button
                                        @click="togglePublish(notice)"
                                        :class="[
                                            notice.is_published ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700',
                                            'rounded-full border px-2.5 py-1 text-[10px] font-bold transition hover:opacity-80'
                                        ]"
                                    >
                                        {{ notice.is_published ? 'Published' : 'Draft' }}
                                    </button>
                                </td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <button
                                        @click="openEditModal(notice)"
                                        class="rounded-lg p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                        title="Edit"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteNotice(notice)"
                                        class="rounded-lg p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                        title="Delete"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create / Edit Notice Modal -->
            <div v-if="isCreating" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                <div class="w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 p-6 space-y-5 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <h2 class="text-base font-bold text-white">{{ editingNotice ? 'Edit Notice' : 'Post New Notice' }}</h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveNotice" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Notice Headline / Title *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="e.g. Scheduled Core Optical Maintenance Window"
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-rose-500 focus:outline-none"
                            />
                            <span v-if="form.errors.title" class="text-rose-400 mt-1 block">{{ form.errors.title }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Category</label>
                                <select
                                    v-model="form.category"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-rose-500 focus:outline-none"
                                >
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Effective Date</label>
                                <input
                                    v-model="form.published_at"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-rose-500 focus:outline-none font-mono"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Notice Details & Instructions *</label>
                            <textarea
                                v-model="form.content"
                                rows="5"
                                required
                                placeholder="Detailed bulletin content for subscribers..."
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-rose-500 focus:outline-none"
                            ></textarea>
                            <span v-if="form.errors.content" class="text-rose-400 mt-1 block">{{ form.errors.content }}</span>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input
                                id="notice_is_published"
                                v-model="form.is_published"
                                type="checkbox"
                                class="rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500"
                            />
                            <label for="notice_is_published" class="font-medium text-slate-200">Published (Visible on /notices)</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-800">
                            <button
                                @click="closeModal"
                                type="button"
                                class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-bold text-slate-300 hover:bg-slate-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-xl bg-rose-600 px-5 py-2 text-xs font-bold text-white hover:bg-rose-500 disabled:opacity-50"
                            >
                                {{ editingNotice ? 'Update Notice' : 'Post Notice' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
