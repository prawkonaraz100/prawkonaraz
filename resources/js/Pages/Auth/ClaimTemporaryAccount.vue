<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    technicalEmail: string;
    expiresAt: string | null;
}>();

const form = useForm({
    email: '',
    current_password: '',
    password: '',
    password_confirmation: '',
});

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('pl-PL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const submit = () => {
    form.put(route('account.claim.update'), {
        onFinish: () => form.reset('current_password', 'password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Przejmij konto" />

        <div class="mb-6 space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#7b735f]">
                Konto tymczasowe
            </p>
            <h1 class="text-2xl font-semibold tracking-tight text-[#1f1d18]">
                Przejmij konto i podaj swój e-mail
            </h1>
            <p class="text-sm leading-6 text-[#5f584b]">
                Moderator utworzył konto bez Twojego docelowego e-maila. Podaj prawdziwy adres i ustaw własne hasło.
            </p>
        </div>

        <div class="mb-5 rounded-2xl border border-[#ece5d8] bg-[#faf7f1] px-4 py-3 text-sm leading-6 text-[#5f584b]">
            Login techniczny: <strong class="text-[#1f1d18]">{{ props.technicalEmail }}</strong><br />
            Ważne do: <strong class="text-[#1f1d18]">{{ formatDate(props.expiresAt) }}</strong>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Twój e-mail" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="email"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="current_password" value="Hasło startowe" />
                <TextInput
                    id="current_password"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.current_password" />
            </div>

            <div>
                <InputLabel for="password" value="Nowe hasło" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Powtórz nowe hasło" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <PrimaryButton
                class="mt-2 w-full justify-center"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Przejmij konto
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
