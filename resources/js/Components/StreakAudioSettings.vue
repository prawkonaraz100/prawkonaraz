<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Settings, X } from '@lucide/vue';
import type { StreakVideoPlaybackRate } from '@/utils/streakVideoPreferences';

const props = defineProps<{ open: boolean; showQuestionAudioControl: boolean; autoPlayQuestionAudio: boolean; autoPlayVideos: boolean; videoPlaybackRate: StreakVideoPlaybackRate; autoJumpToVideoEnding: boolean }>();
const emit = defineEmits<{
    close: [];
    'update:showQuestionAudioControl': [enabled: boolean];
    'update:autoPlayQuestionAudio': [enabled: boolean];
    'update:autoPlayVideos': [enabled: boolean];
    'update:videoPlaybackRate': [rate: StreakVideoPlaybackRate];
    'update:autoJumpToVideoEnding': [enabled: boolean];
}>();
const dialog = ref<HTMLDialogElement | null>(null);
let previousOverflow: string | null = null;
const unlockScroll = () => {
    if (previousOverflow !== null) document.body.style.overflow = previousOverflow;
    previousOverflow = null;
};
watch(() => props.open, async (open) => {
    await nextTick();
    if (!dialog.value || props.open !== open) return;
    if (open) {
        if (previousOverflow === null) previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        if (!dialog.value?.open) dialog.value?.showModal();
    } else {
        dialog.value?.close();
        unlockScroll();
    }
}, { immediate: true });
onBeforeUnmount(unlockScroll);
</script>

<template>
    <Teleport to="body">
        <dialog ref="dialog" aria-labelledby="streak-audio-settings-title" class="streak-audio-settings m-0 h-[100dvh] max-h-none w-screen max-w-none bg-transparent p-0 text-[#ffffffd9] backdrop:bg-black/60" @cancel.prevent="emit('close')" @click="($event.target === dialog) && emit('close')">
            <section class="absolute inset-x-0 bottom-0 mx-auto max-h-[85dvh] max-w-lg overflow-y-auto rounded-t-[14px] border-t border-[#ffffff1a] bg-[#302e2b] px-5 pt-5 pb-[calc(env(safe-area-inset-bottom,0px)+1.25rem)] shadow-[0_-12px_32px_rgba(0,0,0,0.45)]">
                <header class="flex items-center justify-between gap-3">
                    <h2 id="streak-audio-settings-title" class="flex items-center gap-2 text-lg font-bold text-white"><Settings :size="22" aria-hidden="true" />Ustawienia treningu</h2>
                    <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[#ffffff1a] text-[#ffffffd9] focus-visible:outline focus-visible:outline-2 focus-visible:outline-white/80" aria-label="Zamknij ustawienia treningu" @click="emit('close')"><X :size="20" aria-hidden="true" /></button>
                </header>
                <h3 class="mt-4 font-semibold text-white">Czytanie pytania na głos</h3>
                <p class="mt-1 text-sm leading-6 text-[#ffffff80]">Wybierz, czy pytanie ma być przeczytane na głos.</p>
                <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-[5px] border border-transparent bg-[#00000033] p-4">
                    <input type="checkbox" class="mt-1 h-5 w-5 shrink-0 rounded border-[#ffffff4d] bg-transparent text-[#81b64c] focus:ring-[#81b64c] focus:ring-offset-[#302e2b]" :checked="showQuestionAudioControl" @change="emit('update:showQuestionAudioControl', ($event.target as HTMLInputElement).checked)">
                    <span><span class="block text-sm font-semibold">Pokaż ikonę głośnika</span><span class="mt-1 block text-sm leading-6 text-[#ffffff80]">Przy pytaniu pojawi się ikona głośnika. Kliknij ją, aby usłyszeć pytanie.</span></span>
                </label>
                <label class="mt-3 flex items-start gap-3 rounded-[5px] border border-transparent bg-[#00000033] p-4" :class="showQuestionAudioControl ? 'cursor-pointer' : 'opacity-60'">
                    <input type="checkbox" class="mt-1 h-5 w-5 shrink-0 rounded border-[#ffffff4d] bg-transparent text-[#81b64c] focus:ring-[#81b64c] focus:ring-offset-[#302e2b]" :checked="autoPlayQuestionAudio" :disabled="!showQuestionAudioControl" @change="emit('update:autoPlayQuestionAudio', ($event.target as HTMLInputElement).checked)">
                    <span><span class="block text-sm font-semibold">Czytaj kolejne pytania automatycznie</span><span class="mt-1 block text-sm leading-6 text-[#ffffff80]">Po przejściu do następnego pytania jego treść zostanie przeczytana na głos. Jeśli dźwięk nie włączy się sam, kliknij ikonę głośnika.</span></span>
                </label>
                <section class="mt-6 border-t border-[#ffffff1a] pt-5" aria-labelledby="streak-video-settings-title">
                    <h3 id="streak-video-settings-title" class="font-semibold text-white">Pytania z filmem</h3>
                    <p class="mt-1 text-sm leading-6 text-[#ffffff80]">Zdecyduj, czy film ma ruszać sam, czy chcesz włączać go ręcznie.</p>
                    <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-[5px] border border-transparent bg-[#00000033] p-4">
                        <input type="checkbox" class="mt-1 h-5 w-5 shrink-0 rounded border-[#ffffff4d] bg-transparent text-[#81b64c] focus:ring-[#81b64c] focus:ring-offset-[#302e2b]" :checked="autoPlayVideos" @change="emit('update:autoPlayVideos', ($event.target as HTMLInputElement).checked)">
                        <span><span class="block text-sm font-semibold">Odtwarzaj filmy automatycznie</span><span class="mt-1 block text-sm leading-6 text-[#ffffff80]">Wyłącz to, jeśli wolisz sam uruchamiać filmy i mieć nad nimi pełną kontrolę.</span></span>
                    </label>
                    <div class="mt-3 rounded-[5px] border border-transparent bg-[#00000033] p-4">
                        <p id="streak-video-speed-label" class="text-sm font-semibold">Prędkość filmu</p>
                        <p class="mt-1 text-sm leading-6 text-[#ffffff80]">Możesz przyspieszyć film, żeby szybciej dotrzeć do ważnej sytuacji.</p>
                        <div class="mt-3 grid grid-cols-3 gap-2" role="group" aria-labelledby="streak-video-speed-label">
                            <button v-for="rate in ([1, 2, 4] as const)" :key="rate" type="button" class="min-h-11 rounded-[10px] border text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white/80" :class="videoPlaybackRate === rate ? 'border-[#81b64c] bg-[rgba(129,182,76,0.16)] text-white' : 'pk-btn-secondary border-transparent'" :aria-pressed="videoPlaybackRate === rate" :disabled="autoJumpToVideoEnding" @click="emit('update:videoPlaybackRate', rate)">{{ rate }}x</button>
                        </div>
                        <p v-if="autoJumpToVideoEnding" class="mt-2 text-xs leading-5 text-[#ffffff80]">Końcówka odtwarza się w normalnym tempie (1x). Wybór prędkości jest tymczasowo wyłączony.</p>
                    </div>
                    <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-[5px] border border-transparent bg-[#00000033] p-4">
                        <input type="checkbox" class="mt-1 h-5 w-5 shrink-0 rounded border-[#ffffff4d] bg-transparent text-[#81b64c] focus:ring-[#81b64c] focus:ring-offset-[#302e2b]" :checked="autoJumpToVideoEnding" @change="emit('update:autoJumpToVideoEnding', ($event.target as HTMLInputElement).checked)">
                        <span><span class="block text-sm font-semibold">Pokaż końcówkę filmu</span><span class="mt-1 block text-sm leading-6 text-[#ffffff80]">Każdy nowy film przeskoczy do ostatnich 2 sekund. Nadal możesz odtworzyć cały film albo wybrać końcówkę ręcznie na kadrze.</span></span>
                    </label>
                </section>
                <p class="mt-3 text-xs leading-5 text-[#ffffff80]">Ustawienia dotyczą tylko treningu na tym urządzeniu. Głośnik jest dostępny dla pytań z nagraniem. Film może startować automatycznie bez dźwięku podczas czytania pytania. Ręczne odtworzenie filmu z dźwiękiem zatrzymuje lektora.</p>
                <button type="button" class="pk-btn-primary mt-4 min-h-11 w-full px-4 font-semibold" @click="emit('close')">Gotowe</button>
            </section>
        </dialog>
    </Teleport>
</template>

<style scoped>
.streak-audio-settings:not([open]) { display: none; }
</style>
