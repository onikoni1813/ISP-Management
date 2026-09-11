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

        <!-- Typography with Logo brand colors -->
        <div v-if="withText" class="flex flex-col justify-center text-left">
            <span class="text-base sm:text-lg font-black tracking-tight text-white leading-none transition-colors duration-300 group-hover:text-brand-cyan">
                Pirgacha <span class="bg-gradient-to-r from-brand-sky to-brand-orange bg-clip-text text-transparent">Internet</span>
            </span>
            <span v-if="subText" class="text-[10px] sm:text-[11px] font-semibold tracking-wider text-brand-orange mt-1 block">
                {{ subText }}
            </span>
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
