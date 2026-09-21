<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { formatDate } from '@/Utils/date';

const props = defineProps({
    pages: Array,
});

const isCreating = ref(false);
const editingPage = ref(null);
const searchQuery = ref('');

const showDeleteConfirm = ref(false);
const pageToDelete = ref(null);
const deletingPage = ref(false);

const filteredPages = computed(() => {
    if (!searchQuery.value.trim()) return props.pages;
    const q = searchQuery.value.toLowerCase();
    return props.pages.filter(p => 
        p.title.toLowerCase().includes(q) ||
        p.slug.toLowerCase().includes(q) ||
        (p.seo_title && p.seo_title.toLowerCase().includes(q)) ||
        (p.content && p.content.toLowerCase().includes(q))
    );
});

const form = useForm({
    title: '',
    slug: '',
    content: '',
    seo_title: '',
    seo_description: '',
    is_published: true,
});

const openCreateModal = () => {
    editingPage.value = null;
    form.reset();
    form.clearErrors();
    form.is_published = true;
    isCreating.value = true;
};

const openEditModal = (page) => {
    editingPage.value = page;
    form.clearErrors();
    form.title = page.title;
    form.slug = page.slug;
    form.content = page.content;
    form.seo_title = page.seo_title || '';
    form.seo_description = page.seo_description || '';
    form.is_published = Boolean(page.is_published);
    isCreating.value = true;
};

const closeModal = () => {
    isCreating.value = false;
    editingPage.value = null;
    form.reset();
};

const autoSlug = () => {
    if (!editingPage.value) {
        form.slug = form.title
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-');
    }
};

const submit = () => {
    if (editingPage.value) {
        form.patch(route('admin.cms.pages.update', editingPage.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.cms.pages.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const togglePublish = (page) => {
    useForm({}).post(route('admin.cms.pages.toggle', page.id));
};

const deletePage = (page) => {
    pageToDelete.value = page;
    showDeleteConfirm.value = true;
};

const confirmDeletePage = () => {
    if (!pageToDelete.value) return;
    deletingPage.value = true;
    router.delete(route('admin.cms.pages.destroy', pageToDelete.value.id), {
        onFinish: () => {
            deletingPage.value = false;
            showDeleteConfirm.value = false;
            pageToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Manage Pages - Website CMS" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <Link :href="route('admin.cms.index')" class="hover:text-white">CMS</Link>
                        <span>/</span>
                        <span class="text-slate-200">Custom Pages</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white mt-1">Website Pages & Articles</h1>
                    <p class="text-xs text-slate-400">Manage dynamic pages, slug URLs, SEO tags, and publish status.</p>
                </div>

                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-indigo-600/30 hover:from-indigo-500 hover:to-violet-500 transition active:scale-95"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Page
                </button>
            </div>

            <!-- Search Bar -->
            <div class="flex items-center justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search pages by title, slug or content..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-900/80 border border-slate-800 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                    />
                    <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div class="text-xs text-slate-400 font-mono">
                    Showing {{ filteredPages.length }} of {{ pages.length }} pages
                </div>
            </div>

            <!-- Mobile View: Cards (block md:hidden) -->
            <div class="block md:hidden space-y-3">
                <div v-if="filteredPages.length === 0" class="rounded-3xl border border-slate-800 bg-slate-900/60 p-8 text-center text-slate-500 text-xs">
                    No custom pages found matching your search.
                </div>
                <div
                    v-for="page in filteredPages"
                    :key="'mobile-' + page.id"
                    class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 space-y-3 shadow-lg"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs text-slate-500">Order: #{{ page.order }}</span>
                        <button
                            @click="togglePublish(page)"
                            :class="[
                                page.is_published ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700',
                                'rounded-full border px-2.5 py-0.5 text-[10px] font-bold transition hover:opacity-80'
                            ]"
                        >
                            {{ page.is_published ? 'Published' : 'Draft' }}
                        </button>
                    </div>

                    <div>
                        <div class="font-bold text-white text-sm">{{ page.title }}</div>
                        <div class="font-mono text-xs text-cyan-400 mt-0.5">/p/{{ page.slug }}</div>
                    </div>

                    <div v-if="page.seo_title" class="text-xs text-slate-400 italic">
                        SEO: {{ page.seo_title }}
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 text-xs">
                        <span class="text-slate-500">{{ formatDate(page.updated_at) }}</span>
                        <div class="flex items-center gap-2">
                            <a
                                v-if="page.is_published"
                                :href="route('page.custom', page.slug)"
                                target="_blank"
                                class="rounded-lg p-1.5 text-cyan-400 bg-cyan-500/10 hover:bg-cyan-500/20 transition"
                                title="View Live"
                            >
                                Live ↗
                            </a>
                            <button
                                @click="openEditModal(page)"
                                class="rounded-lg p-1.5 text-slate-300 bg-slate-800 hover:text-white transition"
                            >
                                Edit
                            </button>
                            <button
                                @click="deletePage(page)"
                                class="rounded-lg p-1.5 text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 transition"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop View: Table (hidden md:block) -->
            <div class="hidden md:block rounded-3xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="border-b border-slate-800 bg-slate-950/60 text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
                            <tr>
                                <th class="py-3.5 px-4">Order</th>
                                <th class="py-3.5 px-4">Title & Slug</th>
                                <th class="py-3.5 px-4">SEO Meta</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Last Updated</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-if="filteredPages.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-500">
                                    No custom pages found matching your search.
                                </td>
                            </tr>
                            <tr v-for="page in filteredPages" :key="page.id" class="hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4 font-mono text-slate-400">#{{ page.order }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-white">{{ page.title }}</div>
                                    <div class="font-mono text-[11px] text-cyan-400">/p/{{ page.slug }}</div>
                                </td>
                                <td class="py-3 px-4 max-w-xs truncate text-slate-400">
                                    <span v-if="page.seo_title" class="text-slate-300">{{ page.seo_title }}</span>
                                    <span v-else class="italic text-slate-600">No custom SEO title</span>
                                </td>
                                <td class="py-3 px-4">
                                    <button
                                        @click="togglePublish(page)"
                                        :class="[
                                            page.is_published ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700',
                                            'rounded-full border px-2.5 py-1 text-[10px] font-bold transition hover:opacity-80'
                                        ]"
                                    >
                                        {{ page.is_published ? 'Published' : 'Draft' }}
                                    </button>
                                </td>
                                <td class="py-3 px-4 text-slate-400">
                                    {{ formatDate(page.updated_at) }}
                                </td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <a
                                        v-if="page.is_published"
                                        :href="route('page.custom', page.slug)"
                                        target="_blank"
                                        class="inline-block rounded-lg p-1.5 text-slate-400 hover:text-cyan-400 hover:bg-slate-800 transition"
                                        title="View Live Page"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                    <button
                                        @click="openEditModal(page)"
                                        class="rounded-lg p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                        title="Edit Page"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deletePage(page)"
                                        class="rounded-lg p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                        title="Delete Page"
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

            <!-- Page Create/Edit Modal -->
            <div v-if="isCreating" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                <div class="w-full max-w-2xl rounded-3xl border border-slate-800 bg-slate-900 p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <h2 class="text-base font-bold text-white">{{ editingPage ? 'Edit Page' : 'Create New Page' }}</h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="savePage" class="space-y-4 text-xs">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Page Title *</label>
                                <input
                                    v-model="form.title"
                                    @input="autoSlug"
                                    type="text"
                                    required
                                    placeholder="e.g. Terms of Service"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-indigo-500 focus:outline-none"
                                />
                                <span v-if="form.errors.title" class="text-rose-400 mt-1 block">{{ form.errors.title }}</span>
                            </div>

                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Slug URL (/p/...) *</label>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    required
                                    placeholder="terms-of-service"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white font-mono focus:border-indigo-500 focus:outline-none"
                                />
                                <span v-if="form.errors.slug" class="text-rose-400 mt-1 block">{{ form.errors.slug }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Page Content (HTML / Markdown text)</label>
                            <textarea
                                v-model="form.content"
                                rows="6"
                                placeholder="Enter page detailed body text..."
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-indigo-500 focus:outline-none font-sans"
                            ></textarea>
                            <span v-if="form.errors.content" class="text-rose-400 mt-1 block">{{ form.errors.content }}</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">SEO Meta Title</label>
                                <input
                                    v-model="form.seo_title"
                                    type="text"
                                    placeholder="Title tag for search engines"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-indigo-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Display Order</label>
                                <input
                                    v-model.number="form.order"
                                    type="number"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-indigo-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">SEO Meta Description</label>
                            <textarea
                                v-model="form.seo_description"
                                rows="2"
                                placeholder="Short synopsis for Google search results..."
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-indigo-500 focus:outline-none"
                            ></textarea>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input
                                id="is_published"
                                v-model="form.is_published"
                                type="checkbox"
                                class="rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="is_published" class="font-medium text-slate-200">Publish immediately to public website</label>
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
                                class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white hover:bg-indigo-500 disabled:opacity-50"
                            >
                                {{ editingPage ? 'Update Page' : 'Save Page' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Professional Delete Page Modal -->
            <ConfirmModal
                :show="showDeleteConfirm"
                :title="`Delete Page: ${pageToDelete?.title || ''}`"
                :message="`Are you sure you want to permanently delete the page '${pageToDelete?.title}' (/p/${pageToDelete?.slug})? Any published website links pointing to this page will become unavailable.`"
                confirm-text="Delete Page"
                cancel-text="Keep Page"
                type="danger"
                :processing="deletingPage"
                @confirm="confirmDeletePage"
                @cancel="showDeleteConfirm = false; pageToDelete = null;"
            />
        </div>
    </AdminLayout>
</template>
