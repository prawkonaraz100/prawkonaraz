<script setup lang="ts">
interface ExplanationAsset {
    id: number;
    kind: string;
    title: string | null;
    body: string | null;
    caption: string | null;
    alt_text: string | null;
    image_url: string | null;
}

const props = defineProps<{
    asset: ExplanationAsset;
}>();
</script>

<template>
    <section
        v-if="asset.image_url"
        class="border border-[#e5e7eb] bg-white px-3 py-3 text-neutral-950"
    >
        <div class="border border-[#e5e7eb] bg-[#fafafa] px-2.5 py-2.5">
            <div class="flex min-h-[8rem] items-center justify-center bg-white px-2 py-2">
                <img
                    :src="asset.image_url"
                    :alt="asset.alt_text ?? asset.title ?? 'Materiał referencyjny'"
                    loading="lazy"
                    class="max-h-[10rem] w-full object-contain"
                />
            </div>
        </div>
    </section>
</template>
