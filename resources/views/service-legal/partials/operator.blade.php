<dl class="mt-4 grid gap-4 text-sm text-slate-700 sm:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <dt class="font-semibold text-slate-950">Usługodawca i administrator danych</dt>
        <dd class="mt-1 leading-6">
            {{ $organization['legal_name'] ?: $organization['name'] }}
            @if ($legalDocuments['operator_address'])
                <br>{{ $legalDocuments['operator_address'] }}
            @endif
            @if ($legalDocuments['operator_tax_id'])
                <br>NIP: {{ $legalDocuments['operator_tax_id'] }}
            @endif
        </dd>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <dt class="font-semibold text-slate-950">Kontakt</dt>
        <dd class="mt-1 leading-6">
            <a class="font-medium text-blue-700 hover:underline" href="mailto:{{ $legalDocuments['privacy_email'] }}">
                {{ $legalDocuments['privacy_email'] }}
            </a>
            <br>
            <a class="text-blue-700 hover:underline" href="{{ route('about.contact', absolute: false) }}">Formularz i dane kontaktowe</a>
        </dd>
    </div>
</dl>
