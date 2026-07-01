import { onBeforeUnmount, onMounted, ref } from "vue";

export function useIntersection(options = {}) {
    const target = ref(null);
    const isIntersecting = ref(false);
    let observer = null;

    onMounted(() => {
        observer = new IntersectionObserver(
            ([entry]) => {
                isIntersecting.value = !!entry?.isIntersecting;
            },
            {
                threshold: options.threshold ?? 0.2,
                root: options.root ?? null,
                rootMargin: options.rootMargin ?? "0px",
            },
        );

        if (target.value) {
            observer.observe(target.value);
        }
    });

    onBeforeUnmount(() => {
        if (observer && target.value) {
            observer.unobserve(target.value);
        }

        if (observer) {
            observer.disconnect();
        }
    });

    return {
        target,
        isIntersecting,
    };
}
