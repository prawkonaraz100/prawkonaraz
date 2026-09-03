<template>
    <div class="h-full w-full overflow-hidden bg-white text-left transition-transform duration-500 group-hover:scale-[1.02]">
        <div class="flex h-full flex-col p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3 text-[0.56rem] font-bold uppercase tracking-[0.18em] text-slate-500">
                <span>Sesja blyskawiczna</span>
                <span class="rounded-full bg-[#eef4ff] px-2.5 py-1 text-[#023ea4]">
                    20 pytan bez zatrzymywania
                </span>
            </div>

            <div class="mt-4 flex-1 rounded-[1.4rem] border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                <div class="border-b border-slate-100 px-3 py-2.5 sm:px-4">
                    <div class="grid grid-cols-[auto_1fr_auto] items-center gap-2 text-[0.58rem] font-semibold uppercase tracking-[0.16em] text-slate-500">
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-600">
                            Pytanie 20/20
                        </span>
                        <span class="truncate text-center">Tryb szybki</span>
                        <span class="rounded-full bg-rose-50 px-2 py-1 text-rose-600">
                            Blad
                        </span>
                    </div>
                </div>

                <div class="space-y-3 p-3 sm:p-4">
                    <div class="rounded-[1rem] border border-slate-200 bg-[#fbfcfe] p-3">
                        <p class="text-[0.72rem] font-bold uppercase tracking-[0.14em] text-slate-500">
                            Pytanie
                        </p>
                        <p class="mt-2 text-[0.95rem] font-semibold leading-snug text-[#0f172a]">
                            Czy w tej sytuacji odpowiedz musi brzmiec
                            <span class="text-[#023ea4]">TAK albo NIE</span>?
                        </p>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-center text-[0.85rem] font-semibold text-rose-700 shadow-sm">
                                Twoja odpowiedz: NIE
                            </div>
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-[0.85rem] font-semibold text-emerald-700 shadow-sm">
                                Poprawnie: TAK
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[1rem] border border-[#c8dafc] bg-[#eff5ff] p-3 sm:p-4">
                        <div class="flex items-center gap-2 text-[0.68rem] font-bold uppercase tracking-[0.16em] text-[#023ea4]">
                            <span class="inline-block h-2 w-2 rounded-full bg-[#023ea4]" />
                            Wyjasnienie pojawia sie od razu pod materialem
                        </div>

                        <p class="mt-2 text-[0.86rem] leading-6 text-slate-700">
                            To pytanie sprawdza decyzje binarna. Szukasz potwierdzenia sytuacji,
                            wiec odpowiedz musi byc jednoznaczna.
                        </p>

                        <p class="mt-2 text-[0.95rem] font-semibold leading-6 text-[#0f172a]">
                            Krotko, konkretnie, bez odrywania wzroku od sesji.
                        </p>
                    </div>

                    <div class="grid grid-cols-[1.1fr_0.9fr] gap-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-center text-[0.78rem] font-semibold text-slate-500">
                            Wzrok zostaje w tym samym miejscu
                        </div>
                        <div class="rounded-xl bg-[#023ea4] px-3 py-2 text-center text-[0.8rem] font-semibold text-white shadow-[0_10px_20px_rgba(2,62,164,0.2)]">
                            Nastepne pytanie
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
