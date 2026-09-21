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
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="গ্রাহক লগইন (Subscriber Portal) - Pirgacha Internet" />

        <div v-if="status" class="mb-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-400">
            {{ status }}
        </div>

        <!-- Subscriber Portal Header Card -->
        <div class="mb-6 text-center">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-sky/10 border border-brand-sky/30 text-brand-sky text-[11px] font-bold uppercase tracking-wider mb-2">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-sky animate-pulse"></span>
                Subscriber Self-Service Portal
            </div>
            <h1 class="text-xl font-black text-white tracking-tight">গ্রাহক লগইন (PPPoE)</h1>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto leading-relaxed">
                আপনার ব্রডব্যান্ড সংযোগের PPPoE User ID ও Password দিয়ে লগইন করে বিল পরিশোধ ও সংযোগ নিয়ন্ত্রণ করুন।
            </p>
        </div>

        <!-- Informational Banner for Subscriber -->
        <div class="mb-5 rounded-2xl border border-brand-sky/30 bg-brand-sky/10 p-3.5 text-xs text-slate-300 flex items-start gap-2.5">
            <span class="text-base">💡</span>
            <div class="leading-relaxed text-[11px]">
                আপনার রাউটার বা কানেকশন স্লিপে থাকা 
                <strong class="text-white">PPPoE User ID</strong> ও <strong class="text-white">PPPoE Password</strong> 
                দিয়ে লগইন করুন।
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Login Identifier Field -->
            <div>
                <InputLabel 
                    for="login" 
                    value="PPPoE User ID" 
                    class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5"
                />

                <TextInput
                    id="login"
                    type="text"
                    class="block w-full text-xs font-mono"
                    v-model="form.login"
                    required
                    autofocus
                    placeholder="e.g. rahim_01 or cust_000001"
                    autocomplete="username"
                />

                <InputError class="mt-1.5" :message="form.errors.login || form.errors.email" />
            </div>

            <!-- Password Field -->
            <div>
                <InputLabel 
                    for="password" 
                    value="PPPoE Password" 
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

            <!-- Remember & Help -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center select-none cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-xs text-slate-400">Remember session</span>
                </label>

                <Link
                    :href="route('contact')"
                    class="text-xs font-semibold text-brand-sky hover:underline"
                >
                    আইডি মনে নেই? হেল্পলাইন
                </Link>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-3 text-xs font-black tracking-wide bg-gradient-to-r from-brand-sky to-brand-blue hover:from-brand-cyan hover:to-brand-sky shadow-lg shadow-brand-sky/25"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">যাচাই করা হচ্ছে...</span>
                    <span v-else>সাবস্ক্রাইবার পোর্টালে লগইন করুন →</span>
                </PrimaryButton>
            </div>
        </form>

        <!-- Back to Website -->
        <div class="mt-6 pt-4 border-t border-brand-navy/60 text-center">
            <Link 
                href="/" 
                class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-white transition"
            >
                <span>←</span>
                <span>Back to Website</span>
            </Link>
        </div>
    </GuestLayout>
</template>
