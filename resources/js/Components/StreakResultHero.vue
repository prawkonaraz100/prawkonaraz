<script setup lang="ts">
import { computed } from 'vue';
import { Clock } from '@lucide/vue';
import { formatStreakDuration } from '@/utils/streakDuration';
import { streakResultSummary } from '@/utils/streakResultSummary';
import recordArt from '../../images/session/streak-result-record-dark.png';
import medalArt from '../../images/session/streak-result-medal-dark.png';

const props = defineProps<{ score: number; previousBest: number; durationSeconds?: number | null }>();
const summary = computed(() => streakResultSummary(props.score, props.previousBest));
</script>

<template>
    <div class="streak-result-hero" :data-result-state="summary.isNewRecord ? 'record' : 'regular'">
        <div class="streak-result-hero__art" aria-hidden="true">
            <img :src="summary.isNewRecord ? recordArt : medalArt" alt="" :width="summary.isNewRecord ? 160 : 84" :height="summary.isNewRecord ? 88 : 84" />
        </div>
        <h1 id="streak-result-title" class="streak-result-hero__heading">{{ summary.heading }}</h1>
        <p class="streak-result-hero__subtitle">{{ summary.subtitle }}</p>
        <div class="streak-result-hero__score-row">
            <strong class="streak-result-hero__score" :aria-label="`${score} poprawnych odpowiedzi z rzędu`">{{ score }}</strong>
            <span v-if="durationSeconds != null" class="streak-result-hero__time" title="Czas serii do ostatniej poprawnej odpowiedzi"><Clock :size="13" aria-hidden="true" />{{ formatStreakDuration(durationSeconds) }}</span>
        </div>
        <p class="streak-result-hero__record">{{ summary.recordCaption }}</p>
        <div class="streak-result-hero__badge" :class="{ 'streak-result-hero__badge--record': summary.isNewRecord }">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m3 17 6-6 4 4 8-9M16 6h5v5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span>{{ summary.badgeCaption }}</span>
        </div>
    </div>
</template>

<style scoped>
/* Mobile-only component (StreakChallenge result), styled for the dark mobile theme. */
.streak-result-hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: rgb(255 255 255 / 85%);
    font-family: Inter, Arial, sans-serif;
    text-align: center;
}
.streak-result-hero__art {
    display: flex;
    height: 88px;
    align-items: center;
    justify-content: center;
}
.streak-result-hero__art img { display: block; max-width: 100%; object-fit: contain; }
.streak-result-hero__heading { margin: 8px 0 0; font-size: 26px; font-weight: 800; line-height: 1.2; letter-spacing: -0.045em; }
.streak-result-hero__subtitle { margin: 5px 0 0; color: rgb(255 255 255 / 55%); font-size: 13px; line-height: 19px; }
.streak-result-hero__score { display: block; margin-top: 7px; color: #fff; font-size: 64px; font-weight: 800; line-height: 1.1; letter-spacing: -0.055em; font-variant-numeric: tabular-nums; }
.streak-result-hero__score-row { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; }
.streak-result-hero__time { display: inline-flex; align-items: center; gap: 4px; color: rgb(255 255 255 / 55%); font-size: 13px; line-height: 18px; font-variant-numeric: tabular-nums; }
.streak-result-hero__record { margin: 5px 0 0; color: rgb(255 255 255 / 55%); font-size: 12px; line-height: 18px; }
.streak-result-hero__badge { display: inline-flex; min-height: 30px; max-width: 100%; margin-top: 9px; align-items: center; justify-content: center; gap: 10px; border-radius: 999px; padding: 6px 16px; background: rgb(255 201 74 / 12%); color: #ffc94a; font-size: 11px; font-weight: 700; line-height: 16px; }
.streak-result-hero__badge svg { flex-shrink: 0; }
.streak-result-hero__badge--record { background: rgb(129 182 76 / 15%); color: #81b64c; }
@media (max-width: 359px) {
    .streak-result-hero__heading { font-size: 24px; }
}
</style>
