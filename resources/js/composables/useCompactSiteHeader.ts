import { onMounted, onUnmounted, ref } from 'vue';

const COMPACT_SCROLL_OFFSET = 72;

export const useCompactSiteHeader = () => {
    const isCompact = ref(false);
    let animationFrame: number | null = null;

    const syncCompactState = () => {
        isCompact.value = window.scrollY > COMPACT_SCROLL_OFFSET;
        animationFrame = null;
    };

    const handleScroll = () => {
        if (animationFrame !== null) {
            return;
        }

        animationFrame = window.requestAnimationFrame(syncCompactState);
    };

    onMounted(() => {
        syncCompactState();
        window.addEventListener('scroll', handleScroll, { passive: true });
    });

    onUnmounted(() => {
        window.removeEventListener('scroll', handleScroll);

        if (animationFrame !== null) {
            window.cancelAnimationFrame(animationFrame);
        }
    });

    return { isCompact };
};
