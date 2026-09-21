<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
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
                        <Link :href="route('admin.sms.index')" class="text-xs text-brand-sky hover:underline flex items-center gap-1 font-medium">
                            ← SMS Logs Hub
                        </Link>
                    </div>
                    <h1 class="text-2xl font-bold text-white tracking-tight mt-1 flex items-center gap-2.5">
                        <span class="inline-flex p-2 rounded-xl bg-brand-navy/60 text-brand-sky border border-brand-navy">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                        SMS Message Templates
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">Configure pre-formatted system notification templates and variable placeholders.</p>
                </div>
            </div>

            <!-- Placeholders Info Box -->
            <div class="p-4 bg-[#091A2E]/80 backdrop-blur-sm border border-brand-navy rounded-2xl shadow-xl">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Available Dynamic Variables</h3>
                <div class="flex flex-wrap gap-2 text-xs font-mono">
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-brand-navy text-brand-sky">{name}</span>
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-brand-navy text-brand-sky">{customer_code}</span>
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-brand-navy text-brand-sky">{package}</span>
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-brand-navy text-brand-sky">{amount}</span>
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-brand-navy text-brand-sky">{expiry_date}</span>
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-brand-navy text-brand-sky">{due}</span>
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-brand-navy text-brand-sky">{complaint_number}</span>
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-violet-500/40 text-violet-400">{pppoe_username}</span>
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-violet-500/40 text-violet-400">{pppoe_password}</span>
                    <span class="bg-[#071322] px-2.5 py-1 rounded-lg border border-emerald-500/40 text-emerald-400">{login_url}</span>
                </div>
            </div>

            <!-- Templates List -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="tmpl in templates"
                    :key="tmpl.id"
                    class="bg-[#091A2E]/80 backdrop-blur-sm rounded-2xl border border-brand-navy p-6 shadow-xl flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-brand-navy">
                            <div>
                                <h3 class="font-bold text-white text-base">{{ tmpl.name }}</h3>
                                <span class="font-mono text-xs text-slate-400">Code: {{ tmpl.code }}</span>
                            </div>
                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-md text-[11px] font-bold uppercase',
                                    tmpl.is_auto_enabled ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/40' : 'bg-slate-800 text-slate-400 border border-slate-700'
                                ]"
                            >
                                {{ tmpl.is_auto_enabled ? 'Auto-Active' : 'Disabled' }}
                            </span>
                        </div>

                        <div class="mt-4 p-3.5 bg-[#071322] rounded-xl border border-brand-navy text-xs text-slate-300 leading-relaxed font-sans whitespace-pre-wrap">
                            {{ tmpl.template }}
                        </div>
                    </div>

                    <div class="mt-5 pt-3.5 border-t border-brand-navy flex items-center justify-between">
                        <span class="text-[11px] text-slate-500 font-mono">{{ tmpl.template.length }} characters</span>
                        <button
                            @click="openEditModal(tmpl)"
                            class="px-3 py-1.5 text-xs font-semibold text-brand-sky hover:text-white hover:bg-brand-navy/60 rounded-xl transition border border-transparent hover:border-brand-sky/30"
                        >
                            ✏️ Edit Template
                        </button>
                    </div>
                </div>
            </div>

            <!-- Edit Template Modal -->
            <div v-if="editingTemplate" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-[#091A2E] rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-brand-navy animate-in fade-in zoom-in-95">
                    <div class="flex justify-between items-center pb-3 border-b border-brand-navy">
                        <h2 class="text-base font-bold text-white">Edit Template: {{ editingTemplate.name }}</h2>
                        <button @click="editingTemplate = null" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-brand-navy/60 transition">✕</button>
                    </div>

                    <form @submit.prevent="submitUpdate" class="mt-4 space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="text-xs font-semibold text-slate-300 uppercase">Template Message Body *</label>
                                <span class="text-[11px] text-slate-400 font-mono">{{ editForm.template.length }} characters</span>
                            </div>
                            <textarea
                                v-model="editForm.template"
                                required
                                rows="5"
                                class="w-full text-sm rounded-xl bg-[#071322] border-brand-navy text-white placeholder-slate-500 focus:border-brand-sky focus:ring-1 focus:ring-brand-sky"
                            ></textarea>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <input
                                type="checkbox"
                                id="is_auto"
                                v-model="editForm.is_auto_enabled"
                                class="rounded bg-[#071322] border-brand-navy text-brand-sky focus:ring-brand-sky"
                            />
                            <label for="is_auto" class="text-xs font-semibold text-slate-300 cursor-pointer">
                                Enable Automatic Triggering on System Events
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-brand-navy">
                            <button
                                type="button"
                                @click="editingTemplate = null"
                                class="px-4 py-2 border border-brand-navy text-slate-300 text-sm font-medium rounded-xl hover:bg-brand-navy/60 transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-5 py-2 bg-gradient-to-r from-brand-sky to-brand-blue hover:from-sky-400 hover:to-blue-600 text-white text-sm font-semibold rounded-xl shadow-md disabled:opacity-50 transition"
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
