<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    banners: Array,
});

const isCreating = ref(false);
const editingBanner = ref(null);

const form = useForm({
    title: '',
    subtitle: '',
    badge_text: '',
    button_text: '',
    button_url: '',
    background_gradient: 'from-indigo-600 via-blue-600 to-emerald-500',
    order: 0,
    is_active: true,
});

const openCreateModal = () => {
    editingBanner.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.order = props.banners.length;
    isCreating.value = true;
};

const openEditModal = (banner) => {
    editingBanner.value = banner;
    form.clearErrors();
    form.title = banner.title;
    form.subtitle = banner.subtitle || '';
    form.badge_text = banner.badge_text || '';
    form.button_text = banner.button_text || '';
    form.button_url = banner.button_url || '';
    form.background_gradient = banner.background_gradient || 'from-indigo-600 via-blue-600 to-emerald-500';
    form.order = banner.order;
    form.is_active = Boolean(banner.is_active);
    isCreating.value = true;
};

const closeModal = () => {
    isCreating.value = false;
    editingBanner.value = null;
    form.reset();
};

const saveBanner = () => {
    if (editingBanner.value) {
        form.patch(route('admin.cms.banners.update', editingBanner.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.cms.banners.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const toggleActive = (banner) => {
    useForm({}).post(route('admin.cms.banners.toggle', banner.id));
};

const deleteBanner = (banner) => {
    if (confirm(`Are you sure you want to delete banner "${banner.title}"?`)) {
        useForm({}).delete(route('admin.cms.banners.destroy', banner.id));
    }
};
</script>

<template>
    <Head title="Manage Hero Banners - Website CMS" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <Link :href="route('admin.cms.index')" class="hover:text-white">CMS</Link>
                        <span>/</span>
                        <span class="text-slate-200">Hero Banners</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white mt-1">Marketing Banners & Promotions</h1>
                    <p class="text-xs text-slate-400">Configure promotional call-outs, campaign badges, and homepage banners.</p>
                </div>

                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-cyan-600/30 hover:from-cyan-500 hover:to-teal-400 transition active:scale-95"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Banner
                </button>
            </div>

            <!-- Banners Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-if="banners.length === 0" class="col-span-full rounded-3xl border border-slate-800 bg-slate-900/40 p-12 text-center text-slate-500">
                    No banners configured yet. Click "New Banner" to create high-converting promotional banners.
                </div>

                <div
                    v-for="banner in banners"
                    :key="banner.id"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-4 flex flex-col justify-between"
                >
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span v-if="banner.badge_text" class="rounded-full bg-cyan-500/10 border border-cyan-500/30 px-2.5 py-0.5 text-[10px] font-bold text-cyan-300">
                                {{ banner.badge_text }}
                            </span>
                            <span v-else class="text-[10px] text-slate-500 font-mono">Order: #{{ banner.order }}</span>

                            <button
                                @click="toggleActive(banner)"
                                :class="[
                                    banner.is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700',
                                    'rounded-full border px-2.5 py-1 text-[10px] font-bold transition hover:opacity-80'
                                ]"
                            >
                                {{ banner.is_active ? 'Active' : 'Disabled' }}
                            </button>
                        </div>

                        <h3 class="text-base font-black text-white">{{ banner.title }}</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">{{ banner.subtitle }}</p>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                        <div class="text-[11px] text-slate-400 flex items-center gap-2">
                            <span v-if="banner.button_text" class="rounded-lg bg-slate-800 px-2 py-1 text-slate-200">
                                CTA: {{ banner.button_text }} ({{ banner.button_url }})
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="openEditModal(banner)"
                                class="rounded-lg p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                title="Edit"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                @click="deleteBanner(banner)"
                                class="rounded-lg p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                title="Delete"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create / Edit Banner Modal -->
            <div v-if="isCreating" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                <div class="w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 p-6 space-y-5 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <h2 class="text-base font-bold text-white">{{ editingBanner ? 'Edit Banner' : 'Create Banner' }}</h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveBanner" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Banner Headline / Title *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="e.g. 50% Off Installation Charge for New Users"
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"
                            />
                            <span v-if="form.errors.title" class="text-rose-400 mt-1 block">{{ form.errors.title }}</span>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Subtitle / Details</label>
                            <textarea
                                v-model="form.subtitle"
                                rows="3"
                                placeholder="Brief description highlighting offer validity or features..."
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Badge Tag</label>
                                <input
                                    v-model="form.badge_text"
                                    type="text"
                                    placeholder="e.g. Special Offer"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Display Order</label>
                                <input
                                    v-model.number="form.order"
                                    type="number"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Button Text</label>
                                <input
                                    v-model="form.button_text"
                                    type="text"
                                    placeholder="e.g. Apply Now"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Button URL</label>
                                <input
                                    v-model="form.button_url"
                                    type="text"
                                    placeholder="#apply or /packages"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-slate-700 bg-slate-950 text-cyan-600 focus:ring-cyan-500"
                            />
                            <label for="is_active" class="font-medium text-slate-200">Active (Visible on public homepage)</label>
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
                                class="rounded-xl bg-cyan-600 px-5 py-2 text-xs font-bold text-white hover:bg-cyan-500 disabled:opacity-50"
                            >
                                {{ editingBanner ? 'Update Banner' : 'Save Banner' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
