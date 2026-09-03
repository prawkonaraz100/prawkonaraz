@php
    $imageUrl = is_array($image ?? null) ? ($image['full_url'] ?? $image['url'] ?? null) : null;
    $videoUrl = is_array($video ?? null) ? ($video['url'] ?? null) : null;
    $videoPosterUrl = is_array($video ?? null) ? ($video['poster_url'] ?? null) : null;
    $videoDurationSeconds = is_array($video ?? null) ? (int) ceil((float) ($video['duration_seconds'] ?? 0)) : 0;
    $savedAnnotations = is_array($savedAnnotations ?? null) ? $savedAnnotations : [];
    $annotationsPath = trim((string) $schemaComponent->getContainer()->getStatePath());
    $annotationsPath = $annotationsPath !== '' ? "{$annotationsPath}.explanation_annotations" : 'data.explanation_annotations';
@endphp

<style>
    .fi-grid-col:has([id="form.adnotacje-do-medium::data::section"]) {
        grid-column: 1 / -1 !important;
        width: 100% !important;
        max-width: 100% !important;
        --col-span-default: 1 / -1 !important;
    }

    .qe-editor {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .qe-editor__card {
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: #fff;
        padding: 1rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .qe-editor__hero {
        gap: 1rem;
    }

    .qe-editor__hero-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .qe-editor__eyebrow {
        margin: 0;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #64748b;
    }

    .qe-editor__hero-title {
        margin: 0.25rem 0 0;
        font-size: 1.125rem;
        font-weight: 700;
        line-height: 1.3;
        color: #0f172a;
    }

    .qe-editor__hero-text {
        margin: 0.5rem 0 0;
        max-width: 42rem;
        font-size: 0.875rem;
        line-height: 1.6;
        color: #475569;
    }

    .qe-editor__actions,
    .qe-editor__inline-actions,
    .qe-editor__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .qe-editor__layout {
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 1fr);
    }

    @media (min-width: 1700px) {
        .qe-editor__layout {
            grid-template-columns: minmax(0, 1fr) 24rem;
        }
    }

    .qe-editor__sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .qe-editor__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.875rem;
        background: #f8fafc;
        padding: 0.875rem;
    }

    .qe-editor__canvas-frame {
        position: relative;
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 0.875rem;
        background: #fff;
    }

    .qe-editor__overlay-layer {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .qe-editor__annotation-anchor {
        position: absolute;
    }

    .qe-editor__pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #fff;
        padding: 0.4rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #334155;
    }

    .qe-editor__muted {
        color: #94a3b8;
    }

    .qe-editor__section-title {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
    }

    .qe-editor__section-copy {
        margin: 0.35rem 0 0;
        font-size: 0.75rem;
        line-height: 1.6;
        color: #64748b;
    }

    .qe-editor__section-label {
        margin: 0;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #64748b;
    }

    .qe-editor__btn,
    .qe-editor__seg-btn,
    .qe-editor__item {
        border: 1px solid #d1d5db;
        border-radius: 0.75rem;
        background: #fff;
        color: #334155;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease, box-shadow .18s ease;
    }

    .qe-editor button[type="button"] {
        border: 1px solid #d1d5db;
        border-radius: 0.75rem;
        background: #fff;
        color: #334155;
        padding: 0.65rem 0.875rem;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.2;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease, box-shadow .18s ease;
    }

    .qe-editor__btn {
        padding: 0.625rem 0.875rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .qe-editor__btn--primary {
        border-color: #111827;
        background: #111827;
        color: #fff;
    }

    .qe-editor__seg-btn {
        padding: 0.65rem 0.875rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .qe-editor__item {
        width: 100%;
        padding: 0.875rem;
        text-align: left;
    }

    .qe-editor button[type="button"]:hover:not(:disabled) {
        border-color: #94a3b8;
        color: #111827;
    }

    .qe-editor button[type="button"]:disabled {
        cursor: not-allowed;
        opacity: 0.45;
    }

    .qe-editor__btn--danger {
        border-color: #fecaca;
        background: #fef2f2;
        color: #b91c1c;
    }

    .qe-editor__seg-btn--active,
    .qe-editor__item--active,
    .qe-editor__marker-index--active {
        border-color: #111827;
        background: #111827;
        color: #fff;
    }

    .qe-editor__status-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        padding: 0.3rem 0.625rem;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .qe-editor__status-badge--on {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .qe-editor__status-badge--off {
        border-color: #e5e7eb;
        background: #f8fafc;
        color: #64748b;
    }

    .qe-editor__marker-index {
        pointer-events: auto;
        position: absolute;
        z-index: 10;
        display: inline-flex;
        height: 1.6rem;
        width: 1.6rem;
        cursor: move;
        align-items: center;
        justify-content: center;
        border: 1px solid #fff;
        border-radius: 999px;
        background: rgba(71, 85, 105, 0.92);
        color: #fff;
        font-size: 0.6875rem;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
    }

    .qe-editor__resize-handle {
        pointer-events: auto;
        position: absolute;
        z-index: 10;
        display: inline-flex;
        height: 1.25rem;
        width: 1.25rem;
        cursor: nwse-resize;
        align-items: center;
        justify-content: center;
        border: 1px solid #fff;
        border-radius: 999px;
        background: #111827;
        color: #fff;
        font-size: 0.625rem;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
    }

    .qe-editor__overlay--muted {
        opacity: 0.4;
    }

    .qe-editor__overlay-outline {
        box-shadow: 0 0 0 2px rgba(17, 24, 39, 0.2);
    }

    .qe-editor__circle-svg {
        position: absolute;
        inset: 0;
        pointer-events: none;
        overflow: visible;
    }

    .qe-editor__circle-ellipse {
        stroke-width: 3px;
        vector-effect: non-scaling-stroke;
        filter: drop-shadow(0 0 0.45rem rgba(15, 23, 42, 0.16));
        transition: fill .18s ease, stroke .18s ease, filter .18s ease, stroke-width .18s ease;
    }

    .qe-editor__circle-ellipse--info {
        stroke: #2563eb;
        fill: rgba(59, 130, 246, 0.16);
    }

    .qe-editor__circle-ellipse--warning {
        stroke: #d97706;
        fill: rgba(245, 158, 11, 0.18);
    }

    .qe-editor__circle-ellipse--danger {
        stroke: #dc2626;
        fill: rgba(239, 68, 68, 0.16);
    }

    .qe-editor__circle-ellipse--selected {
        stroke-width: 4px;
        filter:
            drop-shadow(0 0 0.4rem rgba(255, 255, 255, 0.92))
            drop-shadow(0 0 0.7rem rgba(15, 23, 42, 0.22));
    }

    .qe-editor__arrow-line {
        vector-effect: non-scaling-stroke;
        filter: drop-shadow(0 0 0.38rem rgba(15, 23, 42, 0.18));
    }

    .qe-editor__arrow-head {
        filter: drop-shadow(0 0 0.38rem rgba(15, 23, 42, 0.18));
    }

    .qe-editor__arrow-line--info,
    .qe-editor__arrow-head--info {
        stroke: #2563eb;
        fill: #2563eb;
    }

    .qe-editor__arrow-line--warning,
    .qe-editor__arrow-head--warning {
        stroke: #d97706;
        fill: #d97706;
    }

    .qe-editor__arrow-line--danger,
    .qe-editor__arrow-head--danger {
        stroke: #dc2626;
        fill: #dc2626;
    }

    .qe-editor__dot {
        position: absolute;
        height: 0.9rem;
        width: 0.9rem;
        transform: translate(-50%, -50%);
        border-radius: 999px;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.16);
    }

    .qe-editor__dot--info { background: #2563eb; }
    .qe-editor__dot--warning { background: #d97706; }
    .qe-editor__dot--danger { background: #dc2626; }

    .qe-editor__label {
        position: absolute;
        left: 0.75rem;
        top: -0.55rem;
        max-width: 16rem;
        border-radius: 0.625rem;
        padding: 0.35rem 0.5rem;
        font-size: 0.74rem;
        font-weight: 700;
        line-height: 1.35;
        color: #fff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.16);
    }

    .qe-editor__label--info { background: #1d4ed8; }
    .qe-editor__label--warning { background: #b45309; }
    .qe-editor__label--danger { background: #991b1b; }

    .qe-editor__text-marker {
        position: absolute;
        left: 0;
        top: 0;
        transform: translate(-50%, -50%);
        white-space: nowrap;
        font-size: 1.05rem;
        line-height: 1;
        font-weight: 700;
        text-transform: uppercase;
    }

    .qe-editor__text-marker--info { color: #1d4ed8; }
    .qe-editor__text-marker--warning { color: #b45309; }
    .qe-editor__text-marker--danger { color: #b91c1c; }

    .qe-editor__tone-btn--info-active { border-color: #93c5fd; background: #eff6ff; color: #1d4ed8; }
    .qe-editor__tone-btn--warning-active { border-color: #fcd34d; background: #fffbeb; color: #b45309; }
    .qe-editor__tone-btn--danger-active { border-color: #fca5a5; background: #fef2f2; color: #b91c1c; }

    .qe-editor__note {
        border: 1px solid #dbeafe;
        border-radius: 0.875rem;
        background: #f8fbff;
        padding: 0.75rem 0.875rem;
        font-size: 0.75rem;
        line-height: 1.6;
        color: #475569;
    }

    .qe-editor__success {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #166534;
    }

    .qe-editor__summary {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .qe-editor__compact-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .qe-editor__count {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        background: #f8fafc;
        padding: 0.35rem 0.65rem;
        font-size: 0.6875rem;
        font-weight: 700;
        color: #475569;
    }

    .qe-editor__details {
        border-top: 1px solid #e5e7eb;
        padding-top: 1rem;
    }

    .qe-editor__details summary {
        cursor: pointer;
        list-style: none;
        font-size: 0.75rem;
        font-weight: 700;
        color: #334155;
    }

    .qe-editor__details summary::-webkit-details-marker {
        display: none;
    }

    .qe-editor__details-body {
        margin-top: 0.875rem;
    }

    .qe-editor .grid {
        display: grid;
        gap: 0.75rem;
    }

    .qe-editor .grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .qe-editor .grid-cols-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .qe-editor .grid-cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .qe-editor .space-y-1 > * + * {
        margin-top: 0.25rem;
    }

    .qe-editor .space-y-2 > * + * {
        margin-top: 0.5rem;
    }

    .qe-editor .space-y-3 > * + * {
        margin-top: 0.75rem;
    }

    .qe-editor .space-y-4 > * + * {
        margin-top: 1rem;
    }

    .qe-editor .mt-1 {
        margin-top: 0.25rem;
    }

    .qe-editor .mt-4 {
        margin-top: 1rem;
    }
</style>

<div x-data="annotationEditor({
    annotationsPath: {{ \Illuminate\Support\Js::from($annotationsPath) }},
    savedAnnotations: {{ \Illuminate\Support\Js::from($savedAnnotations) }},
    hasImage: {{ $imageUrl ? 'true' : 'false' }},
    hasVideo: {{ $videoUrl ? 'true' : 'false' }},
    videoDurationSeconds: {{ $videoDurationSeconds }}
})" x-init="boot()" class="qe-editor">
    <div class="qe-editor__card qe-editor__hero">
        <div class="qe-editor__hero-head">
            <div class="qe-editor__summary">
                <p class="qe-editor__eyebrow">Adnotacje do medium</p>
                <p class="qe-editor__hero-title">Dodaj marker i ustaw go na podglądzie</p>
                <p class="qe-editor__hero-text">Wybierz typ markera, kliknij w obraz albo kadr filmu i zapisz pytanie.</p>
            </div>

            <div class="qe-editor__actions">
                <button
                    type="button"
                    class="qe-editor__btn qe-editor__btn--primary"
                    x-on:click="addAnnotation('label')"
                >
                    Etykieta
                </button>
                <button
                    type="button"
                    class="qe-editor__btn"
                    x-on:click="addAnnotation('text')"
                >
                    Tekst
                </button>
                <button
                    type="button"
                    class="qe-editor__btn"
                    x-on:click="addAnnotation('circle')"
                >
                    Okrąg
                </button>
                <button
                    type="button"
                    class="qe-editor__btn"
                    x-on:click="addAnnotation('arrow')"
                >
                    Strzałka
                </button>
            </div>
        </div>
    </div>

    @if (! $imageUrl && ! $videoUrl)
        <div class="qe-editor__card qe-editor__note">
            Dodaj i zapisz medium pytania, aby włączyć wizualny helper adnotacji. Na ekranie tworzenia możesz już dodać rekordy formularzowo, ale klikalny edytor działa dopiero po zapisaniu pytania z obrazem albo filmem.
        </div>
    @else
        <div class="qe-editor__layout">
            <div class="qe-editor__card">
                <div class="qe-editor__meta">
                    <div>
                        <p class="qe-editor__section-label">Podgląd</p>
                        <p class="qe-editor__section-title">Kliknij w kadr, żeby ustawić marker</p>
                        <p class="qe-editor__section-copy">Przeciągnij numer markera, aby poprawić pozycję. Uchwyt „+” zmienia rozmiar okręgu i geometrię strzałki.</p>
                    </div>

                    <div class="qe-editor__chips">
                        <div class="qe-editor__pill">
                            <span class="qe-editor__muted">Podgląd</span>
                            <span x-text="activeCanvasKindLabel()"></span>
                        </div>

                        <template x-if="hasSelection()">
                            <div class="qe-editor__pill">
                            <span class="qe-editor__muted">Wybrany marker</span>
                            <span x-text="markerLabel(selectedAnnotation(), selectedIndex)"></span>
                            </div>
                        </template>

                        <div class="qe-editor__count">
                            <span x-text="annotations.length"></span>
                            <span x-text="annotations.length === 1 ? 'marker' : 'markery'"></span>
                        </div>
                    </div>
                </div>

                <div
                    class="qe-editor__canvas-frame"
                    x-on:mousedown.prevent="handleCanvasMouseDown($event)"
                    x-on:mousemove="handleCanvasMouseMove($event)"
                    x-on:mouseup="handleCanvasMouseUp()"
                    x-on:mouseleave="handleCanvasMouseUp()"
                    x-on:click="handleCanvasClick($event)"
                >
                    @if ($imageUrl)
                        <img
                            x-show="activeCanvasKind() === 'image'"
                            x-ref="editorImage"
                            src="{{ $imageUrl }}"
                            alt="Podgląd obrazu pytania"
                            class="block max-h-[30rem] w-auto max-w-full object-contain select-none"
                            draggable="false"
                        />
                    @endif

                    @if ($videoUrl)
                        <video
                            x-show="activeCanvasKind() === 'video'"
                            x-ref="editorVideo"
                            src="{{ $videoUrl }}"
                            poster="{{ $videoPosterUrl }}"
                            preload="metadata"
                            muted
                            playsinline
                            disablepictureinpicture
                            controlslist="nodownload noplaybackrate noremoteplayback nofullscreen"
                            class="block max-h-[30rem] w-auto max-w-full object-contain"
                            x-on:loadedmetadata="handleEditorVideoMetadata()"
                            x-on:seeked="handleEditorVideoSeeked()"
                        ></video>
                    @endif

                    <div class="qe-editor__overlay-layer">
                        <template x-for="item in canvasAnnotations()" :key="`${item.index}-${item.annotation.position ?? item.index}`">
                            <div x-bind:class="overlayOpacityClass(item.annotation)">
                                <svg
                                    class="qe-editor__circle-svg"
                                    x-bind:style="circleSvgStyle(item.annotation)"
                                    viewBox="0 0 100 100"
                                    preserveAspectRatio="none"
                                    aria-hidden="true"
                                >
                                    <ellipse
                                        class="qe-editor__circle-ellipse"
                                        x-bind:class="[toneCircleClass(item.annotation), selectedOutlineClass(item.index)]"
                                        x-bind:cx="annotationCircleCx(item.annotation)"
                                        x-bind:cy="annotationCircleCy(item.annotation)"
                                        x-bind:rx="annotationCircleRx(item.annotation)"
                                        x-bind:ry="annotationCircleRy(item.annotation)"
                                    ></ellipse>
                                </svg>

                                <svg
                                    class="qe-editor__circle-svg"
                                    x-bind:style="arrowSvgStyle(item.annotation)"
                                    viewBox="0 0 100 100"
                                    preserveAspectRatio="none"
                                    aria-hidden="true"
                                >
                                    <line
                                        class="qe-editor__arrow-line"
                                        x-bind:x1="arrowStartX(item.annotation)"
                                        x-bind:y1="arrowStartY(item.annotation)"
                                        x-bind:x2="arrowEndX(item.annotation)"
                                        x-bind:y2="arrowEndY(item.annotation)"
                                        x-bind:stroke-width="arrowStrokeWidth(item.annotation)"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        x-bind:class="toneArrowStrokeClass(item.annotation)"
                                    ></line>
                                    <polygon
                                        class="qe-editor__arrow-head"
                                        x-bind:points="arrowHeadPoints(item.annotation)"
                                        x-bind:class="toneArrowFillClass(item.annotation)"
                                    ></polygon>
                                </svg>

                                <div
                                    class="qe-editor__annotation-anchor"
                                    x-bind:style="labelMarkerStyle(item.annotation)"
                                >
                                    <span
                                        class="qe-editor__dot"
                                        x-bind:class="toneDotClass(item.annotation)"
                                    ></span>

                                    <span
                                        x-show="item.annotation.label"
                                        class="qe-editor__label"
                                        x-bind:class="tonePillClass(item.annotation)"
                                        x-text="item.annotation.label"
                                    ></span>
                                </div>

                                <div
                                    class="qe-editor__annotation-anchor"
                                    x-bind:style="textMarkerStyle(item.annotation)"
                                >
                                    <span
                                        x-show="item.annotation.label"
                                        class="qe-editor__text-marker"
                                        x-bind:class="toneTextClass(item.annotation)"
                                        x-text="item.annotation.label"
                                    ></span>
                                </div>

                                <button
                                    type="button"
                                    data-annotation-control
                                    class="qe-editor__marker-index"
                                    x-bind:class="item.index === selectedIndex ? 'qe-editor__marker-index--active' : ''"
                                    x-bind:style="markerIndexStyle(item.annotation)"
                                    x-on:mousedown.stop.prevent="startMoveSelected($event, item.index)"
                                    x-on:click.stop="selectAnnotation(item.index)"
                                    title="Przeciągnij, aby przesunąć marker"
                                >
                                    <span x-text="item.index + 1"></span>
                                </button>

                                <button
                                    type="button"
                                    data-annotation-control
                                    class="qe-editor__resize-handle"
                                    x-bind:style="resizeHandleButtonStyle(item.annotation, item.index)"
                                    x-on:mousedown.stop.prevent="startResizeSelected($event, item.index)"
                                    x-on:click.stop
                                    x-bind:title="resizeHandleTitle(item.annotation)"
                                >
                                    <span x-text="resizeHandleSymbol(item.annotation)"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="qe-editor__sidebar">
                <div class="qe-editor__card">
                    <div class="qe-editor__compact-row">
                        <div>
                            <p class="qe-editor__section-label">Wybrany marker</p>
                            <p class="qe-editor__section-title">Ustawienia markera</p>
                            <p class="qe-editor__section-copy">
                                Najpierw pracuj na podglądzie. Tutaj dopinasz tylko to, czego nie chcesz ustawiać myszką.
                            </p>
                        </div>

                        <template x-if="hasSelection()">
                            <span
                                class="qe-editor__status-badge"
                                x-bind:class="selectedAnnotation().is_active === false ? 'qe-editor__status-badge--off' : 'qe-editor__status-badge--on'"
                                x-text="selectedAnnotation().is_active === false ? 'Nieaktywna' : 'Aktywna'"
                            ></span>
                        </template>
                    </div>

                    <template x-if="!hasSelection()">
                        <div class="qe-editor__note" style="margin-top: 1rem;">
                            Dodaj marker przyciskami u góry albo wybierz istniejący z listy poniżej.
                        </div>
                    </template>

                    <template x-if="hasSelection()">
                        <div class="mt-4 space-y-4">
                            <div class="qe-editor__inline-actions">
                                <button
                                    type="button"
                                    class="qe-editor__btn"
                                    x-on:click="duplicateSelected()"
                                >
                                    Duplikuj marker
                                </button>
                                <button
                                    type="button"
                                    class="qe-editor__btn qe-editor__btn--danger"
                                    x-on:click="removeSelected()"
                                >
                                    Usuń marker
                                </button>
                                <button
                                    type="button"
                                    class="qe-editor__btn"
                                    x-on:click="toggleSelectedActive()"
                                    x-text="selectedAnnotation().is_active === false ? 'Włącz marker' : 'Ukryj marker'"
                                >
                                </button>
                            </div>

                            <div class="space-y-2">
                                <p class="qe-editor__section-label">Gdzie ma się pokazać</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="qe-editor__seg-btn"
                                        x-bind:class="selectedAnnotation().target_kind !== 'video_frame' ? 'qe-editor__seg-btn--active' : ''"
                                        x-bind:disabled="!hasImage"
                                        x-on:click="setSelectedTargetKind('question_image')"
                                    >
                                        Obraz pytania
                                    </button>
                                    <button
                                        type="button"
                                        class="qe-editor__seg-btn"
                                        x-bind:class="selectedAnnotation().target_kind === 'video_frame' ? 'qe-editor__seg-btn--active' : ''"
                                        x-bind:disabled="!hasVideo"
                                        x-on:click="setSelectedTargetKind('video_frame')"
                                    >
                                        Stopklatka filmu
                                    </button>
                                </div>
                            </div>

                            <template x-if="selectedAnnotation().target_kind === 'video_frame'">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                            <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-frame-time">
                                            Kadr filmu
                                        </label>
                                        <span class="text-xs font-medium text-gray-500" x-text="`${formatVideoTime(selectedAnnotation().frame_time_seconds ?? 0)} / ${formatVideoTime(videoTimelineMax())}`"></span>
                                    </div>
                                    <input
                                        id="annotation-editor-frame-time"
                                        type="range"
                                        min="0"
                                        step="1"
                                        class="h-2 w-full cursor-pointer appearance-none rounded-full bg-gray-200 accent-gray-900"
                                        x-bind:max="videoTimelineMax()"
                                        x-bind:value="selectedAnnotation().frame_time_seconds ?? 0"
                                        x-on:input="updateSelectedFrameTime($event.target.value)"
                                    />
                                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-frame-time-number">
                                        Sekunda kadru
                                    </label>
                                    <input
                                        id="annotation-editor-frame-time-number"
                                        type="number"
                                        min="0"
                                        step="1"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                        x-bind:max="videoTimelineMax()"
                                        x-bind:value="selectedAnnotation().frame_time_seconds ?? 0"
                                        x-on:input="updateSelectedFrameTime($event.target.value)"
                                    />
                                </div>
                            </template>

                            <div class="space-y-2">
                                <p class="qe-editor__section-label">Wygląd markera</p>
                                <div class="grid grid-cols-4 gap-2">
                                    <button
                                        type="button"
                                        class="qe-editor__seg-btn"
                                        x-bind:class="selectedAnnotation().annotation_type === 'label' ? 'qe-editor__seg-btn--active' : ''"
                                        x-on:click="setSelectedType('label')"
                                    >
                                        Etykieta
                                    </button>
                                    <button
                                        type="button"
                                        class="qe-editor__seg-btn"
                                        x-bind:class="selectedAnnotation().annotation_type === 'text' ? 'qe-editor__seg-btn--active' : ''"
                                        x-on:click="setSelectedType('text')"
                                    >
                                        Tekst
                                    </button>
                                    <button
                                        type="button"
                                        class="qe-editor__seg-btn"
                                        x-bind:class="selectedAnnotation().annotation_type === 'circle' ? 'qe-editor__seg-btn--active' : ''"
                                        x-on:click="setSelectedType('circle')"
                                    >
                                        Okrąg
                                    </button>
                                    <button
                                        type="button"
                                        class="qe-editor__seg-btn"
                                        x-bind:class="selectedAnnotation().annotation_type === 'arrow' ? 'qe-editor__seg-btn--active' : ''"
                                        x-on:click="setSelectedType('arrow')"
                                    >
                                        Strzałka
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <p class="qe-editor__section-label">Kolor</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        type="button"
                                        class="qe-editor__seg-btn"
                                        x-bind:class="toneChipClass('info', selectedAnnotation().tone)"
                                        x-on:click="setSelectedTone('info')"
                                    >
                                        Info
                                    </button>
                                    <button
                                        type="button"
                                        class="qe-editor__seg-btn"
                                        x-bind:class="toneChipClass('warning', selectedAnnotation().tone)"
                                        x-on:click="setSelectedTone('warning')"
                                    >
                                        Warning
                                    </button>
                                    <button
                                        type="button"
                                        class="qe-editor__seg-btn"
                                        x-bind:class="toneChipClass('danger', selectedAnnotation().tone)"
                                        x-on:click="setSelectedTone('danger')"
                                    >
                                        Danger
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-label">
                                        Tekst markera
                                    </label>
                                </div>
                                <input
                                    id="annotation-editor-label"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400"
                                    x-bind:value="selectedAnnotation().label ?? ''"
                                    x-bind:disabled="!['label', 'text'].includes(selectedAnnotation().annotation_type)"
                                    x-bind:placeholder="selectedAnnotation().annotation_type === 'label' ? 'Np. Ten znak jest ważny' : (selectedAnnotation().annotation_type === 'text' ? 'Np. LEWA STRONA' : 'To pole działa dla typu Etykieta/Tekst')"
                                    x-on:input="updateSelectedField('label', $event.target.value)"
                                />
                                <p class="text-xs leading-5 text-gray-500" x-show="!['label', 'text'].includes(selectedAnnotation().annotation_type)">
                                    To pole jest aktywne tylko dla typu Etykieta i Tekst.
                                </p>
                            </div>

                            <div class="space-y-2">
                                <p class="qe-editor__section-label">Warstwa</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:border-gray-300 hover:text-gray-900"
                                        x-on:click="moveSelectedOrder(-1)"
                                        x-bind:disabled="selectedIndex === 0"
                                    >
                                        Wyżej w kolejności
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:border-gray-300 hover:text-gray-900"
                                        x-on:click="moveSelectedOrder(1)"
                                        x-bind:disabled="selectedIndex === annotations.length - 1"
                                    >
                                        Niżej w kolejności
                                    </button>
                                </div>
                                <p class="qe-editor__note">
                                    Zapisz całe pytanie, aby utrwalić wszystkie zmiany markerów.
                                </p>
                            </div>

                            <details class="qe-editor__details">
                                <summary>Zaawansowane (opcjonalnie)</summary>
                                <div class="qe-editor__details-body space-y-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-2">
                                            <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-x">
                                                X %
                                            </label>
                                            <input
                                                id="annotation-editor-x"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                                x-bind:value="selectedAnnotation().x_percent ?? 0"
                                                x-on:input="updateSelectedNumber('x_percent', $event.target.value)"
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-y">
                                                Y %
                                            </label>
                                            <input
                                                id="annotation-editor-y"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                                x-bind:value="selectedAnnotation().y_percent ?? 0"
                                                x-on:input="updateSelectedNumber('y_percent', $event.target.value)"
                                            />
                                        </div>
                                    </div>

                                    <template x-if="selectedAnnotation().annotation_type === 'circle'">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-2">
                                                <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-width">
                                                    Szer. %
                                                </label>
                                                <input
                                                    id="annotation-editor-width"
                                                    type="number"
                                                    min="4"
                                                    max="100"
                                                    step="0.01"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                                    x-bind:value="selectedAnnotation().width_percent ?? 12"
                                                    x-on:input="updateSelectedNumber('width_percent', $event.target.value, { size: true })"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-height">
                                                    Wys. %
                                                </label>
                                                <input
                                                    id="annotation-editor-height"
                                                    type="number"
                                                    min="4"
                                                    max="100"
                                                    step="0.01"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                                    x-bind:value="selectedAnnotation().height_percent ?? 12"
                                                    x-on:input="updateSelectedNumber('height_percent', $event.target.value, { size: true })"
                                                />
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="selectedAnnotation().annotation_type === 'arrow'">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-2">
                                                <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-arrow-length">
                                                    Długość %
                                                </label>
                                                <input
                                                    id="annotation-editor-arrow-length"
                                                    type="number"
                                                    min="1"
                                                    max="100"
                                                    step="0.01"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                                    x-bind:value="selectedAnnotation().arrow_length_percent ?? 18"
                                                    x-on:input="updateSelectedArrowNumber('arrow_length_percent', $event.target.value)"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-arrow-angle">
                                                    Kąt °
                                                </label>
                                                <input
                                                    id="annotation-editor-arrow-angle"
                                                    type="number"
                                                    min="0"
                                                    max="359"
                                                    step="1"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                                    x-bind:value="selectedAnnotation().arrow_angle_degrees ?? 45"
                                                    x-on:input="updateSelectedArrowAngle($event.target.value)"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-arrow-stroke">
                                                    Grubość %
                                                </label>
                                                <input
                                                    id="annotation-editor-arrow-stroke"
                                                    type="number"
                                                    min="0.5"
                                                    max="8"
                                                    step="0.01"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                                    x-bind:value="selectedAnnotation().arrow_stroke_percent ?? 1.6"
                                                    x-on:input="updateSelectedArrowNumber('arrow_stroke_percent', $event.target.value)"
                                                />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500" for="annotation-editor-arrow-head">
                                                    Grot %
                                                </label>
                                                <input
                                                    id="annotation-editor-arrow-head"
                                                    type="number"
                                                    min="2"
                                                    max="30"
                                                    step="0.01"
                                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                                    x-bind:value="selectedAnnotation().arrow_head_percent ?? 3.5"
                                                    x-on:input="updateSelectedArrowNumber('arrow_head_percent', $event.target.value)"
                                                />
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </details>
                        </div>
                    </template>
                </div>
                <div class="qe-editor__card">
                    <div class="qe-editor__compact-row">
                        <div>
                            <p class="qe-editor__section-title">Lista markerów</p>
                            <p class="qe-editor__section-copy">Kliknij pozycję, aby ją od razu edytować.</p>
                        </div>

                        <div class="qe-editor__count">
                            <span x-text="annotations.length"></span>
                            <span x-text="annotations.length === 1 ? 'pozycja' : 'pozycje'"></span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <template x-if="!annotations.length">
                            <div class="qe-editor__note">
                                Nie ma jeszcze markerów. Dodaj pierwszy u góry, a potem wskaż jego miejsce na podglądzie.
                            </div>
                        </template>

                        <template x-for="(annotation, index) in annotations" :key="`annotation-list-${index}`">
                            <button
                                type="button"
                                class="qe-editor__item"
                                x-bind:class="index === selectedIndex ? 'qe-editor__item--active' : ''"
                                x-on:click="selectAnnotation(index)"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-[0.12em]" x-text="markerLabel(annotation, index)"></p>
                                        <p class="mt-1 text-xs opacity-80">
                                            <span x-text="annotation.target_kind === 'video_frame' ? `Stopklatka ${annotation.frame_time_seconds ?? 0}s` : 'Obraz pytania'"></span>
                                            ·
                                            X: <span x-text="annotation.x_percent ?? '-'"></span>% ·
                                            Y: <span x-text="annotation.y_percent ?? '-'"></span>%
                                            <span x-show="annotation.annotation_type === 'circle'">
                                                · W: <span x-text="annotation.width_percent ?? '-'"></span>%
                                                · H: <span x-text="annotation.height_percent ?? '-'"></span>%
                                            </span>
                                            <span x-show="annotation.annotation_type === 'arrow'">
                                                · Dł: <span x-text="annotation.arrow_length_percent ?? '-'"></span>%
                                                · Kąt: <span x-text="annotation.arrow_angle_degrees ?? '-'"></span>°
                                            </span>
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <span class="text-[11px] font-medium opacity-80" x-text="annotation.tone ?? 'info'"></span>
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.12em] opacity-70" x-text="annotation.is_active === false ? 'off' : 'on'"></span>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>

<script>
    function expandAnnotationSectionContainer() {
        const sectionRoot = document.getElementById('form.adnotacje-do-medium::data::section')
        const container = sectionRoot?.closest('.fi-grid-col')

        if (!container) {
            return
        }

        container.style.gridColumn = '1 / -1'
        container.style.width = '100%'
        container.style.maxWidth = '100%'
        container.style.setProperty('--col-span-default', '1 / -1')
    }

    function scheduleAnnotationSectionExpansion() {
        let attempts = 0
        const interval = window.setInterval(() => {
            expandAnnotationSectionContainer()
            attempts += 1

            const container = document.getElementById('form.adnotacje-do-medium::data::section')?.closest('.fi-grid-col')

            if (!container || container.getBoundingClientRect().width > 800 || attempts >= 12) {
                window.clearInterval(interval)
            }
        }, 180)
    }

    function normalizeAnnotationStatePath(path) {
        const normalizedPath = `${path ?? ''}`.trim()

        if (normalizedPath === '' || normalizedPath === 'explanation_annotations') {
            return 'data.explanation_annotations'
        }

        return normalizedPath
    }

    function annotationEditor(config) {
        return {
            annotationsPath: normalizeAnnotationStatePath(config.annotationsPath),
            savedAnnotations: Array.isArray(config.savedAnnotations) ? config.savedAnnotations : [],
            hasImage: Boolean(config.hasImage),
            hasVideo: Boolean(config.hasVideo),
            videoDurationSeconds: Number(config.videoDurationSeconds ?? 0),
            annotations: [],
            selectedIndex: 0,
            submitSyncBound: false,
            draggingCircle: false,
            dragCenter: null,
            pointerAction: null,
            dragStartPoint: null,
            dragStartAnnotation: null,
            suppressCanvasClick: false,
            boot() {
                this.expandContainer()
                this.annotations = this.$wire.entangle(this.annotationsPath).live
                this.seedFromSavedAnnotations()
                this.ensureSelection()
                this.$watch('annotations', () => {
                    this.ensureSelection()
                    this.$nextTick(() => this.syncVideoFramePreview())
                })
                this.$watch('selectedIndex', () => this.$nextTick(() => this.syncVideoFramePreview()))
                this.$nextTick(() => {
                    this.expandContainer()
                    this.syncVideoFramePreview()
                    this.bindFormSubmitSync()
                })
                window.requestAnimationFrame(() => this.expandContainer())
                window.setTimeout(() => this.expandContainer(), 120)
            },
            bindFormSubmitSync() {
                if (this.submitSyncBound) {
                    return
                }

                const rootElement = this.$root instanceof Element ? this.$root : null
                const form = rootElement?.closest('form')

                if (!form) {
                    return
                }

                form.addEventListener('submit', () => this.flushAnnotationsToWire(), true)
                this.submitSyncBound = true
            },
            flushAnnotationsToWire() {
                const snapshot = this.cloneAnnotations(this.annotations ?? [])
                this.annotations = this.renumberPositions(snapshot)

                if (this.$wire && typeof this.$wire.set === 'function') {
                    // `live = false` queues the value for the upcoming save request
                    // and avoids a race between background entangle sync and submit.
                    this.$wire.set(this.annotationsPath, this.annotations, false)
                }
            },
            expandContainer() {
                expandAnnotationSectionContainer()
            },
            ensureSelection() {
                if (!Array.isArray(this.annotations)) {
                    this.annotations = []
                }

                if (this.annotations.length === 0) {
                    this.selectedIndex = 0
                    return
                }

                if (this.selectedIndex < 0) {
                    this.selectedIndex = 0
                }

                if (this.selectedIndex > this.annotations.length - 1) {
                    this.selectedIndex = this.annotations.length - 1
                }
            },
            renumberPositions(items) {
                return items.map((annotation, index) => ({ ...annotation, position: index + 1 }))
            },
            cloneAnnotations(items) {
                if (!Array.isArray(items)) {
                    return []
                }

                return items.map((annotation, index) => ({
                    target_kind: annotation?.target_kind ?? 'question_image',
                    frame_time_seconds: annotation?.frame_time_seconds ?? null,
                    annotation_type: annotation?.annotation_type ?? 'label',
                    tone: annotation?.tone ?? 'info',
                    label: annotation?.label ?? null,
                    x_percent: Number(annotation?.x_percent ?? 50),
                    y_percent: Number(annotation?.y_percent ?? 50),
                    width_percent: annotation?.width_percent ?? null,
                    height_percent: annotation?.height_percent ?? null,
                    arrow_length_percent: annotation?.arrow_length_percent ?? null,
                    arrow_angle_degrees: annotation?.arrow_angle_degrees ?? null,
                    arrow_stroke_percent: annotation?.arrow_stroke_percent ?? null,
                    arrow_head_percent: annotation?.arrow_head_percent ?? null,
                    position: Number(annotation?.position ?? (index + 1)),
                    is_active: annotation?.is_active !== false,
                }))
            },
            defaultArrowLength() {
                return 18
            },
            defaultArrowAngle() {
                return 45
            },
            defaultArrowStroke() {
                return 1.6
            },
            defaultArrowHead() {
                return 3.5
            },
            seedFromSavedAnnotations() {
                if (!Array.isArray(this.annotations)) {
                    this.annotations = []
                }

                if (this.annotations.length > 0 || this.savedAnnotations.length === 0) {
                    return
                }

                const fallback = this.cloneAnnotations(this.savedAnnotations)

                if (typeof this.annotations.splice === 'function') {
                    this.annotations.splice(0, this.annotations.length, ...fallback)
                    return
                }

                this.annotations = fallback
            },
            selectedAnnotation() {
                if (!Array.isArray(this.annotations) || this.annotations.length === 0) {
                    return null
                }

                return this.annotations[this.selectedIndex] ?? null
            },
            hasSelection() {
                return this.selectedAnnotation() !== null
            },
            selectAnnotation(index) {
                this.selectedIndex = index
                this.$nextTick(() => this.syncVideoFramePreview())
            },
            activeCanvasKind() {
                const annotation = this.selectedAnnotation()

                if (annotation?.target_kind === 'video_frame' && this.hasVideo) {
                    return 'video'
                }

                if (this.hasImage) {
                    return 'image'
                }

                if (this.hasVideo) {
                    return 'video'
                }

                return 'image'
            },
            activeCanvasKindLabel() {
                return this.activeCanvasKind() === 'video' ? 'Stopklatka wideo' : 'Obraz pytania'
            },
            rawVideoFrameCeiling() {
                const frameValues = (this.annotations ?? [])
                    .filter((item) => item?.target_kind === 'video_frame')
                    .map((item) => {
                        const parsed = Number.parseInt(item?.frame_time_seconds ?? 0, 10)

                        return Number.isNaN(parsed) ? 0 : Math.max(parsed, 0)
                    })

                return Math.max(
                    Math.ceil(Number(this.videoDurationSeconds ?? 0)),
                    ...frameValues,
                    0,
                )
            },
            videoTimelineMax() {
                return this.rawVideoFrameCeiling()
            },
            formatVideoTime(value) {
                const totalSeconds = Math.max(Number.parseInt(value, 10) || 0, 0)
                const minutes = Math.floor(totalSeconds / 60)
                const seconds = totalSeconds % 60

                return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
            },
            clampVideoFrameTime(value) {
                const parsed = Number.parseInt(value, 10)
                const normalized = Number.isNaN(parsed) ? 0 : Math.max(parsed, 0)
                const max = this.rawVideoFrameCeiling()

                return max > 0 ? Math.min(normalized, max) : normalized
            },
            visibleVideoFrameSeconds() {
                const annotation = this.selectedAnnotation()

                if (annotation?.target_kind === 'video_frame') {
                    const parsed = Number.parseInt(annotation.frame_time_seconds ?? 0, 10)

                    return Number.isNaN(parsed) ? 0 : Math.max(parsed, 0)
                }

                const firstVideoFrame = (this.annotations ?? []).find((item) => item?.target_kind === 'video_frame')
                const parsed = Number.parseInt(firstVideoFrame?.frame_time_seconds ?? 0, 10)

                return Number.isNaN(parsed) ? 0 : Math.max(parsed, 0)
            },
            visibleVideoAnnotationsCount() {
                return this.canvasAnnotations().length
            },
            canvasAnnotations() {
                const activeCanvasKind = this.activeCanvasKind()
                const visibleFrameTime = this.visibleVideoFrameSeconds()

                return (this.annotations ?? [])
                    .map((annotation, index) => ({ annotation, index }))
                    .filter(({ annotation }) => {
                        const targetKind = annotation?.target_kind ?? 'question_image'

                        if (activeCanvasKind === 'video') {
                            return targetKind === 'video_frame'
                                && Number(annotation?.frame_time_seconds ?? 0) === visibleFrameTime
                        }

                        return targetKind !== 'video_frame'
                    })
            },
            currentCanvasElement() {
                return this.activeCanvasKind() === 'video'
                    ? this.$refs.editorVideo
                    : this.$refs.editorImage
            },
            currentCanvasMetrics() {
                const canvas = this.currentCanvasElement()

                if (!canvas || typeof canvas.getBoundingClientRect !== 'function') {
                    return {
                        left: 0,
                        top: 0,
                        width: 0,
                        height: 0,
                    }
                }

                const rect = canvas.getBoundingClientRect()

                return {
                    left: Number(canvas.offsetLeft ?? 0),
                    top: Number(canvas.offsetTop ?? 0),
                    width: Number(canvas.clientWidth || rect.width || 0),
                    height: Number(canvas.clientHeight || rect.height || 0),
                }
            },
            canvasOverlayStyle() {
                const metrics = this.currentCanvasMetrics()

                return `left:${metrics.left}px;top:${metrics.top}px;width:${metrics.width}px;height:${metrics.height}px;`
            },
            canvasAbsoluteX(percent) {
                const metrics = this.currentCanvasMetrics()

                return metrics.left + ((metrics.width * Number(percent ?? 0)) / 100)
            },
            canvasAbsoluteY(percent) {
                const metrics = this.currentCanvasMetrics()

                return metrics.top + ((metrics.height * Number(percent ?? 0)) / 100)
            },
            canvasAbsoluteWidth(percent, fallback = 12) {
                const metrics = this.currentCanvasMetrics()

                return Math.max(((metrics.width * Math.max(Number(percent ?? fallback), 4)) / 100), 24)
            },
            canvasAbsoluteHeight(percent, fallback = 12) {
                const metrics = this.currentCanvasMetrics()

                return Math.max(((metrics.height * Math.max(Number(percent ?? fallback), 4)) / 100), 24)
            },
            syncVideoFramePreview() {
                if (this.activeCanvasKind() !== 'video') {
                    return
                }

                const video = this.$refs.editorVideo

                if (!(video instanceof HTMLVideoElement) || Number.isNaN(video.duration) || video.duration <= 0) {
                    return
                }

                const desiredFrame = Math.max(this.visibleVideoFrameSeconds(), 0)
                const nextTime = Math.min(desiredFrame, Math.max(video.duration - 0.05, 0))

                try {
                    video.currentTime = nextTime
                } catch (_) {
                    // Browser can reject a seek while metadata is still stabilizing.
                }
            },
            handleEditorVideoMetadata() {
                const video = this.$refs.editorVideo

                if (video instanceof HTMLVideoElement && Number.isFinite(video.duration) && video.duration > 0) {
                    this.videoDurationSeconds = Math.ceil(video.duration)
                }

                this.syncVideoFramePreview()
            },
            handleEditorVideoSeeked() {
                const video = this.$refs.editorVideo

                if (video instanceof HTMLVideoElement) {
                    video.pause()
                }
            },
            addAnnotation(type = 'label') {
                const nextPosition = (this.annotations?.length ?? 0) + 1
                const targetKind = this.activeCanvasKind() === 'video' ? 'video_frame' : 'question_image'
                const isCircle = type === 'circle'
                const isArrow = type === 'arrow'
                const isText = type === 'text'
                const next = {
                    target_kind: targetKind,
                    frame_time_seconds: targetKind === 'video_frame' ? this.visibleVideoFrameSeconds() : null,
                    annotation_type: type,
                    tone: 'info',
                    label: type === 'label'
                        ? 'Nowa etykieta'
                        : (isText ? 'NOWY TEKST' : null),
                    x_percent: 50,
                    y_percent: 50,
                    width_percent: isCircle ? 12 : null,
                    height_percent: isCircle ? 12 : null,
                    arrow_length_percent: isArrow ? this.defaultArrowLength() : null,
                    arrow_angle_degrees: isArrow ? this.defaultArrowAngle() : null,
                    arrow_stroke_percent: isArrow ? this.defaultArrowStroke() : null,
                    arrow_head_percent: isArrow ? this.defaultArrowHead() : null,
                    position: nextPosition,
                    is_active: true,
                }

                this.annotations = [...(this.annotations ?? []), next]
                this.flushAnnotationsToWire()
                this.selectedIndex = this.annotations.length - 1
            },
            duplicateSelected() {
                const annotation = this.selectedAnnotation()

                if (!annotation) {
                    return
                }

                const duplicate = {
                    ...annotation,
                    label: annotation.annotation_type === 'label'
                        ? `${(annotation.label ?? 'Nowa etykieta').trim()} kopia`.trim()
                        : (annotation.annotation_type === 'text'
                            ? `${(annotation.label ?? 'NOWY TEKST').trim()} KOPIA`.trim()
                            : annotation.label),
                    x_percent: Number(Math.min(Math.max(Number(annotation.x_percent ?? 50) + 3, 0), 100).toFixed(2)),
                    y_percent: Number(Math.min(Math.max(Number(annotation.y_percent ?? 50) + 3, 0), 100).toFixed(2)),
                }

                const next = [...(this.annotations ?? [])]
                next.splice(this.selectedIndex + 1, 0, duplicate)

                this.annotations = this.renumberPositions(next)
                this.flushAnnotationsToWire()
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.annotations.length - 1)
            },
            removeSelected() {
                if (!Array.isArray(this.annotations) || this.annotations.length === 0) {
                    return
                }

                this.annotations = this.renumberPositions(
                    this.annotations.filter((_, index) => index !== this.selectedIndex)
                )

                this.flushAnnotationsToWire()
                this.ensureSelection()
            },
            moveSelectedOrder(delta) {
                const annotation = this.selectedAnnotation()
                const nextIndex = this.selectedIndex + delta

                if (!annotation || nextIndex < 0 || nextIndex >= this.annotations.length) {
                    return
                }

                const next = [...this.annotations]
                const [current] = next.splice(this.selectedIndex, 1)
                next.splice(nextIndex, 0, current)

                this.annotations = this.renumberPositions(next)
                this.flushAnnotationsToWire()
                this.selectedIndex = nextIndex
            },
            normalizePercent(value) {
                const parsed = Number.parseFloat(value)

                if (Number.isNaN(parsed)) {
                    return 0
                }

                return Number(Math.min(Math.max(parsed, 0), 100).toFixed(2))
            },
            normalizeSize(value, fallback = 12) {
                const parsed = Number.parseFloat(value)

                if (Number.isNaN(parsed)) {
                    return fallback
                }

                return Number(Math.min(Math.max(parsed, 4), 100).toFixed(2))
            },
            normalizeArrowLength(value, fallback = 18) {
                const parsed = Number.parseFloat(value)

                if (Number.isNaN(parsed)) {
                    return fallback
                }

                return Number(Math.min(Math.max(parsed, 1), 100).toFixed(2))
            },
            normalizeArrowStroke(value, fallback = 1.6) {
                const parsed = Number.parseFloat(value)

                if (Number.isNaN(parsed)) {
                    return fallback
                }

                return Number(Math.min(Math.max(parsed, 0.5), 8).toFixed(2))
            },
            normalizeArrowHead(value, fallback = 3.5) {
                const parsed = Number.parseFloat(value)

                if (Number.isNaN(parsed)) {
                    return fallback
                }

                return Number(Math.min(Math.max(parsed, 2), 30).toFixed(2))
            },
            normalizeArrowAngle(value, fallback = 45) {
                const parsed = Number.parseInt(value, 10)

                if (Number.isNaN(parsed)) {
                    return fallback
                }

                const normalized = parsed % 360

                return normalized >= 0 ? normalized : normalized + 360
            },
            commitSelected(annotation) {
                const next = [...(this.annotations ?? [])]
                next[this.selectedIndex] = { ...annotation }
                this.annotations = next
                this.flushAnnotationsToWire()
            },
            updateSelectedArrowNumber(field, value) {
                const annotation = this.selectedAnnotation()

                if (!annotation || annotation.annotation_type !== 'arrow') {
                    return
                }

                if (field === 'arrow_length_percent') {
                    annotation.arrow_length_percent = this.normalizeArrowLength(value, Number(annotation.arrow_length_percent ?? this.defaultArrowLength()))
                } else if (field === 'arrow_stroke_percent') {
                    annotation.arrow_stroke_percent = this.normalizeArrowStroke(value, Number(annotation.arrow_stroke_percent ?? this.defaultArrowStroke()))
                } else if (field === 'arrow_head_percent') {
                    annotation.arrow_head_percent = this.normalizeArrowHead(value, Number(annotation.arrow_head_percent ?? this.defaultArrowHead()))
                }

                this.commitSelected(annotation)
            },
            updateSelectedArrowAngle(value) {
                const annotation = this.selectedAnnotation()

                if (!annotation || annotation.annotation_type !== 'arrow') {
                    return
                }

                annotation.arrow_angle_degrees = this.normalizeArrowAngle(value, Number(annotation.arrow_angle_degrees ?? this.defaultArrowAngle()))
                this.commitSelected(annotation)
            },
            updateSelectedField(field, value) {
                const annotation = this.selectedAnnotation()

                if (!annotation) {
                    return
                }

                annotation[field] = value
                this.commitSelected(annotation)
            },
            updateSelectedFrameTime(value) {
                const annotation = this.selectedAnnotation()

                if (!annotation) {
                    return
                }

                annotation.frame_time_seconds = this.clampVideoFrameTime(value)
                this.commitSelected(annotation)
                this.$nextTick(() => this.syncVideoFramePreview())
            },
            updateSelectedNumber(field, value, { size = false, nullable = false } = {}) {
                const annotation = this.selectedAnnotation()

                if (!annotation) {
                    return
                }

                if (nullable && `${value}`.trim() === '') {
                    annotation[field] = null
                    this.commitSelected(annotation)
                    return
                }

                annotation[field] = size
                    ? this.normalizeSize(value, Number(annotation[field] ?? 12))
                    : this.normalizePercent(value)

                this.commitSelected(annotation)
            },
            setSelectedTargetKind(targetKind) {
                const annotation = this.selectedAnnotation()

                if (!annotation) {
                    return
                }

                if (targetKind === 'video_frame' && !this.hasVideo) {
                    return
                }

                if (targetKind === 'question_image' && !this.hasImage) {
                    return
                }

                annotation.target_kind = targetKind
                annotation.frame_time_seconds = targetKind === 'video_frame'
                    ? this.clampVideoFrameTime(annotation.frame_time_seconds ?? this.visibleVideoFrameSeconds() ?? 0)
                    : null

                this.commitSelected(annotation)
                this.$nextTick(() => this.syncVideoFramePreview())
            },
            setSelectedType(type) {
                const annotation = this.selectedAnnotation()

                if (!annotation) {
                    return
                }

                annotation.annotation_type = type

                if (type === 'circle') {
                    annotation.width_percent = this.normalizeSize(annotation.width_percent ?? 12)
                    annotation.height_percent = this.normalizeSize(annotation.height_percent ?? 12)
                    annotation.arrow_length_percent = null
                    annotation.arrow_angle_degrees = null
                    annotation.arrow_stroke_percent = null
                    annotation.arrow_head_percent = null
                    annotation.label = null
                } else if (type === 'arrow') {
                    annotation.width_percent = null
                    annotation.height_percent = null
                    annotation.arrow_length_percent = Number(this.normalizeArrowLength(annotation.arrow_length_percent ?? this.defaultArrowLength()))
                    annotation.arrow_angle_degrees = this.normalizeArrowAngle(annotation.arrow_angle_degrees ?? this.defaultArrowAngle())
                    annotation.arrow_stroke_percent = Number(this.normalizeArrowStroke(annotation.arrow_stroke_percent ?? this.defaultArrowStroke()))
                    annotation.arrow_head_percent = Number(this.normalizeArrowHead(annotation.arrow_head_percent ?? this.defaultArrowHead()))
                    annotation.label = null
                } else if (type === 'text') {
                    annotation.arrow_length_percent = null
                    annotation.arrow_angle_degrees = null
                    annotation.arrow_stroke_percent = null
                    annotation.arrow_head_percent = null
                    annotation.width_percent = null
                    annotation.height_percent = null
                    annotation.label = (annotation.label ?? '').trim() !== '' ? annotation.label : 'NOWY TEKST'
                } else {
                    annotation.arrow_length_percent = null
                    annotation.arrow_angle_degrees = null
                    annotation.arrow_stroke_percent = null
                    annotation.arrow_head_percent = null
                    annotation.width_percent = null
                    annotation.height_percent = null
                    annotation.label = (annotation.label ?? '').trim() !== '' ? annotation.label : 'Nowa etykieta'
                }

                this.commitSelected(annotation)
            },
            setSelectedTone(tone) {
                this.updateSelectedField('tone', tone)
            },
            toggleSelectedActive() {
                const annotation = this.selectedAnnotation()

                if (!annotation) {
                    return
                }

                annotation.is_active = !annotation.is_active
                this.commitSelected(annotation)
            },
            startMoveSelected(event, index) {
                const annotation = this.annotations[index] ?? null

                if (!annotation) {
                    return
                }

                this.selectAnnotation(index)
                this.pointerAction = 'move'
                this.dragStartPoint = this.canvasPoint(event)
                this.dragStartAnnotation = { ...annotation }
                this.draggingCircle = false
                this.dragCenter = null
            },
            startResizeSelected(event, index) {
                const annotation = this.annotations[index] ?? null

                if (!annotation || !['circle', 'arrow'].includes(annotation.annotation_type)) {
                    return
                }

                this.selectAnnotation(index)
                this.pointerAction = annotation.annotation_type === 'arrow' ? 'arrow-handle' : 'resize'
                this.dragStartPoint = this.canvasPoint(event)
                this.dragStartAnnotation = { ...annotation }
                this.draggingCircle = false
                this.dragCenter = null
            },
            canvasPoint(event) {
                const canvas = this.currentCanvasElement()

                if (!canvas || typeof canvas.getBoundingClientRect !== 'function') {
                    return { x: 0, y: 0 }
                }

                const rect = canvas.getBoundingClientRect()

                if (rect.width <= 0 || rect.height <= 0) {
                    return { x: 0, y: 0 }
                }

                const rawX = ((event.clientX - rect.left) / rect.width) * 100
                const rawY = ((event.clientY - rect.top) / rect.height) * 100

                return {
                    x: Math.min(Math.max(rawX, 0), 100),
                    y: Math.min(Math.max(rawY, 0), 100),
                }
            },
            handleCanvasMouseDown(event) {
                if (this.pointerAction) {
                    return
                }

                if (event.target.closest('[data-annotation-control]')) {
                    return
                }

                this.startCircleDrag(event)
            },
            handleCanvasMouseMove(event) {
                if (this.pointerAction === 'move' || this.pointerAction === 'resize' || this.pointerAction === 'arrow-handle') {
                    this.movePointerAction(event)
                    return
                }

                this.moveCircle(event)
            },
            handleCanvasMouseUp() {
                if (this.pointerAction) {
                    this.stopPointerAction()
                    return
                }

                this.stopCircleDrag()
            },
            handleCanvasClick(event) {
                if (this.suppressCanvasClick) {
                    this.suppressCanvasClick = false
                    return
                }

                if (event.target.closest('[data-annotation-control]')) {
                    return
                }

                this.placeSelected(event)
            },
            placeSelected(event) {
                const annotation = this.selectedAnnotation()

                if (!annotation) {
                    return
                }

                const point = this.canvasPoint(event)

                annotation.x_percent = Number(point.x.toFixed(2))
                annotation.y_percent = Number(point.y.toFixed(2))

                if (annotation.annotation_type === 'circle') {
                    annotation.width_percent = annotation.width_percent ?? 12
                    annotation.height_percent = annotation.height_percent ?? 12
                } else if (annotation.annotation_type === 'arrow') {
                    annotation.arrow_length_percent = this.normalizeArrowLength(annotation.arrow_length_percent ?? this.defaultArrowLength(), this.defaultArrowLength())
                    annotation.arrow_angle_degrees = this.normalizeArrowAngle(annotation.arrow_angle_degrees ?? this.defaultArrowAngle(), this.defaultArrowAngle())
                    annotation.arrow_stroke_percent = this.normalizeArrowStroke(annotation.arrow_stroke_percent ?? this.defaultArrowStroke(), this.defaultArrowStroke())
                    annotation.arrow_head_percent = this.normalizeArrowHead(annotation.arrow_head_percent ?? this.defaultArrowHead(), this.defaultArrowHead())
                }

                this.commitSelected(annotation)
            },
            movePointerAction(event) {
                const annotation = this.selectedAnnotation()

                if (!annotation || !this.dragStartAnnotation) {
                    return
                }

                const point = this.canvasPoint(event)

                if (this.pointerAction === 'move' && this.dragStartPoint) {
                    const deltaX = point.x - this.dragStartPoint.x
                    const deltaY = point.y - this.dragStartPoint.y

                    annotation.x_percent = Number(Math.min(Math.max(Number(this.dragStartAnnotation.x_percent ?? 0) + deltaX, 0), 100).toFixed(2))
                    annotation.y_percent = Number(Math.min(Math.max(Number(this.dragStartAnnotation.y_percent ?? 0) + deltaY, 0), 100).toFixed(2))
                    this.commitSelected(annotation)
                    return
                }

                if (this.pointerAction === 'resize') {
                    annotation.width_percent = Number(Math.min(Math.max(Math.abs(point.x - Number(this.dragStartAnnotation.x_percent ?? 50)) * 2, 4), 100).toFixed(2))
                    annotation.height_percent = Number(Math.min(Math.max(Math.abs(point.y - Number(this.dragStartAnnotation.y_percent ?? 50)) * 2, 4), 100).toFixed(2))
                    this.commitSelected(annotation)
                    return
                }

                if (this.pointerAction === 'arrow-handle') {
                    const originX = Number(annotation.x_percent ?? 50)
                    const originY = Number(annotation.y_percent ?? 50)
                    const deltaX = point.x - originX
                    const deltaY = point.y - originY
                    const length = Math.sqrt((deltaX * deltaX) + (deltaY * deltaY))

                    if (!Number.isFinite(length) || length <= 0.05) {
                        return
                    }

                    const angleDegrees = (Math.atan2(deltaY, deltaX) * 180) / Math.PI
                    annotation.arrow_length_percent = this.normalizeArrowLength(length, this.defaultArrowLength())
                    annotation.arrow_angle_degrees = this.normalizeArrowAngle(angleDegrees, this.defaultArrowAngle())
                    annotation.arrow_stroke_percent = this.normalizeArrowStroke(annotation.arrow_stroke_percent ?? this.defaultArrowStroke(), this.defaultArrowStroke())
                    annotation.arrow_head_percent = this.normalizeArrowHead(annotation.arrow_head_percent ?? this.defaultArrowHead(), this.defaultArrowHead())
                    this.commitSelected(annotation)
                }
            },
            startCircleDrag(event) {
                const annotation = this.selectedAnnotation()

                if (!annotation || annotation.annotation_type !== 'circle') {
                    this.placeSelected(event)
                    return
                }

                const point = this.canvasPoint(event)
                annotation.x_percent = Number(point.x.toFixed(2))
                annotation.y_percent = Number(point.y.toFixed(2))
                annotation.width_percent = annotation.width_percent ?? 12
                annotation.height_percent = annotation.height_percent ?? 12
                this.commitSelected(annotation)

                this.dragCenter = point
                this.draggingCircle = true
            },
            moveCircle(event) {
                if (!this.draggingCircle) {
                    return
                }

                const annotation = this.selectedAnnotation()

                if (!annotation || annotation.annotation_type !== 'circle' || !this.dragCenter) {
                    return
                }

                const point = this.canvasPoint(event)

                annotation.width_percent = Number(Math.min(Math.max(Math.abs(point.x - this.dragCenter.x) * 2, 4), 100).toFixed(2))
                annotation.height_percent = Number(Math.min(Math.max(Math.abs(point.y - this.dragCenter.y) * 2, 4), 100).toFixed(2))

                this.commitSelected(annotation)
            },
            stopCircleDrag() {
                this.draggingCircle = false
                this.dragCenter = null
            },
            stopPointerAction() {
                this.pointerAction = null
                this.dragStartPoint = null
                this.dragStartAnnotation = null
                this.draggingCircle = false
                this.dragCenter = null
                this.suppressCanvasClick = true
            },
            markerLabel(annotation, index) {
                const markerType = annotation.annotation_type ?? 'label'
                const type = markerType === 'circle'
                    ? 'Okrąg'
                    : (markerType === 'arrow' ? 'Strzałka' : (markerType === 'text' ? 'Tekst' : 'Etykieta'))
                const label = (annotation.label ?? '').trim()

                return label !== '' ? `${type} · ${label}` : `${type} ${index + 1}`
            },
            markerStyle(annotation) {
                const left = this.canvasAbsoluteX(annotation.x_percent ?? 0)
                const top = this.canvasAbsoluteY(annotation.y_percent ?? 0)

                return `left:${left}px;top:${top}px;`
            },
            labelMarkerStyle(annotation) {
                if (
                    annotation?.annotation_type === 'circle'
                    || annotation?.annotation_type === 'arrow'
                    || annotation?.annotation_type === 'text'
                ) {
                    return 'display:none;'
                }

                return `display:block;${this.markerStyle(annotation)}`
            },
            textMarkerStyle(annotation) {
                if (annotation?.annotation_type !== 'text') {
                    return 'display:none;'
                }

                return `display:block;${this.markerStyle(annotation)}`
            },
            annotationCircleCx(annotation) {
                return this.normalizePercent(annotation?.x_percent ?? 50)
            },
            annotationCircleCy(annotation) {
                return this.normalizePercent(annotation?.y_percent ?? 50)
            },
            annotationCircleRx(annotation) {
                return Math.max(Number(annotation?.width_percent ?? 12), 4) / 2
            },
            annotationCircleRy(annotation) {
                return Math.max(Number(annotation?.height_percent ?? 12), 4) / 2
            },
            circleSvgStyle(annotation) {
                if (annotation?.annotation_type !== 'circle') {
                    return 'display:none;'
                }

                return `display:block;${this.canvasOverlayStyle()}`
            },
            arrowStartX(annotation) {
                return this.normalizePercent(annotation?.x_percent ?? 50)
            },
            arrowStartY(annotation) {
                return this.normalizePercent(annotation?.y_percent ?? 50)
            },
            arrowLength(annotation) {
                return this.normalizeArrowLength(annotation?.arrow_length_percent ?? this.defaultArrowLength(), this.defaultArrowLength())
            },
            arrowAngle(annotation) {
                return this.normalizeArrowAngle(annotation?.arrow_angle_degrees ?? this.defaultArrowAngle(), this.defaultArrowAngle())
            },
            arrowRadians(annotation) {
                return (this.arrowAngle(annotation) * Math.PI) / 180
            },
            arrowEndX(annotation) {
                const start = this.arrowStartX(annotation)
                const length = this.arrowLength(annotation)

                return this.normalizePercent(start + (Math.cos(this.arrowRadians(annotation)) * length))
            },
            arrowEndY(annotation) {
                const start = this.arrowStartY(annotation)
                const length = this.arrowLength(annotation)

                return this.normalizePercent(start + (Math.sin(this.arrowRadians(annotation)) * length))
            },
            arrowStrokeWidth(annotation) {
                return this.normalizeArrowStroke(annotation?.arrow_stroke_percent ?? this.defaultArrowStroke(), this.defaultArrowStroke())
            },
            arrowHeadLength(annotation) {
                return this.normalizeArrowHead(annotation?.arrow_head_percent ?? this.defaultArrowHead(), this.defaultArrowHead())
            },
            arrowHeadPoints(annotation) {
                const radians = this.arrowRadians(annotation)
                const endX = this.arrowEndX(annotation)
                const endY = this.arrowEndY(annotation)
                const headLength = this.arrowHeadLength(annotation)
                const baseX = endX - (Math.cos(radians) * headLength)
                const baseY = endY - (Math.sin(radians) * headLength)
                const wing = headLength * 0.58
                const perpX = -Math.sin(radians)
                const perpY = Math.cos(radians)
                const leftX = this.normalizePercent(baseX + (perpX * wing))
                const leftY = this.normalizePercent(baseY + (perpY * wing))
                const rightX = this.normalizePercent(baseX - (perpX * wing))
                const rightY = this.normalizePercent(baseY - (perpY * wing))

                return `${endX},${endY} ${leftX},${leftY} ${rightX},${rightY}`
            },
            arrowSvgStyle(annotation) {
                if (annotation?.annotation_type !== 'arrow') {
                    return 'display:none;'
                }

                return `display:block;${this.canvasOverlayStyle()}`
            },
            markerIndexStyle(annotation) {
                const centerX = this.canvasAbsoluteX(annotation?.x_percent ?? 0)
                const centerY = this.canvasAbsoluteY(annotation?.y_percent ?? 0)

                if (annotation?.annotation_type === 'circle') {
                    const width = this.canvasAbsoluteWidth(annotation.width_percent ?? 12) / 2
                    const height = this.canvasAbsoluteHeight(annotation.height_percent ?? 12) / 2

                    return `left:${centerX - width - 6}px;top:${centerY - height - 6}px;transform:translate(-50%, -50%);`
                }

                return `left:${centerX}px;top:${centerY}px;transform:translate(-50%, -50%);`
            },
            resizeHandleStyle(annotation) {
                if (annotation?.annotation_type === 'arrow') {
                    const endX = this.canvasAbsoluteX(this.arrowEndX(annotation))
                    const endY = this.canvasAbsoluteY(this.arrowEndY(annotation))

                    return `left:${endX}px;top:${endY}px;transform:translate(-50%, -50%);cursor:crosshair;`
                }

                const centerX = this.canvasAbsoluteX(annotation.x_percent ?? 0)
                const centerY = this.canvasAbsoluteY(annotation.y_percent ?? 0)
                const width = this.canvasAbsoluteWidth(annotation.width_percent ?? 12) / 2
                const height = this.canvasAbsoluteHeight(annotation.height_percent ?? 12) / 2

                return `left:${centerX + width + 6}px;top:${centerY + height + 6}px;transform:translate(-50%, -50%);`
            },
            resizeHandleButtonStyle(annotation, index) {
                if (!['circle', 'arrow'].includes(annotation?.annotation_type) || index !== this.selectedIndex) {
                    return 'display:none;'
                }

                return `display:inline-flex;${this.resizeHandleStyle(annotation)}`
            },
            resizeHandleTitle(annotation) {
                return annotation?.annotation_type === 'arrow'
                    ? 'Przeciągnij uchwyt, aby ustawić kierunek i długość strzałki'
                    : 'Przeciągnij uchwyt, aby zmienić rozmiar'
            },
            resizeHandleSymbol(annotation) {
                return annotation?.annotation_type === 'arrow' ? '↗' : '+'
            },
            toneCircleClass(annotation) {
                switch (annotation.tone) {
                    case 'warning':
                        return 'qe-editor__circle-ellipse--warning'
                    case 'danger':
                        return 'qe-editor__circle-ellipse--danger'
                    default:
                        return 'qe-editor__circle-ellipse--info'
                }
            },
            toneDotClass(annotation) {
                switch (annotation.tone) {
                    case 'warning':
                        return 'qe-editor__dot--warning'
                    case 'danger':
                        return 'qe-editor__dot--danger'
                    default:
                        return 'qe-editor__dot--info'
                }
            },
            tonePillClass(annotation) {
                switch (annotation.tone) {
                    case 'warning':
                        return 'qe-editor__label--warning'
                    case 'danger':
                        return 'qe-editor__label--danger'
                    default:
                        return 'qe-editor__label--info'
                }
            },
            toneTextClass(annotation) {
                switch (annotation.tone) {
                    case 'warning':
                        return 'qe-editor__text-marker--warning'
                    case 'danger':
                        return 'qe-editor__text-marker--danger'
                    default:
                        return 'qe-editor__text-marker--info'
                }
            },
            toneArrowStrokeClass(annotation) {
                switch (annotation.tone) {
                    case 'warning':
                        return 'qe-editor__arrow-line--warning'
                    case 'danger':
                        return 'qe-editor__arrow-line--danger'
                    default:
                        return 'qe-editor__arrow-line--info'
                }
            },
            toneArrowFillClass(annotation) {
                switch (annotation.tone) {
                    case 'warning':
                        return 'qe-editor__arrow-head--warning'
                    case 'danger':
                        return 'qe-editor__arrow-head--danger'
                    default:
                        return 'qe-editor__arrow-head--info'
                }
            },
            toneChipClass(tone, currentTone) {
                if (tone !== currentTone) {
                    return ''
                }

                switch (tone) {
                    case 'warning':
                        return 'qe-editor__tone-btn--warning-active'
                    case 'danger':
                        return 'qe-editor__tone-btn--danger-active'
                    default:
                        return 'qe-editor__tone-btn--info-active'
                }
            },
            overlayOpacityClass(annotation) {
                return annotation.is_active === false ? 'qe-editor__overlay--muted' : ''
            },
            selectedOutlineClass(index) {
                return index === this.selectedIndex ? 'qe-editor__circle-ellipse--selected' : ''
            },
        }
    }

    window.requestAnimationFrame(() => expandAnnotationSectionContainer())
    window.setTimeout(() => expandAnnotationSectionContainer(), 180)
    window.addEventListener('load', scheduleAnnotationSectionExpansion, { once: true })
    document.addEventListener('livewire:navigated', scheduleAnnotationSectionExpansion)
</script>
