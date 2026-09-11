<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

const props = defineProps({
    areas: Array,
});

const form = useForm({
    name: '',
    phone: '',
    email: '',
    area_id: props.areas?.[0]?.id || '',
    address: '',
    package_id: '',
    notes: '',
});

const submitInquiry = () => {
    // If no package selected, default to first available
    form.post(route('apply'), {
        onSuccess: () => {
            form.reset();
        }
    });
};
</script>

<template>
    <Head title="Contact NOC Support - Pirgacha Internet" />

    <WebsiteLayout>
        <div class="py-16 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto">
                <span class="text-xs font-bold text-brand-orange uppercase tracking-widest">Get In Touch</span>
                <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">Contact Us & Support NOC</h1>
                <p class="text-xs text-slate-400 mt-2">Have a question or looking to connect your home or enterprise? Reach out anytime.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Contact Info Card -->
                <div class="rounded-3xl border border-brand-navy bg-[#091A2E]/80 p-8 backdrop-blur-sm space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-white">Central Operations Center</h2>
                        <p class="text-xs text-slate-400 mt-1">Town Center, Pirgacha Sadar, Rangpur</p>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="flex items-start gap-3">
                            <span class="text-lg">📞</span>
                            <div>
                                <span class="text-slate-400 block">Customer Care Hotline</span>
                                <span class="text-brand-orange font-mono font-bold text-sm">01711-000000 / 01722-000000</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="text-lg">✉️</span>
                            <div>
                                <span class="text-slate-400 block">Email Support</span>
                                <span class="text-white font-bold">support@pirgachainternet.com</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="text-lg">🕒</span>
                            <div>
                                <span class="text-slate-400 block">Field Support Working Hours</span>
                                <span class="text-brand-sky font-bold">24 Hours Daily (7 Days a Week)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fast Message & Connection Request Form -->
                <div class="rounded-3xl border border-brand-navy bg-[#091A2E]/90 p-8 shadow-2xl backdrop-blur-md">
                    <h3 class="text-lg font-bold text-white mb-4">Send Us a Message</h3>
                    
                    <form @submit.prevent="submitInquiry" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Your Name *</label>
                            <input v-model="form.name" type="text" required placeholder="Full Name" class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3 text-xs text-white focus:border-brand-sky focus:ring-brand-sky" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Mobile Phone *</label>
                            <input v-model="form.phone" type="tel" required placeholder="017XXXXXXXX" class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3 text-xs text-white font-mono focus:border-brand-sky focus:ring-brand-sky" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Area / Village</label>
                            <select v-model="form.area_id" class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3 text-xs text-white focus:border-brand-sky focus:ring-brand-sky">
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Your Message / Query *</label>
                            <textarea v-model="form.notes" rows="3" required placeholder="Tell us how we can help you..." class="w-full rounded-2xl border-brand-navy bg-[#061220] p-3 text-xs text-white focus:border-brand-sky focus:ring-brand-sky"></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-2xl bg-gradient-to-r from-brand-orange via-brand-amber to-brand-gold hover:opacity-95 p-3.5 text-xs font-black text-white shadow-lg shadow-brand-orange/30 transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Sending...' : 'Send Message' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </WebsiteLayout>
</template>
