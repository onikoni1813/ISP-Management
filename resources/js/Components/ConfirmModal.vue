<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm Action',
    },
    message: {
        type: String,
        default: 'Are you sure you want to proceed with this action? This operation cannot be undone.',
    },
    confirmText: {
        type: String,
        default: 'Confirm & Proceed',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    type: {
        type: String,
        default: 'danger', // 'danger' | 'warning' | 'success' | 'info'
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div 
            class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" 
            @click="!processing && emit('cancel')"
        ></div>

        <!-- Modal Box -->
        <div 
            class="relative w-full max-w-md bg-[#091A2E] border border-brand-navy rounded-3xl p-6 sm:p-7 shadow-2xl overflow-hidden transition-all transform animate-in fade-in zoom-in-95 duration-200"
        >
            <!-- Ambient Glow -->
            <div 
                v-if="type === 'danger'"
                class="absolute -top-24 -right-24 w-52 h-52 bg-rose-500/15 rounded-full blur-3xl pointer-events-none"
            ></div>
            <div 
                v-else-if="type === 'warning'"
                class="absolute -top-24 -right-24 w-52 h-52 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"
            ></div>
            <div 
                v-else
                class="absolute -top-24 -right-24 w-52 h-52 bg-brand-sky/15 rounded-full blur-3xl pointer-events-none"
            ></div>

            <div class="relative z-10">
                <!-- Icon Header -->
                <div class="flex items-center gap-4 mb-5">
                    <div 
                        :class="[
                            'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border shadow-inner',
                            type === 'danger' ? 'bg-rose-500/15 border-rose-500/30 text-rose-400' :
                            type === 'warning' ? 'bg-amber-500/15 border-amber-500/30 text-amber-400' :
                            type === 'success' ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400' :
                            'bg-brand-sky/15 border-brand-sky/30 text-brand-sky'
                        ]"
                    >
                        <!-- Danger / Delete Icon -->
                        <svg v-if="type === 'danger'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <!-- Warning Icon -->
                        <svg v-else-if="type === 'warning'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <!-- Success Icon -->
                        <svg v-else-if="type === 'success'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Info / Default Icon -->
                        <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-white tracking-tight leading-snug">
                            {{ title }}
                        </h3>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            {{ type === 'danger' ? 'Permanent Action' : 'Action Confirmation' }}
                        </span>
                    </div>
                </div>

                <!-- Message Body -->
                <div class="mb-6">
                    <p class="text-sm text-slate-300 leading-relaxed font-normal">
                        {{ message }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button 
                        type="button"
                        :disabled="processing"
                        @click="emit('cancel')" 
                        class="flex-1 px-4 py-2.5 bg-[#0B1E36] hover:bg-[#102A4C] border border-brand-navy text-slate-300 hover:text-white rounded-xl font-bold text-xs transition duration-150"
                    >
                        {{ cancelText }}
                    </button>
                    
                    <button 
                        type="button"
                        :disabled="processing"
                        @click="emit('confirm')" 
                        :class="[
                            'flex-1 px-4 py-2.5 text-white rounded-xl font-bold text-xs shadow-lg transition duration-150 flex items-center justify-center gap-2',
                            type === 'danger' ? 'bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 shadow-rose-950/50' :
                            type === 'warning' ? 'bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 shadow-amber-950/50' :
                            type === 'success' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-emerald-950/50' :
                            'bg-gradient-to-r from-brand-sky to-brand-blue hover:from-brand-sky/90 hover:to-brand-blue/90 shadow-cyan-950/50'
                        ]"
                    >
                        <svg v-if="processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ processing ? 'Processing...' : confirmText }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
