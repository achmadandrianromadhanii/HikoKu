export function useDebounce(fn, delay = 300) {
    let timer = null;

    return (...args) => {
        window.clearTimeout(timer);

        timer = window.setTimeout(() => {
            fn(...args);
        }, delay);
    };
}
