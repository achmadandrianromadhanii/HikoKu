import { ref, watch } from "vue";

export function useCountUp(
    targetValue = 0,
    triggerRef = null,
    duration = 1500,
) {
    const displayValue = ref(0);

    const animate = () => {
        const start = performance.now();
        const startValue = 0;
        const endValue = Number(targetValue || 0);

        const step = (timestamp) => {
            const progress = Math.min((timestamp - start) / duration, 1);
            displayValue.value = Math.floor(
                startValue + (endValue - startValue) * progress,
            );

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                displayValue.value = endValue;
            }
        };

        requestAnimationFrame(step);
    };

    if (triggerRef) {
        watch(
            triggerRef,
            (value) => {
                if (value) animate();
            },
            { immediate: true },
        );
    }

    return {
        displayValue,
        animate,
    };
}
