import { onBeforeUnmount } from 'vue'

type FetchFn = () => Promise<boolean> | boolean

interface Options {
    activeInterval?: number
    idleInterval?: number
}

export function useSmartPolling(fetchFn: FetchFn, options: Options = {}) {
    const activeInterval = options.activeInterval ?? 5000
    const idleInterval = options.idleInterval ?? 30000

    let timer: ReturnType<typeof setTimeout> | null = null
    let stopped = false

    const getInterval = () => (document.hidden ? idleInterval : activeInterval)

    const clear = () => {
        if (timer) {
            clearTimeout(timer)
            timer = null
        }
    }

    const tick = async () => {
        if (stopped) return

        const done = await fetchFn()
        if (done) {
            stop()
            return
        }

        timer = setTimeout(tick, getInterval())
    }

    const start = () => {
        stopped = false
        clear()
        timer = setTimeout(tick, getInterval())
    }

    const stop = () => {
        stopped = true
        clear()
    }

    const handleVisibilityChange = () => {
        if (!stopped) {
            clear()
            timer = setTimeout(tick, getInterval())
        }
    }

    document.addEventListener('visibilitychange', handleVisibilityChange)

    onBeforeUnmount(() => {
        stop()
        document.removeEventListener('visibilitychange', handleVisibilityChange)
    })

    return { start, stop }
}