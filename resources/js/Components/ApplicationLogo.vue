<script setup>
defineProps({
    size: {
        type: String,
        default: 'default', // 'sm', 'default', 'lg', 'xl'
    },
    animated: {
        type: Boolean,
        default: true,
    },
    withGlow: {
        type: Boolean,
        default: true,
    },
    withText: {
        type: Boolean,
        default: false,
    },
    subText: {
        type: String,
        default: 'Connect to the world',
    },
    badge: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="logo-brand-container inline-flex items-center gap-3.5 group select-none">
        <div 
            class="relative flex items-center justify-center shrink-0"
            :class="[
                size === 'sm' ? 'h-10 w-10' : '',
                size === 'default' ? 'h-12 w-12' : '',
                size === 'lg' ? 'h-16 w-16' : '',
                size === 'xl' ? 'h-24 w-24' : '',
            ]"
        >
            <!-- Dual Brand Pulse Glow (Cyan/Blue to Vibrant Orange) -->
            <div 
                v-if="withGlow"
                class="absolute -inset-1.5 rounded-2xl bg-gradient-to-tr from-brand-sky via-brand-blue to-brand-orange opacity-60 blur-md transition-all duration-700 group-hover:opacity-100 group-hover:blur-xl"
                :class="animated ? 'animate-brand-pulse' : ''"
            ></div>

            <!-- Rotating Dual-Hue Gradient Border Ring -->
            <div 
                v-if="animated"
                class="absolute -inset-0.5 rounded-2xl bg-gradient-to-r from-brand-sky via-brand-orange to-brand-cyan opacity-75 animate-brand-spin transition-opacity duration-500 group-hover:opacity-100"
            ></div>

            <!-- Inner Logo Mask & Glass Card with White Contrast -->
            <div 
                class="relative h-full w-full rounded-2xl bg-white p-1 shadow-2xl backdrop-blur-xl border border-white/20 flex items-center justify-center overflow-hidden transition-all duration-500 ease-out group-hover:scale-105 group-hover:rotate-1"
                :class="animated ? 'animate-brand-float' : ''"
            >
                <!-- Shimmer Light Sweep -->
                <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-1000 group-hover:translate-x-full animate-brand-shimmer pointer-events-none"></div>

                <!-- Crisp Logo Image -->
                <img 
                    src="/logo.png" 
                    alt="Pirgacha Internet" 
                    class="h-full w-full object-contain filter drop-shadow-[0_2px_6px_rgba(11,83,140,0.3)] transition-transform duration-500 group-hover:scale-105"
                />
            </div>
        </div>

        <!-- Typography with Logo brand colors (Option 3: Brand Sky Blue & Vibrant Orange Dual-Tone) -->
        <div v-if="withText" class="flex flex-col justify-center text-left">
            <div class="flex items-center gap-2">
                <div class="flex items-baseline tracking-tight font-black leading-none">
                    <span class="text-base sm:text-xl font-black bg-gradient-to-r from-sky-400 to-cyan-300 bg-clip-text text-transparent drop-shadow-[0_2px_8px_rgba(56,189,248,0.25)] transition-all duration-300 group-hover:from-white group-hover:to-sky-300">
                        Pirgacha
                    </span>
                    <span class="text-base sm:text-xl font-black bg-gradient-to-r from-orange-400 via-brand-orange to-amber-400 bg-clip-text text-transparent ml-1.5 drop-shadow-[0_2px_8px_rgba(249,115,22,0.3)] transition-all duration-300 group-hover:from-orange-300 group-hover:to-amber-300">
                        Internet
                    </span>
                </div>
                <span 
                    v-if="badge" 
                    class="rounded-md bg-brand-orange/15 px-1.5 sm:px-2 py-0.5 text-[10px] sm:text-xs font-bold text-brand-orange border border-brand-orange/30 tracking-normal shadow-sm"
                >
                    {{ badge }}
                </span>
            </div>
            <div v-if="subText" class="flex items-center gap-1.5 mt-1.5">
                <span class="h-1.5 w-1.5 rounded-full bg-gradient-to-r from-sky-400 to-brand-orange shadow-sm shadow-brand-orange"></span>
                <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.2em] text-slate-300 group-hover:text-white transition-colors">
                    {{ subText }}
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes brandFloat {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-3px) rotate(0.5deg);
    }
}

@keyframes brandPulse {
    0%, 100% {
        opacity: 0.55;
        transform: scale(0.98);
    }
    50% {
        opacity: 0.9;
        transform: scale(1.05);
    }
}

@keyframes brandSpin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

@keyframes brandShimmer {
    0% {
        transform: translateX(-150%);
    }
    40%, 100% {
        transform: translateX(150%);
    }
}

.animate-brand-float {
    animation: brandFloat 4s ease-in-out infinite;
}

.animate-brand-pulse {
    animation: brandPulse 3.5s ease-in-out infinite;
}

.animate-brand-spin {
    animation: brandSpin 8s linear infinite;
}

.animate-brand-shimmer {
    animation: brandShimmer 5s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}
</style>
