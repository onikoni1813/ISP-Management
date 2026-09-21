<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    faqs: Array,
});

const isCreating = ref(false);
const editingFaq = ref(null);

const showDeleteConfirm = ref(false);
const faqToDelete = ref(null);
const deletingFaq = ref(false);

const form = useForm({
    question: '',
    answer: '',
    category: 'General',
    order: 0,
    is_published: true,
});

const categories = ['General', 'Setup & Connection', 'Billing & Payments', 'Troubleshooting', 'Technical'];

const openCreateModal = () => {
    editingFaq.value = null;
    form.reset();
    form.clearErrors();
    form.category = 'General';
    form.is_published = true;
    form.order = props.faqs.length;
    isCreating.value = true;
};

const openEditModal = (faq) => {
    editingFaq.value = faq;
    form.clearErrors();
    form.question = faq.question;
    form.answer = faq.answer;
    form.category = faq.category || 'General';
    form.order = faq.order;
    form.is_published = Boolean(faq.is_published);
    isCreating.value = true;
};

const closeModal = () => {
    isCreating.value = false;
    editingFaq.value = null;
    form.reset();
};

const saveFaq = () => {
    if (editingFaq.value) {
        form.patch(route('admin.cms.faqs.update', editingFaq.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.cms.faqs.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const togglePublish = (faq) => {
    useForm({}).post(route('admin.cms.faqs.toggle', faq.id));
};

const deleteFaq = (faq) => {
    faqToDelete.value = faq;
    showDeleteConfirm.value = true;
};

const confirmDeleteFaq = () => {
    if (!faqToDelete.value) return;
    deletingFaq.value = true;
    router.delete(route('admin.cms.faqs.destroy', faqToDelete.value.id), {
        onFinish: () => {
            deletingFaq.value = false;
            showDeleteConfirm.value = false;
            faqToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Manage FAQs - Website CMS" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <Link :href="route('admin.cms.index')" class="hover:text-white">CMS</Link>
                        <span>/</span>
                        <span class="text-slate-200">FAQs</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white mt-1">Frequently Asked Questions</h1>
                    <p class="text-xs text-slate-400">Configure questions, detailed answers, and categories displayed on the public FAQ page.</p>
                </div>

                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-amber-500/30 hover:from-amber-400 hover:to-orange-500 transition active:scale-95"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Question
                </button>
            </div>

            <!-- FAQ List -->
            <div class="space-y-4">
                <div v-if="faqs.length === 0" class="rounded-3xl border border-slate-800 bg-slate-900/40 p-12 text-center text-slate-500 text-xs">
                    No FAQs added yet. Click "New Question" to populate your public support section.
                </div>

                <div
                    v-for="faq in faqs"
                    :key="faq.id"
                    class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-sm space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-amber-500/10 border border-amber-500/30 px-2.5 py-0.5 text-[10px] font-bold text-amber-300">
                                {{ faq.category }}
                            </span>
                            <span class="text-[10px] text-slate-500 font-mono">Order: #{{ faq.order }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="togglePublish(faq)"
                                :class="[
                                    faq.is_published ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700',
                                    'rounded-full border px-2.5 py-1 text-[10px] font-bold transition hover:opacity-80'
                                ]"
                            >
                                {{ faq.is_published ? 'Published' : 'Draft' }}
                            </button>

                            <button
                                @click="openEditModal(faq)"
                                class="rounded-lg p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                title="Edit"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                @click="deleteFaq(faq)"
                                class="rounded-lg p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                title="Delete"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span class="text-amber-400 font-mono">Q.</span>
                        {{ faq.question }}
                    </h3>
                    <p class="text-xs text-slate-300 pl-5 leading-relaxed">
                        {{ faq.answer }}
                    </p>
                </div>
            </div>

            <!-- Create / Edit FAQ Modal -->
            <div v-if="isCreating" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
                <div class="w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 p-6 space-y-5 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <h2 class="text-base font-bold text-white">{{ editingFaq ? 'Edit FAQ' : 'Add FAQ Question' }}</h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveFaq" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Question *</label>
                            <input
                                v-model="form.question"
                                type="text"
                                required
                                placeholder="e.g. How can I test my optical signal?"
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-amber-500 focus:outline-none"
                            />
                            <span v-if="form.errors.question" class="text-rose-400 mt-1 block">{{ form.errors.question }}</span>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Answer *</label>
                            <textarea
                                v-model="form.answer"
                                rows="4"
                                required
                                placeholder="Clear, helpful response for customers..."
                                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-amber-500 focus:outline-none"
                            ></textarea>
                            <span v-if="form.errors.answer" class="text-rose-400 mt-1 block">{{ form.errors.answer }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Category</label>
                                <select
                                    v-model="form.category"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-amber-500 focus:outline-none"
                                >
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium text-slate-300 mb-1">Display Order</label>
                                <input
                                    v-model.number="form.order"
                                    type="number"
                                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input
                                id="faq_is_published"
                                v-model="form.is_published"
                                type="checkbox"
                                class="rounded border-slate-700 bg-slate-950 text-amber-600 focus:ring-amber-500"
                            />
                            <label for="faq_is_published" class="font-medium text-slate-200">Published (Visible on website /faq)</label>
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
                                class="rounded-xl bg-amber-600 px-5 py-2 text-xs font-bold text-white hover:bg-amber-500 disabled:opacity-50"
                            >
                                {{ editingFaq ? 'Update FAQ' : 'Save FAQ' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Professional Delete FAQ Modal -->
            <ConfirmModal
                :show="showDeleteConfirm"
                :title="'Delete FAQ Item'"
                :message="`Are you sure you want to delete this FAQ: &quot;${faqToDelete?.question}&quot;? It will be removed from customer guidance.`"
                confirm-text="Delete FAQ"
                cancel-text="Keep FAQ"
                type="danger"
                :processing="deletingFaq"
                @confirm="confirmDeleteFaq"
                @cancel="showDeleteConfirm = false; faqToDelete = null;"
            />
        </div>
    </AdminLayout>
</template>
