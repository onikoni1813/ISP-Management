<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    noc_hotline: props.settings?.noc_hotline || '01711-000000 / 01722-000000',
    support_email: props.settings?.support_email || 'support@pirgachainternet.com',
    working_hours: props.settings?.working_hours || '24 Hours Daily (7 Days a Week)',
    office_address: props.settings?.office_address || 'Town Center, Pirgacha Sadar, Rangpur',
    maintenance_mode: props.settings?.maintenance_mode === '1',
    maintenance_title: props.settings?.maintenance_title || 'আমরা রক্ষণাবেক্ষণ করছি (Under Maintenance)',
    maintenance_message: props.settings?.maintenance_message || 'আমাদের সিস্টেম আপগ্রেড ও নেটওয়ার্ক রক্ষণাবেক্ষণের কাজ চলছে। সাময়িক অসুবিধার জন্য আমরা আন্তরিকভাবে দুঃখিত। খুব শীঘ্রই সাইটটি স্বাভাবিক হবে।',
    maintenance_estimated_time: props.settings?.maintenance_estimated_time || 'শীঘ্রই সম্পন্ন হবে',
});

const submit = () => {
    form.post(route('admin.cms.settings.update'));
};
</script>

<template>
    <Head title="NOC Hotline & Company Settings - Admin" />

    <AdminLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-brand-orange animate-pulse"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-orange">Website & Portal Configuration</span>
                    </div>
                    <h1 class="text-2xl font-black text-white tracking-tight mt-1">NOC হটলাইন ও যোগাযোগ সেটিংস</h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        এখানে পরিবর্তিত হটলাইন নম্বর ও ঠিকানা গ্রাহক পোর্টাল ও পাবলিক ওয়েবসাইটে স্বয়ংক্রিয়ভাবে আপডেট হবে
                    </p>
                </div>

                <Link
                    :href="route('admin.cms.index')"
                    class="rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:text-white transition flex items-center gap-1.5"
                >
                    <span>←</span>
                    <span>CMS Overview</span>
                </Link>
            </div>

            <!-- Settings Form Card -->
            <form @submit.prevent="submit" class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 md:p-8 backdrop-blur-sm space-y-6 shadow-2xl">
                <!-- Validation Error Alert -->
                <div v-if="form.hasErrors" class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 text-xs text-rose-300 space-y-1">
                    <div v-for="(err, key) in form.errors" :key="key" class="font-medium">• {{ err }}</div>
                </div>

                <div class="space-y-4">
                    <!-- NOC Hotline Numbers -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                📞 জরুরী কারিগরি NOC হটলাইন (Emergency NOC Hotline) <span class="text-rose-400">*</span>
                            </label>
                            <span class="text-[11px] text-brand-orange font-mono font-bold">
                                গ্রাহক পোর্টাল ও ওয়েবসাইটে দৃশ্যমান
                            </span>
                        </div>
                        <input
                            v-model="form.noc_hotline"
                            type="text"
                            required
                            placeholder="যেমন: 01711-000000 / 01722-000000 বা আপনার একক হটলাইন"
                            class="w-full rounded-2xl border-2 border-slate-800 bg-slate-950 p-3.5 text-sm text-white placeholder-slate-600 focus:border-brand-orange focus:ring-2 focus:ring-brand-orange/30 font-mono font-bold outline-none transition"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">
                            এই নম্বরটি গ্রাহকের <strong>Support & Complaints (/account/complaints)</strong> পেজের ব্যানারে এবং ওয়েবসাইটের কন্টাক্ট সেকশনে ডায়নামিক ভাবে প্রদর্শিত হবে।
                        </p>
                    </div>

                    <!-- Support Email -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            ✉️ সাপোর্ট ইমেইল (Support & NOC Email)
                        </label>
                        <input
                            v-model="form.support_email"
                            type="email"
                            placeholder="support@pirgachainternet.com"
                            class="w-full rounded-2xl border-2 border-slate-800 bg-slate-950 p-3.5 text-xs text-white placeholder-slate-600 focus:border-brand-orange focus:ring-2 focus:ring-brand-orange/30 outline-none transition"
                        />
                    </div>

                    <!-- Working Hours -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            🕒 হটলাইন ও ফিল্ড সাপোর্ট কার্যসময় (Working Hours)
                        </label>
                        <input
                            v-model="form.working_hours"
                            type="text"
                            placeholder="24 Hours Daily (7 Days a Week)"
                            class="w-full rounded-2xl border-2 border-slate-800 bg-slate-950 p-3.5 text-xs text-white placeholder-slate-600 focus:border-brand-orange focus:ring-2 focus:ring-brand-orange/30 outline-none transition"
                        />
                    </div>

                    <!-- Office Address -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            📍 কেন্দ্রীয় অপারেশন সেন্টার ঠিকানা (Central NOC Office Address)
                        </label>
                        <textarea
                            v-model="form.office_address"
                            rows="2"
                            placeholder="Town Center, Pirgacha Sadar, Rangpur"
                            class="w-full rounded-2xl border-2 border-slate-800 bg-slate-950 p-3.5 text-xs text-white placeholder-slate-600 focus:border-brand-orange focus:ring-2 focus:ring-brand-orange/30 outline-none transition"
                        ></textarea>
                    </div>

                    <!-- Maintenance Mode Control Section -->
                    <div class="pt-6 border-t border-slate-800 space-y-4">
                        <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">🛠️</span>
                                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                                            পাবলিক ওয়েবসাইট মেইনটেন্যান্স মোড (Maintenance Mode)
                                        </h3>
                                    </div>
                                    <p class="text-xs text-slate-300 mt-1">
                                        এটি চালু করলে সাধারণ ভিজিটররা ওয়েবসাইট ব্রাউজ করতে পারবে না এবং মেইনটেন্যান্স পেজ দেখতে পাবে। তবে <strong>অ্যাডমিন ও স্টাফরা লগইন থাকা অবস্থায় স্বাভাবিকভাবে অ্যাডমিন প্যানেল ও পুরো ওয়েবসাইট পরিচালনা করতে পারবে</strong>।
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer ml-4">
                                    <input
                                        type="checkbox"
                                        v-model="form.maintenance_mode"
                                        class="sr-only peer"
                                    />
                                    <div class="w-12 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                                </label>
                            </div>

                            <!-- Detailed Maintenance Inputs when active -->
                            <div v-if="form.maintenance_mode" class="pt-4 border-t border-amber-500/20 space-y-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                        মেইনটেন্যান্স নোটিশ শিরোনাম (Notice Title)
                                    </label>
                                    <input
                                        v-model="form.maintenance_title"
                                        type="text"
                                        placeholder="আমরা রক্ষণাবেক্ষণ করছি (Under Maintenance)"
                                        class="w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-white placeholder-slate-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                        বার্তা ও বিস্তারিত বিবরণ (Notice Message)
                                    </label>
                                    <textarea
                                        v-model="form.maintenance_message"
                                        rows="2"
                                        placeholder="আমাদের সিস্টেম আপগ্রেড ও নেটওয়ার্ক রক্ষণাবেক্ষণের কাজ চলছে..."
                                        class="w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-white placeholder-slate-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                        আনুমানিক সময় (Estimated Completion Time)
                                    </label>
                                    <input
                                        v-model="form.maintenance_estimated_time"
                                        type="text"
                                        placeholder="যেমন: রাত ০২:০০ থেকে ০৪:০০ পর্যন্ত বা শীঘ্রই সম্পন্ন হবে"
                                        class="w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-white placeholder-slate-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                    <div class="text-[11px] text-slate-400">
                        পরিবর্তন সেভ করলে সাথে সাথে পুরো ওয়েবসাইটে তা আপডেট হবে।
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-2xl bg-gradient-to-r from-brand-orange to-amber-500 hover:from-orange-500 hover:to-amber-400 px-8 py-3.5 text-xs font-black uppercase tracking-wider text-white shadow-xl shadow-brand-orange/25 transition active:scale-95 disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                    >
                        <span v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span>{{ form.processing ? 'সেভ হচ্ছে...' : 'সেটিংস আপডেট করুন (Save Settings) →' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
