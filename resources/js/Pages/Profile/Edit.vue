<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import FriendInvitationPanel from './Partials/FriendInvitationPanel.vue';
import SocialConnectionsForm from './Partials/SocialConnectionsForm.vue';
import UpdateProfileAvatarForm from './Partials/UpdateProfileAvatarForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { useSafeLogout } from '@/composables/useSafeLogout';
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface FriendInvitationPanelData {
    enabled: boolean;
    eligible: boolean;
    can_issue: boolean;
    reason: string | null;
    reason_label: string | null;
    pending_limit: number;
    pending_count: number;
    owner_access_expires_at: string | null;
    plan: {
        code: string | null;
        name: string | null;
    };
    active_guest: {
        public_id: string;
        name: string | null;
        email: string | null;
        access_expires_at: string | null;
        accepted_at: string | null;
    } | null;
    pending: Array<{
        public_id: string;
        created_at: string | null;
        expires_at: string | null;
        display_code_last4: string | null;
    }>;
    generated: {
        public_id: string;
        link: string;
        code: string;
        expires_at: string | null;
    } | null;
}

const props = defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    categories: Array<{
        id: number;
        code: string;
        name: string;
        short_name: string;
    }>;
    productProfile: {
        target_category_id: number | null;
        study_streak: number;
        tier: string;
    };
    canChangeTargetCategory: boolean;
    categoryLockMessage: string | null;
    socialConnections: {
        password_login_enabled: boolean;
        providers: Array<{
            provider: 'google' | 'facebook';
            label: string;
            connected: boolean;
            email: string | null;
            linked_at: string | null;
        }>;
    };
    friendInvitations: FriendInvitationPanelData;
    review: {
        exists: boolean;
        status: 'pending' | 'approved' | 'rejected' | null;
        status_label: string | null;
        edit_url: string;
    };
}>();

const targetCategoryLabel = computed(() => {
    const category = props.categories.find(
        (item) => item.id === props.productProfile.target_category_id,
    );

    return category?.short_name ?? category?.code ?? 'Nie wybrano';
});

const tierLabel = computed(() => {
    const labels: Record<string, string> = {
        free: 'Brak aktywnego dostępu',
        paid: 'Aktywny dostęp',
        premium: 'Aktywny dostęp',
        moderator: 'Dostęp moderatora',
        test: 'Konto testowe',
    };

    return labels[props.productProfile.tier] ?? props.productProfile.tier;
});

const studyStreakLabel = computed(() => {
    const days = props.productProfile.study_streak;
    const unit = days === 1 ? 'dzień' : 'dni';

    return `${days} ${unit}`;
});

const compactTierLabel = computed(() => ({
    free: 'Brak',
    paid: 'Aktywny',
    premium: 'Aktywny',
    moderator: 'Moderator',
    test: 'Testowe',
})[props.productProfile.tier] ?? tierLabel.value);

const hasActiveAccess = computed(() => props.productProfile.tier !== 'free');
const passwordStatusLabel = computed(
    () => props.socialConnections.password_login_enabled ? 'Ustawione' : 'Nie ustawiono',
);
const invitationStatusLabel = computed(() => {
    if (props.friendInvitations.active_guest) {
        return 'Aktywne';
    }

    if (props.friendInvitations.pending_count > 0) {
        return `${props.friendInvitations.pending_count} oczekuje`;
    }

    return null;
});
const reviewActionLabel = computed(() => props.review.exists ? 'Edytuj swoją opinię' : 'Dodaj swoją opinię');
const reviewStatusLabel = computed(() => props.review.status_label ?? 'Nie dodano');

const profileSummary = computed(() => [
    {
        label: 'Kategoria',
        value: targetCategoryLabel.value,
    },
    {
        label: 'Seria nauki',
        value: studyStreakLabel.value,
    },
    {
        label: 'Dostęp',
        value: tierLabel.value,
    },
]);

const profileSections = computed(() => [
    { href: '#zdjecie', label: 'Zdjęcie' },
    { href: '#dane-konta', label: 'Dane konta' },
    ...(props.friendInvitations.enabled
        ? [{ href: '#zapros-znajomego', label: 'Zaproszenia' }]
        : []),
    { href: props.review.edit_url, label: 'Moja opinia' },
    { href: '#haslo', label: 'Hasło' },
    { href: '#social-login', label: 'Logowanie' },
    { href: '#usun-konto', label: 'Usunięcie konta' },
]);

type ProfileSheet = 'avatar' | 'account' | 'invitations' | 'password' | 'social' | 'delete';

const profileSheetByHash: Record<string, ProfileSheet> = {
    '#zdjecie': 'avatar',
    '#dane-konta': 'account',
    '#zapros-znajomego': 'invitations',
    '#haslo': 'password',
    '#social-login': 'social',
    '#usun-konto': 'delete',
};

const page = usePage<PageProps>();
const profileUser = computed(() => page.props.auth.user);
const navigation = computed(() => page.props.navigation);
const {
    logout,
    logoutError,
    logoutPreparing,
} = useSafeLogout();
const openProfileSheet = ref<ProfileSheet | null>(null);
const isMobile = ref(false);
const profileDialog = ref<HTMLDialogElement | null>(null);
let mobileMedia: MediaQueryList | null = null;
let previousOverflow: string | null = null;
let sheetTrigger: HTMLElement | null = null;
const profileSheetTitle = computed(() => ({
    avatar: 'Zdjęcie profilowe',
    account: 'Dane konta',
    invitations: 'Zaproszenia',
    password: 'Hasło',
    social: 'Metody logowania',
    delete: 'Usuń konto',
})[openProfileSheet.value ?? 'account']);

const closeProfileSheet = () => {
    openProfileSheet.value = null;

    if (typeof window !== 'undefined' && profileSheetByHash[window.location.hash]) {
        window.history.replaceState(window.history.state, '', `${window.location.pathname}${window.location.search}`);
    }
};

const syncProfileSheetFromHash = () => {
    const requestedSheet = profileSheetByHash[window.location.hash] ?? null;

    openProfileSheet.value = isMobile.value
        && (requestedSheet !== 'invitations' || props.friendInvitations.enabled)
        ? requestedSheet
        : null;
};

const restoreSheetState = () => {
    if (previousOverflow !== null) {
        document.body.style.overflow = previousOverflow;
        previousOverflow = null;
    }
    if (sheetTrigger?.isConnected) {
        sheetTrigger.focus({ preventScroll: true });
    }
    sheetTrigger = null;
};

const syncViewport = () => {
    isMobile.value = mobileMedia?.matches ?? false;
    syncProfileSheetFromHash();
};

watch(openProfileSheet, async (section) => {
    if (!section) {
        profileDialog.value?.close();
        restoreSheetState();
        return;
    }

    if (previousOverflow === null) {
        previousOverflow = document.body.style.overflow;
        sheetTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    }
    document.body.style.overflow = 'hidden';
    await nextTick();
    if (openProfileSheet.value === section && isMobile.value && !profileDialog.value?.open) {
        profileDialog.value?.showModal();
    }
});

onMounted(() => {
    mobileMedia = window.matchMedia('(max-width: 767px)');
    syncViewport();
    mobileMedia.addEventListener('change', syncViewport);
    window.addEventListener('hashchange', syncProfileSheetFromHash);
});

onUnmounted(() => {
    restoreSheetState();
    mobileMedia?.removeEventListener('change', syncViewport);
    window.removeEventListener('hashchange', syncProfileSheetFromHash);
});
</script>

<template>
    <Head title="Profil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="max-w-4xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                    Profil
                </p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
                    Ustawienia konta
                </h1>
                <p class="mt-5 max-w-3xl text-base leading-7 text-slate-600 md:text-lg">
                    Zarządzaj danymi logowania, hasłem i połączeniami z Google lub Facebookiem.
                </p>
            </div>
        </template>

        <section class="-mx-4 -my-10 min-h-[calc(100svh-4.75rem)] bg-[#faf9f6] pb-[calc(5.5rem+env(safe-area-inset-bottom))] text-[#0a0c13] md:hidden" aria-label="Profil i ustawienia">
            <header class="mx-auto max-w-[30rem] px-4 pt-[max(env(safe-area-inset-top),0.75rem)]">
                <div class="flex items-center justify-between gap-3 px-1">
                    <h1 class="text-[2.15rem] font-extrabold leading-tight tracking-[-0.055em]">Profil</h1>
                    <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-[#0a0c13] shadow-[0_8px_22px_rgba(32,30,26,0.04)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#eeb230]" aria-label="Otwórz ustawienia konta" @click="openProfileSheet = 'account'">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2.7h4l.7 2.1 1.8.8 2-.9 2.8 2.8-.9 2 .8 1.8 2.1.7v4l-2.1.7-.8 1.8.9 2-2.8 2.8-2-.9-1.8.8-.7 2.1h-4l-.7-2.1-1.8-.8-2 .9-2.8-2.8.9-2-.8-1.8-2.1-.7v-4l2.1-.7.8-1.8-.9-2 2.8-2.8 2 .9 1.8-.8.7-2.1Z" transform="translate(0 -1.3) scale(.94)"/><circle cx="12" cy="12" r="3.1"/></svg>
                    </button>
                </div>

                <div class="profile-mobile-hero relative mt-2.5 overflow-hidden rounded-[1.35rem] bg-white p-3.5 shadow-[0_14px_35px_rgba(48,38,20,0.045)]">
                    <div class="relative z-10 flex min-h-[4.5rem] items-center gap-3.5">
                        <button type="button" class="relative h-[4.5rem] w-[4.5rem] shrink-0 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-[#eeb230] focus-visible:ring-offset-2" aria-label="Zmień zdjęcie profilowe" @click="openProfileSheet = 'avatar'">
                            <span class="grid h-full w-full place-items-center overflow-hidden rounded-full bg-[#e9edf2] text-xl font-bold text-[#28394e]">
                                <img v-if="profileUser?.avatar_url" :src="profileUser.avatar_url" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                                <span v-else>{{ profileUser?.avatar_initials ?? 'U' }}</span>
                            </span>
                            <span class="absolute -bottom-0.5 -right-0.5 grid h-6 w-6 place-items-center rounded-full border-2 border-white text-white" :class="hasActiveAccess || profileUser?.is_admin ? 'bg-[#ffba20]' : 'bg-[#667085]'" aria-hidden="true">
                                <svg v-if="hasActiveAccess || profileUser?.is_admin" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="m2 6 5 4 5-7 5 7 5-4-2 13H4L2 6Z"/></svg>
                                <svg v-else class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h3l1.5-2h7L17 8h3v11H4V8Z"/><circle cx="12" cy="13" r="3"/></svg>
                            </span>
                        </button>
                        <button type="button" class="min-w-0 flex-1 text-left focus:outline-none focus-visible:rounded-lg focus-visible:ring-2 focus-visible:ring-[#eeb230]" aria-label="Edytuj dane konta" @click="openProfileSheet = 'account'">
                            <span class="block truncate text-[1.25rem] font-bold leading-tight tracking-[-0.03em]">{{ profileUser?.name ?? 'Twoje konto' }}</span>
                            <span class="mt-0.5 block truncate text-[0.82rem] text-[#747a85]">{{ profileUser?.email }}</span>
                        </button>
                    </div>

                    <div class="relative z-10 mt-3 grid grid-cols-3 gap-1.5" aria-label="Podsumowanie konta">
                        <div class="profile-mobile-stat">
                            <span class="profile-mobile-stat__icon" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14h2l2-5h8l2 5h2M6 14h12v4H6z"/><circle cx="8" cy="18" r="1.5"/><circle cx="16" cy="18" r="1.5"/></svg></span>
                            <span class="min-w-0"><span class="block truncate text-[0.68rem] text-[#6f7480]">Kategoria</span><strong class="block truncate text-[1rem] leading-tight">{{ targetCategoryLabel }}</strong></span>
                        </div>
                        <div class="profile-mobile-stat">
                            <span class="profile-mobile-stat__icon" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 2.5v5M16 2.5v5M4 10h16"/></svg></span>
                            <span class="min-w-0"><span class="block truncate text-[0.68rem] text-[#6f7480]">Seria</span><strong class="block truncate text-[1rem] leading-tight tabular-nums">{{ studyStreakLabel }}</strong></span>
                        </div>
                        <div class="profile-mobile-stat">
                            <span class="profile-mobile-stat__icon" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 5 4 5-7 5 7 5-4-1.5 12h-17L2 7Z"/></svg></span>
                            <span class="min-w-0"><span class="block truncate text-[0.68rem] text-[#6f7480]">Dostęp</span><strong class="block truncate text-[1rem] leading-tight">{{ compactTierLabel }}</strong></span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-[30rem] space-y-3 px-4 pb-4 pt-3">
                <section aria-labelledby="profile-account-title">
                    <h2 id="profile-account-title" class="mb-1.5 px-1 text-sm font-medium text-[#6f7480]">Konto</h2>
                    <div class="profile-mobile-group">
                        <button type="button" class="profile-mobile-row" @click="openProfileSheet = 'account'">
                            <span class="profile-mobile-row__icon" aria-hidden="true">
                                <svg class="h-[1.15rem] w-[1.15rem]" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="8" r="3.25" stroke="currentColor" stroke-width="1.8" />
                                    <path d="M5.5 19c.7-3.6 2.9-5.4 6.5-5.4s5.8 1.8 6.5 5.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.9rem] font-semibold text-[#101828]">Dane konta</span>
                                <span class="mt-0.5 block truncate text-[0.76rem] text-[#747a85]">Imię i adres e-mail</span>
                            </span>
                            <span class="profile-mobile-row__trailing">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>

                        <a class="profile-mobile-row" :href="review.edit_url">
                            <span class="profile-mobile-row__icon" aria-hidden="true">
                                <svg class="h-[1.15rem] w-[1.15rem]" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 5.5h16v11H9l-5 4v-15Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.9rem] font-semibold text-[#101828]">{{ reviewActionLabel }}</span>
                                <span class="mt-0.5 block truncate text-[0.76rem] text-[#747a85]">{{ reviewStatusLabel }}</span>
                            </span>
                            <span class="profile-mobile-row__trailing">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </a>

                        <button v-if="friendInvitations.enabled" type="button" class="profile-mobile-row" @click="openProfileSheet = 'invitations'">
                            <span class="profile-mobile-row__icon" aria-hidden="true">
                                <svg class="h-[1.15rem] w-[1.15rem]" viewBox="0 0 24 24" fill="none">
                                    <path d="M4.5 10h15v10h-15V10ZM3.5 7h17v3h-17V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                    <path d="M12 7v13M8.2 7c-1.5 0-2.4-.7-2.4-1.7S6.6 3.6 7.7 3.6C9.3 3.6 10.5 5 12 7M15.8 7c1.5 0 2.4-.7 2.4-1.7s-.8-1.7-1.9-1.7C14.7 3.6 13.5 5 12 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.9rem] font-semibold text-[#101828]">Zaproszenia</span>
                                <span class="mt-0.5 block truncate text-[0.76rem] text-[#747a85]">Udostępnij dostęp znajomej osobie</span>
                            </span>
                            <span class="profile-mobile-row__trailing">
                                <span v-if="invitationStatusLabel" class="max-w-20 truncate text-[0.7rem] text-[#667085]">{{ invitationStatusLabel }}</span>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </section>

                <section aria-labelledby="profile-security-title">
                    <h2 id="profile-security-title" class="mb-1.5 px-1 text-sm font-medium text-[#6f7480]">Bezpieczeństwo</h2>
                    <div class="profile-mobile-group">
                        <button type="button" class="profile-mobile-row" @click="openProfileSheet = 'password'">
                            <span class="profile-mobile-row__icon" aria-hidden="true">
                                <svg class="h-[1.15rem] w-[1.15rem]" viewBox="0 0 24 24" fill="none">
                                    <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8" />
                                    <path d="M8 10V7.5a4 4 0 0 1 8 0V10M12 14v2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.9rem] font-semibold text-[#101828]">Hasło</span>
                                <span class="mt-0.5 block truncate text-[0.76rem] text-[#747a85]">Zmień lub ustaw hasło</span>
                            </span>
                            <span class="profile-mobile-row__trailing">
                                <span class="max-w-24 truncate rounded-full px-2.5 py-1 text-[0.68rem] font-medium" :class="socialConnections.password_login_enabled ? 'bg-[#eaf7ee] text-[#176840]' : 'bg-[#fff5e4] text-[#9b6410]'">{{ passwordStatusLabel }}</span>
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>

                        <button type="button" class="profile-mobile-row" @click="openProfileSheet = 'social'">
                            <span class="profile-mobile-row__icon" aria-hidden="true">
                                <svg class="h-[1.15rem] w-[1.15rem]" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2.5 19 5v6.3c0 4.3-2.7 7.8-7 10.2-4.3-2.4-7-5.9-7-10.2V5l7-2.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" /><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.9rem] font-semibold text-[#101828]">Metody logowania</span>
                                <span class="mt-0.5 block truncate text-[0.76rem] text-[#747a85]">Google i Facebook</span>
                            </span>
                            <span class="profile-mobile-row__trailing">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </section>

                <section v-if="navigation.logout_href" aria-labelledby="profile-session-title">
                    <h2 id="profile-session-title" class="mb-1.5 px-1 text-sm font-medium text-[#6f7480]">Sesja</h2>
                    <div class="profile-mobile-group">
                        <button
                            type="button"
                            class="profile-mobile-row disabled:cursor-wait disabled:opacity-60"
                            :disabled="logoutPreparing"
                            @click="logout(navigation.logout_href)"
                        >
                            <span class="profile-mobile-row__icon profile-mobile-row__icon--danger" aria-hidden="true">
                                <svg class="h-[1.15rem] w-[1.15rem]" viewBox="0 0 24 24" fill="none">
                                    <path d="M10 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19H10M14 8l4 4-4 4M18 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.9rem] font-semibold text-[#101828]">
                                    {{ logoutPreparing ? 'Wylogowywanie...' : 'Wyloguj' }}
                                </span>
                                <span class="mt-0.5 block truncate text-[0.76rem] text-[#747a85]">Zakończ sesję na tym urządzeniu</span>
                            </span>
                            <span class="profile-mobile-row__trailing" aria-hidden="true">
                                <svg v-if="logoutPreparing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2" opacity="0.25" />
                                    <path d="M20 12a8 8 0 0 0-8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            </span>
                        </button>
                    </div>
                    <p v-if="logoutError" class="mt-2 px-1 text-[0.72rem] font-medium text-[#b42318]" role="alert">
                        {{ logoutError }}
                    </p>
                </section>

                <section aria-labelledby="profile-danger-title">
                    <h2 id="profile-danger-title" class="mb-1.5 px-1 text-sm font-medium text-[#6f7480]">Konto i dane</h2>
                    <div class="profile-mobile-group">
                        <button type="button" class="profile-mobile-row" @click="openProfileSheet = 'delete'">
                            <span class="profile-mobile-row__icon profile-mobile-row__icon--danger" aria-hidden="true">
                                <svg class="h-[1.15rem] w-[1.15rem]" viewBox="0 0 24 24" fill="none">
                                    <path d="M8 8v10M12 8v10M16 8v10M5 5h14M9 5V3.5h6V5M6.5 5l.7 16h9.6l.7-16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[0.9rem] font-semibold text-[#101828]">Usuń konto</span>
                                <span class="mt-0.5 block truncate text-[0.76rem] text-[#747a85]">Usuń dane i historię nauki</span>
                            </span>
                            <span class="profile-mobile-row__trailing">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </section>
            </div>
        </section>

        <section class="hidden gap-10 md:grid lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="lg:sticky lg:top-28 lg:self-start">
                <div class="divide-y divide-slate-200 border-y border-slate-200">
                    <div
                        v-for="item in profileSummary"
                        :key="item.label"
                        class="py-4"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0d47a1]">
                            {{ item.label }}
                        </p>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-950">
                            {{ item.value }}
                        </p>
                    </div>
                </div>

                <nav
                    class="mt-8 hidden divide-y divide-slate-200 border-y border-slate-200 text-sm font-semibold text-slate-600 lg:block"
                    aria-label="Sekcje profilu"
                >
                    <a
                        v-for="section in profileSections"
                        :key="section.href"
                        :href="section.href"
                        class="block py-3 transition hover:text-[#0d47a1]"
                    >
                        {{ section.label }}
                    </a>
                </nav>
            </aside>

            <div class="space-y-14">
                <section v-if="!isMobile" id="zdjecie" class="scroll-mt-24">
                    <UpdateProfileAvatarForm />
                </section>

                <section v-if="!isMobile" id="dane-konta" class="scroll-mt-24">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :password-login-enabled="socialConnections.password_login_enabled"
                        :status="status"
                    />
                </section>

                <section v-if="!isMobile" class="border-y border-slate-200 py-6">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#0d47a1]">Moja opinia</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ reviewActionLabel }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Status: {{ reviewStatusLabel }}. Każda zmiana ponownie trafia do zatwierdzenia.</p>
                        </div>
                        <a
                            :href="review.edit_url"
                            class="inline-flex min-h-11 shrink-0 items-center justify-center border border-[#0d47a1] px-5 text-sm font-semibold text-[#0d47a1] transition hover:bg-[#0d47a1] hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0d47a1] focus-visible:ring-offset-2"
                        >
                            {{ reviewActionLabel }}
                        </a>
                    </div>
                </section>

                <section v-if="!isMobile && friendInvitations.enabled" id="zapros-znajomego" class="scroll-mt-24">
                    <FriendInvitationPanel
                        :invitations="friendInvitations"
                        :status="status"
                    />
                </section>

                <section v-if="!isMobile" id="haslo" class="scroll-mt-24">
                    <UpdatePasswordForm
                        :password-login-enabled="socialConnections.password_login_enabled"
                    />
                </section>

                <section v-if="!isMobile" id="social-login" class="scroll-mt-24">
                    <SocialConnectionsForm
                        :social-connections="socialConnections"
                        :status="status"
                    />
                </section>

                <section v-if="!isMobile" id="usun-konto" class="scroll-mt-24">
                    <DeleteUserForm
                        :password-login-enabled="socialConnections.password_login_enabled"
                    />
                </section>
            </div>
        </section>

        <Teleport to="body">
            <Transition name="profile-mobile-sheet">
                <dialog
                    v-if="openProfileSheet"
                    ref="profileDialog"
                    class="fixed inset-0 z-[70] m-0 h-full max-h-none w-full max-w-none border-0 bg-transparent p-0 backdrop:bg-transparent md:hidden"
                    aria-modal="true"
                    :aria-label="profileSheetTitle"
                    @cancel.prevent="closeProfileSheet"
                >
                    <button
                        type="button"
                        class="absolute inset-0 bg-[#0f172a]/35"
                        aria-label="Zamknij ustawienia"
                        @click="closeProfileSheet"
                    />
                    <section class="absolute inset-x-0 bottom-0 flex max-h-[92svh] flex-col overflow-hidden rounded-t-[1rem] bg-white shadow-[0_-22px_54px_rgba(15,23,42,0.24)]">
                        <div class="flex justify-center pt-2.5"><span class="h-1 w-12 rounded-full bg-[#d1d6e0]" aria-hidden="true" /></div>
                        <header class="flex min-h-14 items-center justify-between gap-3 border-b border-[#eaecf0] px-5 pb-2 pt-1.5">
                            <h2 class="min-w-0 text-[1.05rem] font-semibold text-[#101828]">{{ profileSheetTitle }}</h2>
                            <button
                                type="button"
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f2f4f7] text-[#475467] transition-colors hover:bg-[#eaecf0] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                                aria-label="Zamknij ustawienia"
                                @click="closeProfileSheet"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m7 7 10 10M17 7 7 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </button>
                        </header>
                        <div class="profile-mobile-sheet__content flex-1 overflow-y-auto px-5 pb-[max(env(safe-area-inset-bottom),1.25rem)] pt-5">
                            <UpdateProfileAvatarForm
                                v-if="openProfileSheet === 'avatar'"
                                mobile-sheet
                            />
                            <UpdateProfileInformationForm
                                v-else-if="openProfileSheet === 'account'"
                                :must-verify-email="mustVerifyEmail"
                                :password-login-enabled="socialConnections.password_login_enabled"
                                :status="status"
                                mobile-sheet
                            />
                            <FriendInvitationPanel
                                v-else-if="openProfileSheet === 'invitations' && friendInvitations.enabled"
                                :invitations="friendInvitations"
                                :status="status"
                                mobile-sheet
                            />
                            <UpdatePasswordForm
                                v-else-if="openProfileSheet === 'password'"
                                :password-login-enabled="socialConnections.password_login_enabled"
                                mobile-sheet
                            />
                            <SocialConnectionsForm
                                v-else-if="openProfileSheet === 'social'"
                                :social-connections="socialConnections"
                                :status="status"
                                mobile-sheet
                            />
                            <DeleteUserForm
                                v-else-if="openProfileSheet === 'delete'"
                                :password-login-enabled="socialConnections.password_login_enabled"
                                mobile-sheet
                            />
                        </div>
                    </section>
                </dialog>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.profile-mobile-hero::before,
.profile-mobile-hero::after {
    position: absolute;
    right: -2.5rem;
    width: 12rem;
    height: 7rem;
    border-radius: 50%;
    background: rgba(245, 239, 226, 0.46);
    content: '';
    pointer-events: none;
}

.profile-mobile-hero::before {
    top: -3.4rem;
    transform: rotate(-16deg);
}

.profile-mobile-hero::after {
    top: 2.6rem;
    right: -5rem;
    background: rgba(249, 246, 239, 0.65);
    transform: rotate(21deg);
}

.profile-mobile-stat {
    display: flex;
    min-width: 0;
    min-height: 4rem;
    align-items: center;
    gap: 0.45rem;
    padding: 0.5rem;
    border-radius: 1rem;
    background: #f8f6f1;
}

.profile-mobile-stat__icon {
    display: grid;
    width: 2.15rem;
    height: 2.15rem;
    flex: none;
    place-items: center;
    border-radius: 0.75rem;
    background: #f1eee7;
    color: #111827;
}

.profile-mobile-group {
    overflow: hidden;
    border-radius: 1.25rem;
    background: #ffffff;
    box-shadow: 0 12px 30px rgba(48, 38, 20, 0.045);
}

.profile-mobile-row {
    position: relative;
    display: flex;
    min-height: 3.75rem;
    width: 100%;
    align-items: center;
    gap: 0.8rem;
    padding: 0.55rem 0.9rem;
    text-align: left;
    transition: background-color 150ms ease;
}

.profile-mobile-row + .profile-mobile-row::before {
    position: absolute;
    top: 0;
    right: 0.9rem;
    left: 0.9rem;
    height: 1px;
    background: #eceef0;
    content: '';
}

.profile-mobile-row:hover {
    background: #f9fafb;
}

.profile-mobile-row:active {
    background: #f2f4f7;
}

.profile-mobile-row__icon {
    display: grid;
    height: 2.35rem;
    width: 2.35rem;
    flex: none;
    place-items: center;
    border-radius: 0.85rem;
    background: #f8f5ef;
    color: #101828;
}

.profile-mobile-row__icon--danger {
    background: #fff0ed;
    color: #ef472a;
}

.profile-mobile-row__trailing {
    display: flex;
    min-width: 1rem;
    flex: none;
    align-items: center;
    justify-content: flex-end;
    gap: 0.25rem;
    color: #626b7c;
}

@media (max-width: 390px) {
    .profile-mobile-stat {
        gap: 0.3rem;
        padding: 0.4rem;
    }

    .profile-mobile-stat__icon {
        width: 1.9rem;
        height: 1.9rem;
    }
}

.profile-mobile-sheet-enter-active,
.profile-mobile-sheet-leave-active {
    transition: opacity 180ms ease;
}

.profile-mobile-sheet-enter-active section,
.profile-mobile-sheet-leave-active section {
    transition: transform 220ms cubic-bezier(0.2, 0.8, 0.2, 1);
}

.profile-mobile-sheet-enter-from,
.profile-mobile-sheet-leave-to {
    opacity: 0;
}

.profile-mobile-sheet-enter-from section,
.profile-mobile-sheet-leave-to section {
    transform: translateY(2rem);
}

@media (prefers-reduced-motion: reduce) {
    .profile-mobile-row,
    .profile-mobile-sheet-enter-active,
    .profile-mobile-sheet-leave-active,
    .profile-mobile-sheet-enter-active section,
    .profile-mobile-sheet-leave-active section {
        transition: none !important;
    }
}
</style>
