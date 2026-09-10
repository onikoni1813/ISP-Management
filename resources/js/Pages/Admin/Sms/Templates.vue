<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    templates: Array,
});

const editingTemplate = ref(null);

const editForm = useForm({
    template: '',
    is_auto_enabled: true,
});

const openEditModal = (tmpl) => {
    editingTemplate.value = tmpl;
    editForm.template = tmpl.template;
    editForm.is_auto_enabled = Boolean(tmpl.is_auto_enabled);
};

const submitUpdate = () => {
    if (!editingTemplate.value) return;
    editForm.patch(route('admin.sms.templates.update', editingTemplate.value.id), {
        onSuccess: () => {
            editingTemplate.value = null;
        }
    });
};
</script>

<template>
    <Head title="SMS Templates - Pirgacha Internet" />

    <AdminLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <a :href="route('admin.sms.index')" class="text-xs text-indigo-600 hover:underline">← SMS Logs Hub</a>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">SMS Message Templates</h1>
                    <p class="text-sm text-gray-500">Configure pre-formatted system notification templates and variable placeholders.</p>
                </div>
            </div>

            <!-- Placeholders Info Box -->
            <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl">
                <h3 class="text-xs font-bold text-indigo-900 uppercase tracking-wider mb-1">Available Dynamic Variables</h3>
                <div class="flex flex-wrap gap-2 text-xs font-mono">
                    <span class="bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{name}</span>
                    <span class="bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{customer_code}</span>
                    <span class="bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{package}</span>
                    <span class="bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{amount}</span>
                    <span class="bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{expiry_date}</span>
                    <span class="bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{due}</span>
                    <span class="bg-white px-2 py-0.5 rounded border border-indigo-200 text-indigo-700">{complaint_number}</span>
                </div>
            </div>

            <!-- Templates List -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="tmpl in templates"
                    :key="tmpl.id"
                    class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div>
                                <h3 class="font-bold text-gray-900">{{ tmpl.name }}</h3>
                                <span class="font-mono text-xs text-gray-400">Code: {{ tmpl.code }}</span>
                            </div>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded text-[11px] font-bold uppercase',
                                    tmpl.is_auto_enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500'
                                ]"
                            >
                                {{ tmpl.is_auto_enabled ? 'Auto-Active' : 'Disabled' }}
                            </span>
                        </div>

                        <div class="mt-4 p-3 bg-gray-50 rounded-xl border border-gray-100 text-xs text-gray-700 leading-relaxed font-sans whitespace-pre-wrap">
                            {{ tmpl.template }}
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-[11px] text-gray-400">{{ tmpl.template.length }} characters</span>
                        <button
                            @click="openEditModal(tmpl)"
                            class="px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition"
                        >
                            ✏️ Edit Template
                        </button>
                    </div>
                </div>
            </div>

            <!-- Edit Template Modal -->
            <div v-if="editingTemplate" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-gray-100 animate-in fade-in zoom-in-95">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900">Edit Template: {{ editingTemplate.name }}</h2>
                        <button @click="editingTemplate = null" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <form @submit.prevent="submitUpdate" class="mt-4 space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-semibold text-gray-700 uppercase">Template Message Body *</label>
                                <span class="text-[11px] text-gray-400">{{ editForm.template.length }} characters</span>
                            </div>
                            <textarea
                                v-model="editForm.template"
                                required
                                rows="5"
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="is_auto"
                                v-model="editForm.is_auto_enabled"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="is_auto" class="text-xs font-semibold text-gray-700 cursor-pointer">
                                Enable Automatic Triggering on System Events
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="editingTemplate = null"
                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm disabled:opacity-50"
                            >
                                {{ editForm.processing ? 'Saving...' : 'Save Template' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
