<script setup lang="ts">
import { computed } from 'vue';
import { formatStreakDuration, type StreakLeader } from '@/utils/streakDuration';
import trophyArt from '../../../../images/session/streak-result-record-dark.png';
import podiumTrophy from '../../../../images/session/streak-podium-trophy-dark.png';

// Dark phone leaderboard styled after chess.com league standings:
// gold diamond-pattern banner with trophy art, header row rgba(0,0,0,.2) 35px with 12px labels,
// 40px rows with #1/#2/#3 gold/silver/bronze rank badges (32px, r3), 16px/700 white scores,
// a "promotion" style divider under the podium and the player's own row pinned below the list.
const props = withDefaults(defineProps<{
    leaders: StreakLeader[];
    title?: string;
    titleId?: string;
    caption?: string;
    showDuration?: boolean;
    label?: string;
    /** The player's own standing when it is outside `leaders`. */
    me?: StreakLeader | null;
}>(), {
    title: 'Najlepsi w serii',
    titleId: undefined,
    caption: '',
    showDuration: true,
    label: 'Ranking najlepszych serii',
    me: null,
});

const pinnedMe = computed(() => (props.me && !props.leaders.some((leader) => leader.is_me) ? props.me : null));

const rankClass = (position: number): string => (
    position === 1
        ? 'bg-[#f7c631] text-[#312e2b]'
        : position === 2
          ? 'bg-[#bebdb9] text-[#312e2b]'
          : position === 3
            ? 'bg-[#d5a47d] text-[#312e2b]'
            : 'text-[#ffffffd9]'
);
</script>

<template>
    <div class="pk-panel overflow-hidden">
        <div class="streak-board__banner flex min-h-[5.5rem] items-center gap-3 py-3 pl-2 pr-3">
            <img :src="trophyArt" alt="" width="104" height="57" class="h-auto w-[6.5rem] shrink-0" aria-hidden="true">
            <div class="min-w-0 flex-1">
                <h2 :id="titleId" class="text-[1.375rem] font-extrabold leading-tight tracking-[-0.01em] text-white">{{ title }}</h2>
                <p v-if="caption" class="mt-1 truncate text-sm text-white">{{ caption }} · rekordy</p>
            </div>
            <slot name="action" />
        </div>

        <div class="px-2 pb-2 pt-2">
            <div
                class="streak-board__grid h-[2.1875rem] bg-[#00000033] px-1 text-xs text-[#ffffffd9]"
                :class="showDuration ? 'streak-board__grid--duration' : ''"
                aria-hidden="true"
            >
                <span class="pl-0.5">Miejsce</span>
                <span>Gracz</span>
                <span class="text-right">Seria</span>
                <span v-if="showDuration" class="text-right">Czas</span>
            </div>

            <ol :aria-label="label">
                <template v-for="leader in leaders" :key="leader.position">
                    <li
                        class="streak-board__grid streak-board__row min-h-10 rounded-[2px] px-1 py-1"
                        :class="[showDuration ? 'streak-board__grid--duration' : '', leader.is_me ? 'streak-board__row--me' : '']"
                    >
                        <span class="grid h-8 w-9 place-items-center rounded-[3px] text-sm font-semibold tabular-nums" :class="rankClass(leader.position)" :aria-label="`Miejsce ${leader.position}`">
                            #{{ leader.position }}
                        </span>

                        <span class="flex min-w-0 items-center gap-2.5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-[3px] bg-[#ffffff1a] text-xs font-bold text-white">
                                <img v-if="leader.avatar_url" :src="leader.avatar_url" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                                <span v-else>{{ leader.initials }}</span>
                            </span>
                            <span class="min-w-0 truncate text-sm font-semibold text-[#ffffffb8]" :class="leader.is_me ? 'text-white' : ''" :title="leader.name">{{ leader.name }}</span>
                            <span v-if="leader.is_me" class="shrink-0 rounded-[3px] bg-[#81b64c26] px-1.5 py-0.5 text-[0.6875rem] font-bold leading-none text-[#81b64c]">Ty</span>
                        </span>

                        <strong class="text-right text-base font-bold tabular-nums text-white">{{ leader.score }}</strong>

                        <span
                            v-if="showDuration"
                            class="whitespace-nowrap text-right text-[0.8125rem] tabular-nums text-[#ffffff80]"
                            :title="`Czas serii: ${formatStreakDuration(leader.duration_seconds)}`"
                        >
                            {{ formatStreakDuration(leader.duration_seconds) }}
                        </span>
                    </li>

                    <li
                        v-if="leader.position === 3"
                        class="my-1 flex h-[2.1875rem] items-center bg-[#00000033] px-2 text-[#ffffffb8]"
                        aria-hidden="true"
                    >
                        <span class="text-base leading-none">↑</span>
                        <span class="flex flex-1 items-center justify-center gap-2 text-sm font-semibold">
                            <img :src="podiumTrophy" alt="" width="20" height="21" class="h-5 w-auto">
                            Podium
                        </span>
                        <span class="text-base leading-none">↑</span>
                    </li>
                </template>

                <template v-if="pinnedMe">
                    <li class="flex h-6 items-center justify-center text-sm leading-none tracking-[0.2em] text-[#ffffff4d]" aria-hidden="true">···</li>
                    <li
                        class="streak-board__grid streak-board__row streak-board__row--me min-h-10 rounded-[2px] px-1 py-1"
                        :class="showDuration ? 'streak-board__grid--duration' : ''"
                    >
                        <span class="grid h-8 w-9 place-items-center rounded-[3px] text-sm font-semibold tabular-nums text-[#ffffffd9]" :aria-label="`Twoje miejsce: ${pinnedMe.position}`">
                            #{{ pinnedMe.position }}
                        </span>
                        <span class="flex min-w-0 items-center gap-2.5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-[3px] bg-[#ffffff1a] text-xs font-bold text-white">
                                <img v-if="pinnedMe.avatar_url" :src="pinnedMe.avatar_url" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                                <span v-else>{{ pinnedMe.initials }}</span>
                            </span>
                            <span class="min-w-0 truncate text-sm font-semibold text-white" :title="pinnedMe.name">{{ pinnedMe.name }}</span>
                            <span class="shrink-0 rounded-[3px] bg-[#81b64c26] px-1.5 py-0.5 text-[0.6875rem] font-bold leading-none text-[#81b64c]">Ty</span>
                        </span>
                        <strong class="text-right text-base font-bold tabular-nums text-white">{{ pinnedMe.score }}</strong>
                        <span v-if="showDuration" class="whitespace-nowrap text-right text-[0.8125rem] tabular-nums text-[#ffffff80]">
                            {{ formatStreakDuration(pinnedMe.duration_seconds) }}
                        </span>
                    </li>
                </template>
            </ol>
        </div>
    </div>
</template>

<style scoped>
/* chess.com league banner: gold base with an 80px diamond pattern. */
.streak-board__banner {
    background-color: #7e6a31;
    background-image:
        linear-gradient(45deg, transparent 75%, #8a7636 0),
        linear-gradient(135deg, transparent 75%, #8a7636 0),
        linear-gradient(225deg, transparent 75%, #8a7636 0),
        linear-gradient(315deg, transparent 75%, #8a7636 0);
    background-position: 100% 0;
    background-size: 80px 80px;
}

.streak-board__grid {
    display: grid;
    grid-template-columns: 2.75rem minmax(0, 1fr) 3rem;
    align-items: center;
    column-gap: 0.5rem;
}

.streak-board__grid--duration {
    grid-template-columns: 2.75rem minmax(0, 1fr) 2.75rem 3.25rem;
}

.streak-board__row.streak-board__row--me {
    background-color: rgb(129 182 76 / 10%);
    box-shadow: inset 3px 0 0 #81b64c;
}
</style>
