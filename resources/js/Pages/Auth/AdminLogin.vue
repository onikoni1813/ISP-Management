<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('admin.login.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="অ্যাডমিন ও স্টাফ কনসোল - Pirgacha Internet" />

        <div v-if="status" class="mb-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-400">
            {{ status }}
        </div>

        <!-- Secure Header Badge -->
        <div class="mb-6 text-center">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-orange/10 border border-brand-orange/30 text-brand-orange text-[11px] font-bold uppercase tracking-wider mb-2">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-orange animate-pulse"></span>
                Restricted NOC & Management Console
            </div>
            <h1 class="text-xl font-black text-white tracking-tight">অ্যাডমিন ও স্টাফ লগইন</h1>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto leading-relaxed">
                অনুমোদিত কর্মকর্তা এবং মাঠপর্যায়ের টেকনিশিয়ান ও কালেকশন স্টাফদের সিকিউর কনসোল।
            </p>
        </div>

        <!-- Informational Banner for Admin/Staff -->
        <div class="mb-5 rounded-2xl border border-brand-orange/30 bg-brand-orange/10 p-3.5 text-xs text-slate-300 flex items-start gap-2.5">
            <span class="text-base">🛡️</span>
            <div class="leading-relaxed text-[11px]">
                অফিশিয়াল অ্যাডমিনিস্ট্রেটর ও অনুমোদিত স্টাফরা তাদের নির্ধারিত 
                <strong class="text-white">ইমেইল, ফোন অথবা ইউজারনেম</strong> এবং পাসওয়ার্ড দিয়ে প্রবেশ করুন।
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Identifier Field -->
            <div>
                <InputLabel 
                    for="login" 
                    value="Email, Phone, or Username" 
                    class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5"
                />

                <TextInput
                    id="login"
                    type="text"
                    class="block w-full text-xs font-mono"
                    v-model="form.login"
                    required
                    autofocus
                    placeholder="admin@pirgachainternet.com or 017XXXXXXXX"
                    autocomplete="username"
                />

                <InputError class="mt-1.5" :message="form.errors.login || form.errors.email" />
            </div>

            <!-- Password Field -->
            <div>
                <InputLabel 
                    for="password" 
                    value="Password" 
                    class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5"
                />

                <TextInput
                    id="password"
                    type="password"
                    class="block w-full text-xs"
                    v-model="form.password"
                    required
                    placeholder="••••••••"
                    autocomplete="current-password"
                />

                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <!-- Remember Password -->
            <div class="flex items-center pt-1">
                <label class="flex items-center select-none cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-xs text-slate-400">Remember session</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-3 text-xs font-black tracking-wide bg-gradient-to-r from-brand-orange to-brand-amber hover:from-brand-amber hover:to-brand-gold shadow-lg shadow-brand-orange/25"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">নিরাপত্তা যাচাই চলছে...</span>
                    <span v-else>কনসোলে লগইন করুন →</span>
                </PrimaryButton>
            </div>
        </form>


    </GuestLayout>
</template>
