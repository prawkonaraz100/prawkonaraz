<script setup lang="ts">
import {
    injectExplanationSignImages,
    renderExplanationHtml,
} from '@/utils/explanationFormatting';
import type {
    ExplanationSignReference,
    InlineFormattingPalette,
} from '@/utils/explanationFormatting';
import { computed } from 'vue';

interface ExplanationAsset {
    id: number;
    kind: string;
    title: string | null;
    body: string | null;
    caption: string | null;
    alt_text: string | null;
    image_url: string | null;
}

const props = withDefaults(defineProps<{
    explanationHtml?: string | null;
    fallbackText?: string | null;
    asset?: ExplanationAsset | null;
    signReferences?: ExplanationSignReference[];
    showImage?: boolean;
    showSignReferences?: boolean;
    emptyMessage?: string;
    palette?: InlineFormattingPalette;
    enableBoldFormatting?: boolean;
    enableColorFormatting?: boolean;
}>(), {
    explanationHtml: null,
    fallbackText: null,
    asset: null,
    signReferences: () => [],
    showImage: true,
    showSignReferences: true,
    emptyMessage: 'Do tego pytania nie mamy jeszcze gotowego wyjaśnienia. Warto wrócić do niego od razu po tej sesji.',
    palette: 'classic',
    enableBoldFormatting: true,
    enableColorFormatting: true,
});

const fallbackImageUrl = '/images/session/explanation-fallback.svg';

const resolvedExplanationHtml = computed(() => {
    const primary = props.explanationHtml?.trim();

    if (primary) {
        return primary;
    }

    const fallback = props.fallbackText?.trim();

    if (fallback) {
        return renderExplanationHtml(fallback, {
            palette: props.palette,
            enableBold: props.enableBoldFormatting,
            enableColors: props.enableColorFormatting,
        });
    }

    return '';
});

const renderedExplanationHtml = computed(() => {
    if (!props.showSignReferences) {
        return resolvedExplanationHtml.value;
    }

    return injectExplanationSignImages(
        resolvedExplanationHtml.value,
        props.signReferences,
    );
});

const imageUrl = computed(() => {
    if (!props.showImage) {
        return null;
    }

    return props.asset?.image_url ?? fallbackImageUrl;
});

const imageAlt = computed(() =>
    props.asset?.alt_text
    ?? props.asset?.title
    ?? 'Grafika pomocnicza do wyjaśnienia'
);
</script>

<template>
    <section class="text-neutral-950">
        <div
            class="gap-3"
            :class="imageUrl ? 'grid grid-cols-[6.5rem_minmax(0,1fr)] items-start sm:grid-cols-[8.25rem_minmax(0,1fr)] sm:items-center' : 'block'"
        >
            <div class="order-2 min-w-0">
                <div
                    v-if="renderedExplanationHtml"
                    class="text-[0.98rem] leading-7 text-[#374151] [&_p:not(:first-child)]:mt-3 [&_strong]:font-semibold [&_strong]:text-inherit"
                    v-html="renderedExplanationHtml"
                />

                <p
                    v-else
                    class="text-[0.98rem] leading-7 text-[#64748b]"
                >
                    {{ emptyMessage }}
                </p>
            </div>

            <div
                v-if="imageUrl"
                class="order-1"
            >
                <div class="flex min-h-[5.5rem] items-center justify-center bg-white sm:min-h-[6.75rem]">
                    <img
                        :src="imageUrl"
                        :alt="imageAlt"
                        loading="lazy"
                        class="max-h-[5.75rem] w-full object-contain sm:max-h-[8rem]"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
:deep(.explanation-inline-sign) {
    display: inline-block;
    width: 1.55em;
    height: 1.55em;
    margin: 0 0.16em;
    object-fit: contain;
    vertical-align: -0.42em;
}
</style>
