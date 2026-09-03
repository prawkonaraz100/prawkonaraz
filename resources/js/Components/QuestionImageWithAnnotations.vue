<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { computeContainContentRect } from '@/utils/annotationContentRect';
import { isRenderableAnnotationType } from '@/utils/explanationAnnotations';
import { computeArrowPercentGeometry } from '@/utils/annotationArrowGeometry';

interface ExplanationAnnotation {
    id: number;
    annotation_type: string;
    x_percent: number;
    y_percent: number;
    width_percent: number | null;
    height_percent: number | null;
    arrow_length_percent: number | null;
    arrow_angle_degrees: number | null;
    arrow_stroke_percent: number | null;
    arrow_head_percent: number | null;
    label: string | null;
    tone: string | null;
}

type ClassValue = string | string[] | Record<string, boolean>;

const props = withDefaults(defineProps<{
    src: string;
    alt?: string;
    loading?: 'lazy' | 'eager';
    annotations?: ExplanationAnnotation[];
    naturalWidth?: number | null;
    naturalHeight?: number | null;
    presentationClass?: ClassValue;
    imageClass?: ClassValue;
    testId?: string | null;
}>(), {
    alt: '',
    loading: 'lazy',
    annotations: () => [],
    naturalWidth: null,
    naturalHeight: null,
    presentationClass: '',
    imageClass: '',
    testId: null,
});

const containerRef = ref<HTMLElement | null>(null);
const imageRef = ref<HTMLImageElement | null>(null);
const contentRect = ref({
    left: 0,
    top: 0,
    width: 0,
    height: 0,
});
const measuredNaturalWidth = ref<number | null>(props.naturalWidth);
const measuredNaturalHeight = ref<number | null>(props.naturalHeight);
let resizeObserver: ResizeObserver | null = null;

const renderableAnnotations = computed(() =>
    props.annotations.filter((annotation) =>
        Number.isFinite(annotation.x_percent)
        && Number.isFinite(annotation.y_percent),
    ).filter((annotation) =>
        isRenderableAnnotationType(annotation.annotation_type),
    ),
);

const toneClasses = (tone: string | null | undefined) => {
    switch (tone) {
        case 'warning':
            return {
                circle: 'border-[#d97706] bg-[#f59e0b]/12',
                dot: 'bg-[#d97706]',
                pill: 'bg-[#92400e] text-white',
                arrowStroke: 'stroke-[#d97706]',
                arrowFill: 'fill-[#d97706]',
                text: 'text-[#b45309]',
            };
        case 'danger':
            return {
                circle: 'border-[#dc2626] bg-[#ef4444]/10',
                dot: 'bg-[#dc2626]',
                pill: 'bg-[#991b1b] text-white',
                arrowStroke: 'stroke-[#dc2626]',
                arrowFill: 'fill-[#dc2626]',
                text: 'text-[#b91c1c]',
            };
        default:
            return {
                circle: 'border-[#2563eb] bg-[#3b82f6]/10',
                dot: 'bg-[#2563eb]',
                pill: 'bg-[#1d4ed8] text-white',
                arrowStroke: 'stroke-[#2563eb]',
                arrowFill: 'fill-[#2563eb]',
                text: 'text-[#1d4ed8]',
            };
    }
};

const updateContentRect = () => {
    const container = containerRef.value;
    const image = imageRef.value;
    const naturalWidth = measuredNaturalWidth.value;
    const naturalHeight = measuredNaturalHeight.value;

    if (!container || !image || !naturalWidth || !naturalHeight) {
        return;
    }

    const containerWidth = container.clientWidth;
    const containerHeight = container.clientHeight;

    if (containerWidth <= 0 || containerHeight <= 0) {
        return;
    }

    const computedStyle = typeof window === 'undefined'
        ? null
        : window.getComputedStyle(image);

    contentRect.value = computeContainContentRect({
        containerWidth,
        containerHeight,
        naturalWidth,
        naturalHeight,
        objectPosition: computedStyle?.objectPosition,
    });
};

const handleImageLoad = () => {
    const image = imageRef.value;

    if (!image) {
        return;
    }

    measuredNaturalWidth.value = image.naturalWidth || props.naturalWidth || image.clientWidth || null;
    measuredNaturalHeight.value = image.naturalHeight || props.naturalHeight || image.clientHeight || null;
    updateContentRect();
};

const absoluteX = (xPercent: number) =>
    contentRect.value.left + ((contentRect.value.width * xPercent) / 100);

const absoluteY = (yPercent: number) =>
    contentRect.value.top + ((contentRect.value.height * yPercent) / 100);

const circleStyle = (annotation: ExplanationAnnotation) => ({
    left: `${absoluteX(annotation.x_percent)}px`,
    top: `${absoluteY(annotation.y_percent)}px`,
    width: `${Math.max(((contentRect.value.width * (annotation.width_percent ?? 12)) / 100), 24)}px`,
    height: `${Math.max(((contentRect.value.height * (annotation.height_percent ?? 12)) / 100), 24)}px`,
    transform: 'translate(-50%, -50%)',
});

const labelAnchorStyle = (annotation: ExplanationAnnotation) => ({
    left: `${absoluteX(annotation.x_percent)}px`,
    top: `${absoluteY(annotation.y_percent)}px`,
});

const arrowGeometry = (annotation: ExplanationAnnotation) => {
    return computeArrowPercentGeometry(annotation);
};

const arrowStartX = (annotation: ExplanationAnnotation) =>
    arrowGeometry(annotation).startX;

const arrowStartY = (annotation: ExplanationAnnotation) =>
    arrowGeometry(annotation).startY;

const arrowEndX = (annotation: ExplanationAnnotation) =>
    arrowGeometry(annotation).endX;

const arrowEndY = (annotation: ExplanationAnnotation) =>
    arrowGeometry(annotation).endY;

const arrowStrokeWidth = (annotation: ExplanationAnnotation) =>
    arrowGeometry(annotation).strokeWidth;

const arrowHeadPoints = (annotation: ExplanationAnnotation) =>
    arrowGeometry(annotation).headPoints;

const contentRectStyle = computed(() => ({
    left: `${contentRect.value.left}px`,
    top: `${contentRect.value.top}px`,
    width: `${contentRect.value.width}px`,
    height: `${contentRect.value.height}px`,
}));

onMounted(() => {
    updateContentRect();

    if (typeof ResizeObserver !== 'undefined' && containerRef.value) {
        resizeObserver = new ResizeObserver(() => updateContentRect());
        resizeObserver.observe(containerRef.value);
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    resizeObserver = null;
});

watch(
    () => [props.naturalWidth, props.naturalHeight, props.src],
    () => {
        measuredNaturalWidth.value = props.naturalWidth;
        measuredNaturalHeight.value = props.naturalHeight;
        updateContentRect();
    },
);
</script>

<template>
    <div
        ref="containerRef"
        :data-testid="testId ?? undefined"
        class="relative"
        :class="presentationClass"
    >
        <img
            ref="imageRef"
            :src="src"
            :alt="alt"
            :loading="loading"
            decoding="async"
            :class="imageClass"
            @load="handleImageLoad"
        />

        <div
            v-if="renderableAnnotations.length > 0 && contentRect.width > 0 && contentRect.height > 0"
            class="pointer-events-none absolute inset-0 overflow-hidden"
        >
            <template
                v-for="annotation in renderableAnnotations"
                :key="annotation.id"
            >
                <div
                    v-if="annotation.annotation_type === 'circle'"
                    class="absolute rounded-full border-2 shadow-[0_0_0_1px_rgba(255,255,255,0.68)]"
                    :class="toneClasses(annotation.tone).circle"
                    :style="circleStyle(annotation)"
                />
                <svg
                    v-else-if="annotation.annotation_type === 'arrow'"
                    class="absolute overflow-visible"
                    :style="contentRectStyle"
                    viewBox="0 0 100 100"
                    preserveAspectRatio="none"
                    aria-hidden="true"
                >
                    <line
                        :x1="arrowStartX(annotation)"
                        :y1="arrowStartY(annotation)"
                        :x2="arrowEndX(annotation)"
                        :y2="arrowEndY(annotation)"
                        :stroke-width="arrowStrokeWidth(annotation)"
                        vector-effect="non-scaling-stroke"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :class="toneClasses(annotation.tone).arrowStroke"
                    />
                    <polygon
                        :points="arrowHeadPoints(annotation)"
                        :class="toneClasses(annotation.tone).arrowFill"
                    />
                </svg>

                <div
                    v-else-if="annotation.annotation_type === 'label'"
                    class="absolute"
                    :style="labelAnchorStyle(annotation)"
                >
                    <span
                        class="absolute h-3.5 w-3.5 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-white shadow-[0_4px_14px_rgba(15,23,42,0.18)]"
                        :class="toneClasses(annotation.tone).dot"
                    />
                    <span
                        v-if="annotation.label"
                        class="absolute left-3 top-[-0.55rem] max-w-[13rem] rounded-md px-2 py-1 text-[0.72rem] font-medium leading-5 shadow-[0_10px_24px_rgba(15,23,42,0.18)]"
                        :class="toneClasses(annotation.tone).pill"
                    >
                        {{ annotation.label }}
                    </span>
                </div>

                <div
                    v-else-if="annotation.annotation_type === 'text'"
                    class="absolute"
                    :style="labelAnchorStyle(annotation)"
                >
                    <span
                        v-if="annotation.label"
                        class="absolute left-0 top-0 -translate-x-1/2 -translate-y-1/2 whitespace-nowrap text-[1.05rem] font-bold uppercase leading-none"
                        :class="toneClasses(annotation.tone).text"
                    >
                        {{ annotation.label }}
                    </span>
                </div>
            </template>
        </div>
    </div>
</template>
