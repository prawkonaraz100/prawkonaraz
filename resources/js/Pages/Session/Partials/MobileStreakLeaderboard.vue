<script setup lang="ts">
import { formatStreakDuration, type StreakLeader } from '@/utils/streakDuration';

// Dark phone leaderboard styled after chess.com's game archive table:
// section rgba(0,0,0,.2) r5, sub-header with a 5% divider, header row rgba(0,0,0,.14) at 40px,
// 12px/600 header labels, zebra rows (even rows white 2%) and white 85% / 72% / 50% text.
withDefaults(defineProps<{
    leaders: StreakLeader[];
    caption?: string;
    showDuration?: boolean;
    label?: string;
}>(), {
    caption: '',
    showDuration: true,
    label: 'Ranking najlepszych serii',
});

const rankClass = (position: number): string => (
    position === 1
        ? 'bg-[#ffc94a] text-[#302e2b]'
        : position === 2
          ? 'bg-[#c9d1cd] text-[#302e2b]'
          : position === 3
            ? 'bg-[#d99a6c] text-[#302e2b]'
            : 'text-[#ffffffb8]'
);
</script>

<template>
    <div class="pk-panel overflow-hidden">
        <div
            v-if="caption"
            class="flex items-center justify-between gap-3 border-b border-[#ffffff0d] px-4 py-2 text-sm text-[#ffffffb8]"
        >
            <span class="truncate font-semibold">{{ caption }}</span>
            <span class="shrink-0 tabular-nums">{{ leaders.length }} {{ leaders.length === 1 ? 'gracz' : 'graczy' }}</span>
        </div>

        <div
            class="streak-board__grid h-10 bg-[#00000024] pl-1 pr-2 text-xs font-semibold text-[#ffffffb8]"
            :class="showDuration ? 'streak-board__grid--duration' : ''"
            aria-hidden="true"
        >
            <span class="text-center">#</span>
            <span>Gracz</span>
            <span class="text-center">Seria</span>
            <span v-if="showDuration" class="text-right">Czas</span>
        </div>

        <ol :aria-label="label">
            <li
                v-for="leader in leaders"
                :key="leader.position"
                class="streak-board__grid streak-board__row py-3 pl-1 pr-2"
                :class="[showDuration ? 'streak-board__grid--duration' : '', leader.is_me ? 'streak-board__row--me' : '']"
            >
                <span class="grid place-items-center" :aria-label="`Miejsce ${leader.position}`">
                    <span class="grid h-5 min-w-5 place-items-center rounded-[3px] px-1 text-xs font-bold tabular-nums" :class="rankClass(leader.position)">
                        {{ leader.position }}
                    </span>
                </span>

                <span class="flex min-w-0 items-center gap-2.5">
                    <span class="grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-[3px] bg-[#ffffff1a] text-xs font-bold text-white">
                        <img v-if="leader.avatar_url" :src="leader.avatar_url" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                        <span v-else>{{ leader.initials }}</span>
                    </span>
                    <span class="min-w-0 truncate text-sm font-semibold text-[#ffffffd9]" :title="leader.name">{{ leader.name }}</span>
                    <span v-if="leader.is_me" class="shrink-0 rounded-[3px] bg-[#81b64c26] px-1.5 py-0.5 text-[0.6875rem] font-bold leading-none text-[#81b64c]">Ty</span>
                </span>

                <strong class="text-center text-sm font-bold tabular-nums text-white">{{ leader.score }}</strong>

                <span
                    v-if="showDuration"
                    class="whitespace-nowrap text-right text-[0.8125rem] tabular-nums text-[#ffffff80]"
                    :title="`Czas serii: ${formatStreakDuration(leader.duration_seconds)}`"
                >
                    {{ formatStreakDuration(leader.duration_seconds) }}
                </span>
            </li>
        </ol>
    </div>
</template>

<style scoped>
.streak-board__grid {
    display: grid;
    grid-template-columns: 2.25rem minmax(0, 1fr) 3.25rem;
    align-items: center;
    column-gap: 0.5rem;
}

.streak-board__grid--duration {
    grid-template-columns: 2.25rem minmax(0, 1fr) 3.25rem 3.5rem;
}

.streak-board__row:nth-child(2n) {
    background-color: rgb(255 255 255 / 2%);
}

.streak-board__row.streak-board__row--me {
    background-color: rgb(129 182 76 / 8%);
    box-shadow: inset 3px 0 0 #81b64c;
}
</style>
