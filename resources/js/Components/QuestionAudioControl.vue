<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { Pause, Volume2 } from '@lucide/vue';

interface QuestionAudioAsset {
    type: string;
    url: string | null;
    duration_seconds: number | null;
    encoding_format: string | null;
    transcript: string | null;
    asset_key?: string | null;
}

const props = withDefaults(defineProps<{
    asset: QuestionAudioAsset | null;
    autoplay?: boolean;
    autoplayKey?: string | number | null;
    variant?: 'classic' | 'zen';
}>(), {
    autoplay: false,
    autoplayKey: null,
    variant: 'zen',
});

const emit = defineEmits<{
    play: [];
}>();

const audioElement = ref<HTMLAudioElement | null>(null);
const sourceReady = ref(false);
const isPlaying = ref(false);
const hasEnded = ref(false);
const hasPlaybackError = ref(false);

const hasAudio = computed(() => Boolean(props.asset?.url));
const toggleLabel = computed(() => {
    if (hasPlaybackError.value) {
        return 'Spróbuj ponownie odtworzyć audio pytania';
    }

    if (isPlaying.value) {
        return 'Pauza audio pytania';
    }

    return hasEnded.value ? 'Odtwórz audio pytania ponownie' : 'Odtwórz audio pytania';
});
const buttonClass = computed(() =>
    props.variant === 'classic'
        ? 'bg-[#0071ce] text-white hover:bg-[#005fae] focus-visible:outline-[#005fae]'
        : 'bg-[#1f2937] text-white hover:bg-[#111827] focus-visible:outline-[#1f2937]',
);

const ensureSource = () => {
    const audio = audioElement.value;

    if (!audio || sourceReady.value || !props.asset?.url) {
        return;
    }

    audio.src = props.asset.url;
    audio.load();
    sourceReady.value = true;
};

const play = async () => {
    if (!hasAudio.value) {
        return;
    }

    await nextTick();
    ensureSource();

    const audio = audioElement.value;

    if (!audio) {
        return;
    }

    try {
        hasPlaybackError.value = false;

        if (hasEnded.value) {
            audio.currentTime = 0;
            hasEnded.value = false;
        }

        emit('play');
        await audio.play();
    } catch {
        isPlaying.value = false;
        hasPlaybackError.value = true;
    }
};

const pause = () => {
    audioElement.value?.pause();
};

const stop = () => {
    const audio = audioElement.value;

    if (!audio) {
        return;
    }

    audio.pause();
    audio.currentTime = 0;
    isPlaying.value = false;
    hasEnded.value = false;
};

const togglePlayback = () => {
    const audio = audioElement.value;

    if (audio && !audio.paused && !audio.ended) {
        pause();
        return;
    }

    void play();
};

const resetPlayback = () => {
    stop();
    sourceReady.value = false;
    hasPlaybackError.value = false;
};

const preventMediaMenu = (event: Event) => {
    event.preventDefault();
};

watch(
    () => props.autoplayKey,
    () => {
        if (props.autoplay && hasAudio.value) {
            void play();
        }
    },
);

watch(
    () => props.autoplay,
    (enabled) => {
        if (enabled && hasAudio.value) {
            void play();
        }
    },
);

watch(
    () => props.asset?.url ?? null,
    resetPlayback,
);

onBeforeUnmount(stop);

onMounted(() => {
    if (props.autoplay && hasAudio.value) {
        void play();
    }
});

defineExpose({
    pause,
    play,
    stop,
});
</script>

<template>
    <span
        v-if="hasAudio"
        class="inline-flex items-center"
        data-testid="session-question-audio"
    >
        <button
            type="button"
            class="inline-flex h-6 w-6 items-center justify-center rounded-full transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
            :class="buttonClass"
            :aria-label="toggleLabel"
            :title="toggleLabel"
            @click="togglePlayback"
        >
            <Volume2
                v-if="!isPlaying"
                :size="15"
                :stroke-width="2.35"
                aria-hidden="true"
            />
            <Pause
                v-else
                :size="13"
                :stroke-width="2.5"
                aria-hidden="true"
            />
        </button>

        <audio
            ref="audioElement"
            class="hidden"
            preload="none"
            controlslist="nodownload noremoteplayback"
            disableremoteplayback
            @play="isPlaying = true; hasEnded = false; hasPlaybackError = false"
            @pause="isPlaying = false"
            @ended="isPlaying = false; hasEnded = true"
            @error="hasPlaybackError = true; isPlaying = false"
            @contextmenu="preventMediaMenu"
            @dragstart="preventMediaMenu"
        />
    </span>
</template>
