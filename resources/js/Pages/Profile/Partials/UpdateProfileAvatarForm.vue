<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import type { PageProps } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        mobileSheet?: boolean;
    }>(),
    {
        mobileSheet: false,
    },
);

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user!);
const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const selectedFileName = ref('');

const uploadForm = useForm<{
    avatar: File | null;
}>({
    avatar: null,
});

const deleteForm = useForm({});
const busy = computed(() => uploadForm.processing || deleteForm.processing);

const avatarUrl = computed(() => previewUrl.value ?? user.value.avatar_url ?? null);
const initials = computed(() => user.value.avatar_initials ?? 'U');
const hasUploadedAvatar = computed(() => Boolean(user.value.has_uploaded_avatar));
const usesSocialFallback = computed(() => Boolean(user.value.avatar_url) && !hasUploadedAvatar.value);

const clearPreview = () => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
};

const resetSelectedFile = () => {
    clearPreview();
    uploadForm.reset('avatar');
    selectedFileName.value = '';

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const chooseAvatar = () => {
    fileInput.value?.click();
};

const onAvatarSelected = (event: Event) => {
    if (busy.value) return;
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (!file) {
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
        resetSelectedFile();
        uploadForm.setError('avatar', 'Wybierz zdjęcie JPG, PNG lub WEBP o rozmiarze do 2 MB.');
        return;
    }

    clearPreview();
    uploadForm.avatar = file;
    selectedFileName.value = file.name;
    previewUrl.value = URL.createObjectURL(file);
    uploadForm.clearErrors('avatar');
};

const submitAvatar = () => {
    if (!uploadForm.avatar || busy.value) {
        return;
    }

    uploadForm.post('/profile/avatar', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: resetSelectedFile,
    });
};

const removeAvatar = () => {
    if (busy.value) return;
    deleteForm.delete('/profile/avatar', {
        preserveScroll: true,
        onSuccess: resetSelectedFile,
    });
};

onBeforeUnmount(clearPreview);
</script>

<template>
    <section>
        <header v-if="!props.mobileSheet" class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#0d47a1]">
                Zdjęcie profilowe
            </p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-950">
                Avatar konta
            </h2>

            <p class="mt-3 text-sm leading-6 text-slate-600">
                Zdjęcie będzie widoczne w menu konta. Jeśli go nie ustawisz, użyjemy zdjęcia z Google albo Twoich inicjałów.
            </p>
        </header>

        <p v-else class="text-sm leading-6 text-[#667085]">
            Wybierz zdjęcie, które będzie widoczne na ekranie profilu. Możesz też pozostać przy zdjęciu z konta społecznościowego lub inicjałach.
        </p>

        <div :class="props.mobileSheet ? 'mt-5' : 'mt-8 divide-y divide-slate-200 border-y border-slate-200'">
            <div
                class="flex gap-4"
                :class="props.mobileSheet ? 'items-center rounded-lg bg-[#f7f8fa] p-4' : 'flex-col py-6 sm:flex-row sm:items-center'"
            >
                <div
                    class="grid shrink-0 place-items-center overflow-hidden rounded-full bg-white font-bold uppercase text-slate-950"
                    :class="props.mobileSheet ? 'h-[4.75rem] w-[4.75rem] text-xl ring-1 ring-[#d0d5dd]' : 'h-24 w-24 border-2 border-slate-950 text-2xl shadow-[0_18px_44px_rgba(15,23,42,0.08)]'"
                >
                    <img
                        v-if="avatarUrl"
                        :src="avatarUrl"
                        alt=""
                        class="h-full w-full object-cover"
                        referrerpolicy="no-referrer"
                    >
                    <span v-else>{{ initials }}</span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-[#101828]">
                        {{ uploadForm.avatar ? 'Nowe zdjęcie' : 'Aktualne zdjęcie' }}
                    </p>
                    <p v-if="uploadForm.avatar" class="mt-1 truncate text-xs leading-5 text-[#667085]">
                        {{ selectedFileName }}
                    </p>
                    <p v-else-if="hasUploadedAvatar" class="mt-1 text-sm leading-6 text-slate-600">
                        Korzystasz z własnego zdjęcia profilowego.
                    </p>
                    <p v-else-if="usesSocialFallback" class="mt-1 text-sm leading-6 text-slate-600">
                        Pokazujemy zdjęcie z podłączonego konta Google lub Facebook. Wybrane przez Ciebie zdjęcie je zastąpi.
                    </p>
                    <p v-else class="mt-1 text-sm leading-6 text-slate-600">
                        Nie masz jeszcze zdjęcia profilowego. W menu pokazujemy inicjały.
                    </p>

                    <p v-if="!props.mobileSheet" class="mt-2 text-xs font-medium leading-5 text-slate-500">
                        Akceptujemy JPG, PNG i WEBP do 2 MB. Zdjęcie pokażemy w okrągłej ramce.
                    </p>
                </div>
            </div>

            <form :class="props.mobileSheet ? 'pt-4' : 'py-6'" @submit.prevent="submitAvatar">
                <input
                    ref="fileInput"
                    type="file"
                    class="sr-only"
                    aria-label="Wybierz zdjęcie profilowe"
                    tabindex="-1"
                    :disabled="busy"
                    accept="image/jpeg,image/png,image/webp"
                    @change="onAvatarSelected"
                >

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <button
                        type="button"
                        class="inline-flex h-12 items-center justify-center rounded-lg border border-[#d0d5dd] bg-white px-6 text-sm font-semibold text-[#344054] transition hover:bg-[#f9fafb] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0b5cff] focus-visible:ring-offset-2"
                        :class="props.mobileSheet ? 'w-full' : 'sm:h-11 sm:w-auto sm:rounded-md'"
                        @click="chooseAvatar"
                        :disabled="busy"
                    >
                        {{ uploadForm.avatar ? 'Wybierz inne zdjęcie' : 'Wybierz zdjęcie' }}
                    </button>

                    <p v-if="selectedFileName && !props.mobileSheet" class="min-w-0 truncate text-sm font-medium text-slate-600">
                        {{ selectedFileName }}
                    </p>
                </div>

                <p v-if="props.mobileSheet" class="mt-2 text-xs leading-5 text-[#667085]">
                    JPG, PNG lub WEBP, maksymalnie 2 MB.
                </p>

                <InputError class="mt-3" :message="uploadForm.errors.avatar" />

                <div
                    v-if="!props.mobileSheet || uploadForm.avatar || uploadForm.recentlySuccessful"
                    class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center"
                >
                    <button
                        v-if="!props.mobileSheet || uploadForm.avatar"
                        type="submit"
                        class="inline-flex h-12 items-center justify-center rounded-lg bg-[#0b5cff] px-6 text-sm font-semibold text-white transition hover:bg-[#084fdc] disabled:cursor-not-allowed disabled:opacity-50"
                        :class="props.mobileSheet ? 'w-full' : 'sm:h-11 sm:w-auto sm:rounded-md sm:bg-[#0d47a1]'"
                        :disabled="!uploadForm.avatar || busy"
                    >
                        {{ uploadForm.processing ? 'Zapisywanie...' : 'Zapisz zdjęcie' }}
                    </button>

                    <button
                        v-if="uploadForm.avatar"
                        type="button"
                        class="inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-[#667085] transition hover:bg-[#f2f4f7] hover:text-[#101828]"
                        @click="resetSelectedFile"
                        :disabled="busy"
                    >
                        Anuluj wybór
                    </button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-if="uploadForm.recentlySuccessful"
                            class="text-center text-sm font-medium text-emerald-700"
                        >
                            Zdjęcie zostało zapisane.
                        </p>
                    </Transition>
                </div>
            </form>

            <div
                v-if="hasUploadedAvatar"
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                :class="props.mobileSheet ? 'mt-6 border-t border-[#eaecf0] pt-5' : 'py-6'"
            >
                <div>
                    <p class="text-sm font-semibold text-slate-950">
                        Usuń własne zdjęcie
                    </p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Po usunięciu wrócimy do zdjęcia z Google/Facebook albo do inicjałów.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex h-11 items-center justify-center rounded-lg border border-red-200 bg-white px-6 text-sm font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                    :class="props.mobileSheet ? 'w-full' : 'sm:w-auto sm:rounded-md'"
                    :disabled="busy"
                    @click="removeAvatar"
                >
                    {{ deleteForm.processing ? 'Usuwanie...' : 'Usuń zdjęcie' }}
                </button>
            </div>

            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
                <p
                    v-if="deleteForm.recentlySuccessful"
                    class="py-6 text-sm font-medium text-emerald-700"
                >
                    Zdjęcie zostało usunięte.
                </p>
            </Transition>
        </div>
    </section>
</template>
