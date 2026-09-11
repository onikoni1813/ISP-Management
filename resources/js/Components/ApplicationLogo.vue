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
        default: 'Ultra Fast Optical Fiber',
    },
});
</script>

<template>
    <div class="logo-brand-container inline-flex items-center gap-3 group select-none">
        <div 
            class="relative flex items-center justify-center"
            :class="[
                size === 'sm' ? 'h-9 w-9' : '',
                size === 'default' ? 'h-11 w-11' : '',
                size === 'lg' ? 'h-14 w-14' : '',
                size === 'xl' ? 'h-20 w-20' : '',
            ]"
        >
            <!-- Outer Pulsing Glow Effect -->
            <div 
                v-if="withGlow"
                class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-cyan-500 via-teal-400 to-indigo-500 opacity-60 blur-md transition-all duration-700 group-hover:opacity-100 group-hover:blur-lg"
                :class="animated ? 'animate-logo-pulse' : ''"
            ></div>

            <!-- Rotating Gradient Border Ring on hover or continuous animation -->
            <div 
                v-if="animated"
                class="absolute -inset-0.5 rounded-2xl bg-gradient-to-tr from-cyan-400 via-indigo-500 to-emerald-400 opacity-80 animate-logo-spin transition-opacity duration-500 group-hover:opacity-100"
            ></div>

            <!-- Inner Logo Mask & Glass Container -->
            <div 
                class="relative h-full w-full rounded-2xl bg-slate-950/90 p-1.5 shadow-2xl backdrop-blur-xl border border-white/10 flex items-center justify-center overflow-hidden transition-transform duration-500 ease-out group-hover:scale-105 group-hover:rotate-1"
                :class="animated ? 'animate-logo-float' : ''"
            >
                <!-- Shimmer Ray Effect -->
                <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-1000 group-hover:translate-x-full animate-logo-shimmer pointer-events-none"></div>

                <!-- Logo Image -->
                <img 
                    src="/logo.png" 
                    alt="Pirgacha Internet" 
                    class="h-full w-full object-contain filter drop-shadow-[0_2px_8px_rgba(6,182,212,0.4)] transition-all duration-500 group-hover:brightness-110"
                />
            </div>
        </div>

        <!-- Optional Text Header -->
        <div v-if="withText" class="flex flex-col justify-center">
            <span class="text-base font-black tracking-tight text-white leading-tight transition-colors duration-300 group-hover:text-cyan-300">
                Pirgacha Internet
            </span>
            <span v-if="subText" class="text-[10px] font-bold text-cyan-400 tracking-wider uppercase mt-0.5 block">
                {{ subText }}
            </span>
        </div>
    </div>
</template>

<style scoped>
@keyframes logoFloat {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-3px) rotate(0.5deg);
    }
}

@keyframes logoPulseGlow {
    0%, 100% {
        opacity: 0.5;
        transform: scale(0.98);
    }
    50% {
        opacity: 0.85;
        transform: scale(1.04);
    }
}

@keyframes logoSpinBorder {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

@keyframes logoShimmerSlide {
    0% {
        transform: translateX(-150%);
    }
    40%, 100% {
        transform: translateX(150%);
    }
}

.animate-logo-float {
    animation: logoFloat 4s ease-in-out infinite;
}

.animate-logo-pulse {
    animation: logoPulseGlow 3s ease-in-out infinite;
}

.animate-logo-spin {
    animation: logoSpinBorder 10s linear infinite;
}

.animate-logo-shimmer {
    animation: logoShimmerSlide 5s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}
</style>
