<script setup lang="ts">
import { computed, ref, watch } from 'vue';

interface SignLanguageAsset {
    id: number;
    external_id: string | null;
    role: string;
    url: string | null;
    mime_type: string | null;
    duration_seconds?: number | null;
    bytes?: number | null;
    width?: number | null;
    height?: number | null;
    variant?: string | null;
    processing_status?: string | null;
    review_required?: boolean;
}

const props = withDefaults(defineProps<{
    asset: SignLanguageAsset;
    label: string;
    compact?: boolean;
    testId?: string;
}>(), {
    compact: false,
    testId: 'pjm-video',
});

const videoElement = ref<HTMLVideoElement | null>(null);
const isPlaying = ref(false);
const hasEnded = ref(false);

const videoKey = computed(() => `${props.asset.id}-${props.asset.url ?? ''}`);
const playbackButtonLabel = computed(() => {
    return hasEnded.value ? 'Odtwórz ponownie film PJM' : 'Odtwórz film PJM';
});

const resetPlaybackState = () => {
    isPlaying.value = false;
    hasEnded.value = false;
};

const playVideo = async () => {
    const video = videoElement.value;

    if (!video) {
        return;
    }

    if (video.ended) {
        video.currentTime = 0;
    }

    try {
        await video.play();
    } catch {
        isPlaying.value = false;
    }
};

watch(videoKey, resetPlaybackState);
</script>

<template>
    <div
        class="relative flex h-full w-full items-center justify-center overflow-hidden bg-[#f8fafc]"
        :class="compact ? 'min-h-[8.5rem]' : 'min-h-[16rem]'"
        @contextmenu.prevent
    >
        <p class="sr-only">
            {{ label }}
        </p>

        <video
            v-if="asset.url"
            :key="videoKey"
            ref="videoElement"
            :data-testid="testId"
            class="h-full max-h-full w-full max-w-full object-contain"
            preload="metadata"
            playsinline
            disablepictureinpicture
            controlslist="nodownload noremoteplayback"
            @play="isPlaying = true; hasEnded = false"
            @pause="isPlaying = false"
            @ended="isPlaying = false; hasEnded = true"
            @loadedmetadata="resetPlaybackState"
        >
            <source :src="asset.url" :type="asset.mime_type ?? undefined" />
        </video>

        <button
            v-if="asset.url && !isPlaying"
            type="button"
            :aria-label="playbackButtonLabel"
            :title="playbackButtonLabel"
            class="group absolute inset-0 z-10 flex items-center justify-center bg-transparent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-6px] focus-visible:outline-[#111827]"
            @click="playVideo"
        >
            <span
                class="flex h-13 w-13 items-center justify-center rounded-full bg-white/82 text-[#111827] shadow-[0_10px_24px_rgba(15,23,42,0.16)] ring-1 ring-black/5 backdrop-blur-[2px] transition duration-200 group-hover:scale-105 group-hover:bg-white/92 group-focus-visible:scale-105 group-focus-visible:bg-white"
                aria-hidden="true"
            >
                <span
                    class="ml-1 h-0 w-0 border-y-[0.58rem] border-l-[0.92rem] border-y-transparent border-l-current"
                />
            </span>
        </button>

        <div
            v-if="!asset.url"
            class="px-4 py-8 text-center text-sm leading-6 text-[#64748b]"
        >
            Film PJM nie ma jeszcze poprawnego adresu pliku.
        </div>
    </div>
</template>
