@php
    $config = $this->getFrontendConfig();
@endphp

<x-filament-panels::page>
    <div
        x-data="{
            config: @js($config),
            kind: 'image',
            variant: 'full',
            file: null,
            posterFile: null,
            width: '',
            height: '',
            durationSeconds: '',
            sortOrder: 0,
            metadataText: '',
            busy: false,
            error: null,
            success: null,
            init() {
                this.syncVariant()
                this.sortOrder = this.existingMedia.length
            },
            get question() { return this.config.question },
            get existingMedia() {
                return [...(this.config.existingMedia ?? [])].sort((a, b) => {
                    if ((a.sortOrder ?? 0) === (b.sortOrder ?? 0)) return (a.id ?? 0) - (b.id ?? 0)
                    return (a.sortOrder ?? 0) - (b.sortOrder ?? 0)
                })
            },
            get allowedMimeTypes() { return this.config.allowedMimeTypes ?? {} },
            get allowedVariants() { return this.config.allowedVariants ?? {} },
            get maxBytes() { return this.config.maxBytes ?? {} },
            get variantOptions() { return this.allowedVariants[this.kind] ?? [] },
            get acceptAttribute() { return (this.allowedMimeTypes[this.kind] ?? []).join(',') },
            get posterAcceptAttribute() { return (this.allowedMimeTypes.image ?? []).join(',') },
            get maxBytesLabel() { return this.formatBytes(this.maxBytes[this.kind] ?? 0) },
            get hasPrimaryMedia() { return this.existingMedia.some((item) => item.variant === 'full') },
            get imageCount() { return this.existingMedia.filter((item) => item.kind === 'image').length },
            get videoCount() { return this.existingMedia.filter((item) => item.kind === 'video').length },
            get primaryState() {
                if (!this.question.requiresPrimaryMedia) return 'Nie wymagane'
                return this.hasPrimaryMedia ? 'Uzupełnione' : 'Brakuje'
            },
            get primaryHint() {
                if (!this.question.requiresPrimaryMedia) return 'To pytanie może działać bez głównego medium.'
                return this.hasPrimaryMedia ? 'Główne medium jest już podpięte.' : 'To pytanie czeka na wariant główny.'
            },
            setKind(kind) {
                this.kind = kind
                if (kind !== 'video') {
                    this.posterFile = null
                    this.durationSeconds = ''
                }
                this.syncVariant()
            },
            syncVariant() {
                if (!this.variantOptions.includes(this.variant)) this.variant = this.variantOptions[0] ?? 'full'
            },
            variantLabel(variant) {
                return ({ full: 'Główne', thumb: 'Miniatura', poster: 'Poster' })[variant] ?? variant
            },
            kindLabel(kind) {
                return ({ image: 'Obraz', video: 'Wideo' })[kind] ?? kind
            },
            uploadHint() {
                return this.kind === 'video'
                    ? 'Wideo dodajesz jako wariant główny. Poster możesz dołączyć od razu.'
                    : 'Dla obrazu możesz dodać wariant główny, miniaturę albo poster.'
            },
            onFileChange(event) { this.file = event.target.files?.[0] ?? null },
            onPosterFileChange(event) { this.posterFile = event.target.files?.[0] ?? null },
            async submit() {
                this.error = null
                this.success = null
                if (!this.file) {
                    this.error = 'Wybierz plik do uploadu.'
                    return
                }
                this.syncVariant()
                if ((this.maxBytes[this.kind] ?? 0) > 0 && this.file.size > this.maxBytes[this.kind]) {
                    this.error = `Plik przekracza limit dla typu ${this.kind}.`
                    return
                }
                if (this.kind === 'video' && this.posterFile && (this.maxBytes.image ?? 0) > 0 && this.posterFile.size > this.maxBytes.image) {
                    this.error = 'Poster przekracza limit dla obrazów.'
                    return
                }
                this.busy = true
                try {
                    const upload = await this.presign(this.file, this.kind, this.variant)
                    await this.uploadFile(upload, this.file)
                    let posterUpload = null
                    if (this.kind === 'video' && this.posterFile) {
                        posterUpload = await this.presign(this.posterFile, 'image', 'poster')
                        await this.uploadFile(posterUpload, this.posterFile)
                    }
                    const payload = {
                        upload_token: upload.upload_token,
                        sort_order: this.sortOrder === '' ? this.existingMedia.length : Number(this.sortOrder),
                    }
                    if (posterUpload) payload.poster_upload_token = posterUpload.upload_token
                    if (this.width !== '') payload.width = Number(this.width)
                    if (this.height !== '') payload.height = Number(this.height)
                    if (this.durationSeconds !== '') payload.duration_seconds = Number(this.durationSeconds)
                    const metadata = this.parseMetadata()
                    if (metadata) payload.metadata = metadata
                    const createdItem = await this.requestJson(this.config.confirmUrl, { method: 'POST', body: JSON.stringify(payload) })
                    this.replaceMedia([...this.existingMedia, this.camelizeMedia(createdItem)])
                    this.success = 'Media zapisane.'
                    this.file = null
                    this.posterFile = null
                    this.width = ''
                    this.height = ''
                    this.durationSeconds = ''
                    this.metadataText = ''
                    this.sortOrder = this.existingMedia.length
                } catch (error) {
                    this.error = error.message ?? 'Upload nie powiódł się.'
                } finally {
                    this.busy = false
                }
            },
            async deleteMedia(item) {
                this.error = null
                this.success = null
                if (!window.confirm('Usunąć ten asset z bazy i storage?')) return
                this.busy = true
                try {
                    const response = await this.requestJson(this.buildDeleteUrl(item.id), { method: 'DELETE' })
                    this.replaceMedia((response.items ?? []).map((entry) => this.camelizeMedia(entry)))
                    this.sortOrder = this.existingMedia.length
                    this.success = 'Asset został usunięty.'
                } catch (error) {
                    this.error = error.message ?? 'Usuwanie nie powiodło się.'
                } finally {
                    this.busy = false
                }
            },
            async moveMedia(itemId, direction) {
                const items = [...this.existingMedia]
                const currentIndex = items.findIndex((item) => item.id === itemId)
                if (currentIndex === -1) return
                const targetIndex = currentIndex + direction
                if (targetIndex < 0 || targetIndex >= items.length) return
                this.error = null
                this.success = null
                this.busy = true
                try {
                    ;[items[currentIndex], items[targetIndex]] = [items[targetIndex], items[currentIndex]]
                    const response = await this.requestJson(this.config.reorderUrl, {
                        method: 'PATCH',
                        body: JSON.stringify({ media_ids: items.map((item) => item.id) }),
                    })
                    this.replaceMedia((response.items ?? []).map((entry) => this.camelizeMedia(entry)))
                    this.success = 'Kolejność mediów została zaktualizowana.'
                } catch (error) {
                    this.error = error.message ?? 'Zmiana kolejności nie powiodła się.'
                } finally {
                    this.busy = false
                }
            },
            async presign(file, kind, variant) {
                const mimeType = file.type || this.fallbackMimeType(kind, file.name)
                return await this.requestJson(this.config.presignUrl, {
                    method: 'POST',
                    headers: { 'Idempotency-Key': this.idempotencyKey() },
                    body: JSON.stringify({ question_id: this.question.id, kind, mime_type: mimeType, bytes: file.size, variant }),
                })
            },
            async uploadFile(upload, file) {
                const response = await fetch(upload.upload_url, { method: upload.method ?? 'PUT', headers: upload.headers ?? {}, body: file })
                if (!response.ok) throw new Error(`Upload do storage nie powiódł się (${response.status}).`)
            },
            async requestJson(url, options = {}) {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    ...options,
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.config.csrfToken, ...(options.headers ?? {}) },
                })
                const payload = await response.json().catch(() => ({}))
                if (!response.ok) {
                    const validationMessage = payload.errors ? Object.values(payload.errors).flat()[0] : null
                    throw new Error(validationMessage ?? payload.error?.message ?? payload.message ?? 'Żądanie nie powiodło się.')
                }
                return payload.data ?? payload
            },
            parseMetadata() {
                if (this.metadataText.trim() === '') return null
                let parsed = null
                try { parsed = JSON.parse(this.metadataText) } catch (error) { throw new Error('Metadane muszą być poprawnym JSON-em.') }
                if (parsed === null || Array.isArray(parsed) || typeof parsed !== 'object') throw new Error('Metadane muszą być obiektem JSON.')
                return parsed
            },
            idempotencyKey() {
                if (window.crypto?.randomUUID) return window.crypto.randomUUID()
                return `upload-${Date.now()}-${Math.random().toString(16).slice(2)}`
            },
            fallbackMimeType(kind, filename) {
                const extension = (filename.split('.').pop() ?? '').toLowerCase()
                if (kind === 'video') return extension === 'mp4' ? 'video/mp4' : 'application/octet-stream'
                if (extension === 'png') return 'image/png'
                if (extension === 'jpg' || extension === 'jpeg') return 'image/jpeg'
                if (extension === 'webp') return 'image/webp'
                if (extension === 'avif') return 'image/avif'
                return 'application/octet-stream'
            },
            formatBytes(bytes) {
                if (!bytes) return '0 B'
                const units = ['B', 'KB', 'MB', 'GB']
                let value = Number(bytes)
                let unit = 0
                while (value >= 1024 && unit < units.length - 1) { value /= 1024; unit++ }
                return `${value.toFixed(value >= 10 || unit === 0 ? 0 : 1)} ${units[unit]}`
            },
            metadataPreview(metadata) { return metadata ? JSON.stringify(metadata, null, 2) : '{}' },
            replaceMedia(items) { this.config.existingMedia = items },
            buildDeleteUrl(id) { return `${this.config.deleteBaseUrl}/${id}` },
            camelizeMedia(item) {
                return { id: item.id, kind: item.kind, disk: item.disk, path: item.path, posterPath: item.poster_path ?? item.posterPath ?? null, url: item.url, posterUrl: item.poster_url ?? item.posterUrl ?? null, mimeType: item.mime_type ?? item.mimeType ?? null, bytes: item.bytes, width: item.width, height: item.height, durationSeconds: item.duration_seconds ?? item.durationSeconds ?? null, variant: item.variant, sortOrder: item.sort_order ?? item.sortOrder ?? 0, metadata: item.metadata ?? null }
            },
        }"
        class="space-y-6"
    >
        <section class="border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-[0.16em] text-slate-500">
                            <span x-text="question.licenseCategory?.code ? `Kategoria ${question.licenseCategory.code}` : 'Pytanie'"></span>
                            <span>•</span>
                            <span x-text="question.questionTypeLabel"></span>
                        </div>
                        <h2 class="text-lg font-semibold text-slate-950"><span x-text="question.externalId || `#${question.id}`"></span></h2>
                        <p class="text-sm leading-6 text-slate-700" x-text="question.prompt"></p>
                    </div>
                    <div class="text-sm text-slate-600 lg:max-w-xs">
                        <p class="font-medium text-slate-900" x-text="question.deliveryStatus"></p>
                        <p class="mt-1" x-text="question.deliveryIssueLabel || primaryHint"></p>
                    </div>
                </div>
            </div>
            <div class="grid gap-px bg-slate-200 sm:grid-cols-2 xl:grid-cols-4">
                <div class="bg-white px-5 py-4"><p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Stan publikacji</p><p class="mt-2 text-base font-semibold text-slate-950" x-text="question.deliveryStatus"></p><p class="mt-1 text-sm text-slate-600" x-text="question.deliveryIssueLabel || 'Brak blokady publikacji.'"></p></div>
                <div class="bg-white px-5 py-4"><p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Główne medium</p><p class="mt-2 text-base font-semibold text-slate-950" x-text="primaryState"></p><p class="mt-1 text-sm text-slate-600" x-text="primaryHint"></p></div>
                <div class="bg-white px-5 py-4"><p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Assety</p><p class="mt-2 text-base font-semibold text-slate-950" x-text="existingMedia.length"></p><p class="mt-1 text-sm text-slate-600"><span x-text="`${imageCount} obrazów · ${videoCount} wideo`"></span></p></div>
                <div class="bg-white px-5 py-4"><p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Kategoria</p><p class="mt-2 text-base font-semibold text-slate-950" x-text="question.licenseCategory?.name || '-'"></p><p class="mt-1 text-sm text-slate-600">Zarządzaj mediami bez wychodzenia z pytania.</p></div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(340px,420px)]">
            <section class="border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-950">Aktualne media</h3>
                            <p class="mt-1 text-sm text-slate-600">Sprawdź, co już jest podpięte do pytania i w jakiej kolejności będzie używane.</p>
                        </div>
                        <div class="border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700"><span x-text="`${existingMedia.length} assetów`"></span></div>
                    </div>
                </div>
                <div class="p-5">
                    <template x-if="existingMedia.length === 0">
                        <div class="border border-dashed border-slate-300 px-6 py-10 text-center">
                            <p class="text-sm font-medium text-slate-900">To pytanie nie ma jeszcze żadnych mediów.</p>
                            <p class="mt-2 text-sm text-slate-600" x-text="question.requiresPrimaryMedia ? 'Żeby było gotowe do publikacji, potrzebuje wariantu głównego.' : 'Jeśli chcesz, możesz zostawić je jako pytanie tekstowe albo dodać media później.'"></p>
                        </div>
                    </template>
                    <div class="grid gap-4 md:grid-cols-2" x-show="existingMedia.length > 0">
                        <template x-for="item in existingMedia" :key="item.id">
                            <article class="overflow-hidden border border-slate-200 bg-white">
                                <template x-if="item.kind === 'image' && item.url"><div class="border-b border-slate-200 bg-slate-50"><img :src="item.url" alt="" class="h-48 w-full object-contain bg-slate-50"></div></template>
                                <template x-if="item.kind === 'video' && item.url"><div class="border-b border-slate-200 bg-slate-950"><video class="h-48 w-full object-contain bg-slate-950" controls preload="metadata" :poster="item.posterUrl || null"><source :src="item.url" :type="item.mimeType || 'video/mp4'"></video></div></template>
                                <div class="space-y-4 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700" x-text="kindLabel(item.kind)"></span>
                                        <span class="border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700" x-text="variantLabel(item.variant)"></span>
                                        <template x-if="item.variant === 'full'"><span class="border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-900">Główne medium</span></template>
                                    </div>
                                    <dl class="grid gap-2 text-sm text-slate-700">
                                        <div class="flex items-center justify-between gap-3"><dt>Rozmiar</dt><dd x-text="formatBytes(item.bytes)"></dd></div>
                                        <div class="flex items-center justify-between gap-3"><dt>Wymiary</dt><dd x-text="item.width && item.height ? `${item.width}x${item.height}` : '-'"></dd></div>
                                        <div class="flex items-center justify-between gap-3"><dt>Długość</dt><dd x-text="item.durationSeconds ? `${item.durationSeconds}s` : '-'"></dd></div>
                                        <div class="flex items-center justify-between gap-3"><dt>Kolejność</dt><dd x-text="item.sortOrder"></dd></div>
                                    </dl>
                                    <div class="flex flex-wrap items-center gap-2 pt-1">
                                        <button class="inline-flex h-8 w-8 items-center justify-center border border-slate-300 text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 disabled:opacity-40" type="button" :disabled="busy" @click="moveMedia(item.id, -1)" title="Przesuń wyżej">↑</button>
                                        <button class="inline-flex h-8 w-8 items-center justify-center border border-slate-300 text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 disabled:opacity-40" type="button" :disabled="busy" @click="moveMedia(item.id, 1)" title="Przesuń niżej">↓</button>
                                        <a class="inline-flex items-center border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-800 transition hover:bg-slate-50" :href="item.url" target="_blank" rel="noreferrer">Otwórz</a>
                                        <button class="inline-flex items-center border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-800 transition hover:bg-slate-50 disabled:opacity-40" type="button" :disabled="busy" @click="deleteMedia(item)">Usuń</button>
                                    </div>
                                    <details class="border-t border-slate-200 pt-3">
                                        <summary class="cursor-pointer text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Dane techniczne</summary>
                                        <div class="mt-3 space-y-2 text-xs text-slate-600">
                                            <p class="break-all"><span class="font-medium text-slate-800">Path:</span> <span x-text="item.path"></span></p>
                                            <template x-if="item.posterPath"><p class="break-all"><span class="font-medium text-slate-800">Poster:</span> <span x-text="item.posterPath"></span></p></template>
                                            <template x-if="item.metadata"><pre class="overflow-x-auto border border-slate-200 bg-slate-50 p-3 text-[11px] text-slate-800" x-text="metadataPreview(item.metadata)"></pre></template>
                                        </div>
                                    </details>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>
            </section>

            <aside class="border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-950">Dodaj media</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Upload idzie bezpośrednio do storage, a po potwierdzeniu asset trafia od razu do pytania.</p>
                </div>
                <form class="space-y-5 p-5" @submit.prevent="submit">
                    <div class="border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-sm font-medium text-slate-900">Jakiego pliku teraz potrzebujesz?</p><p class="mt-1 text-sm text-slate-600" x-text="uploadHint()"></p></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-2"><span class="text-sm font-medium text-slate-800">Typ pliku</span><select class="w-full border-slate-300 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500" x-model="kind" @change="setKind($event.target.value)"><option value="image">Obraz</option><option value="video">Wideo</option></select></label>
                        <label class="space-y-2"><span class="text-sm font-medium text-slate-800">Wariant</span><select class="w-full border-slate-300 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500" x-model="variant"><template x-for="option in variantOptions" :key="option"><option :value="option" x-text="variantLabel(option)"></option></template></select></label>
                    </div>
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-slate-800">Plik</span>
                        <input class="block w-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-none file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium" type="file" :accept="acceptAttribute" @change="onFileChange">
                        <p class="text-xs text-slate-500">Dozwolone MIME: <span x-text="acceptAttribute || 'brak'"></span> · limit <span x-text="maxBytesLabel"></span></p>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-2"><span class="text-sm font-medium text-slate-800">Szerokość</span><input class="w-full border-slate-300 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500" type="number" min="1" x-model="width"></label>
                        <label class="space-y-2"><span class="text-sm font-medium text-slate-800">Wysokość</span><input class="w-full border-slate-300 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500" type="number" min="1" x-model="height"></label>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-2"><span class="text-sm font-medium text-slate-800">Długość (s)</span><input class="w-full border-slate-300 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500 disabled:opacity-60" type="number" min="0" x-model="durationSeconds" :disabled="kind !== 'video'"></label>
                        <label class="space-y-2"><span class="text-sm font-medium text-slate-800">Kolejność</span><input class="w-full border-slate-300 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500" type="number" min="0" x-model="sortOrder"></label>
                    </div>
                    <label class="space-y-2" x-show="kind === 'video'">
                        <span class="text-sm font-medium text-slate-800">Poster do wideo</span>
                        <input class="block w-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-none file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium" type="file" :accept="posterAcceptAttribute" @change="onPosterFileChange">
                        <p class="text-xs text-slate-500">Opcjonalny poster możesz podpiąć od razu przy uploadzie wideo.</p>
                    </label>
                    <details class="border border-slate-200 bg-slate-50">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-slate-900">Metadane techniczne</summary>
                        <div class="space-y-2 border-t border-slate-200 px-4 py-4">
                            <textarea class="min-h-28 w-full border-slate-300 text-sm shadow-none focus:border-primary-500 focus:ring-primary-500" x-model="metadataText" placeholder='{"codec":"h264","origin":"manual-admin-upload"}'></textarea>
                            <p class="text-xs text-slate-500">Pole opcjonalne. Użyj tylko wtedy, gdy naprawdę chcesz zapisać własne dane pomocnicze.</p>
                        </div>
                    </details>
                    <template x-if="error"><div class="border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="error"></div></template>
                    <template x-if="success"><div class="border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" x-text="success"></div></template>
                    <button class="inline-flex w-full items-center justify-center border border-slate-300 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60" type="submit" :disabled="busy"><span x-show="!busy">Zapisz media</span><span x-show="busy">Upload w toku...</span></button>
                </form>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
