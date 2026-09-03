<template>
    <div class="h-full w-full overflow-hidden bg-white text-left transition-transform duration-500 group-hover:scale-[1.02]">
        <div class="flex h-full flex-col p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3 text-[0.56rem] font-bold uppercase tracking-[0.18em] text-slate-500">
                <span>Tryb 1v1</span>
                <span class="rounded-full bg-[#eef4ff] px-2.5 py-1 text-[#023ea4]">
                    Wspolna nauka na czas
                </span>
            </div>

            <div class="mt-4 flex-1 rounded-[1.4rem] border border-slate-200 bg-[linear-gradient(180deg,#f8fbff_0%,#ffffff_100%)] shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                <div class="grid grid-cols-2 gap-3 border-b border-slate-100 px-3 py-3 sm:px-4">
                    <div class="rounded-[1rem] border border-[#d8e5ff] bg-white px-3 py-3 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[0.62rem] font-bold uppercase tracking-[0.16em] text-slate-500">
                                Ty
                            </span>
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[0.68rem] font-semibold text-emerald-700">
                                8 / 10
                            </span>
                        </div>
                        <p class="mt-2 text-[1.1rem] font-semibold tracking-tight text-[#0f172a]">
                            18.4 s
                        </p>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full w-[78%] rounded-full bg-[#023ea4]" />
                        </div>
                    </div>

                    <div class="rounded-[1rem] border border-[#f0d9dd] bg-white px-3 py-3 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[0.62rem] font-bold uppercase tracking-[0.16em] text-slate-500">
                                Zaproszona osoba
                            </span>
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[0.68rem] font-semibold text-amber-700">
                                7 / 10
                            </span>
                        </div>
                        <p class="mt-2 text-[1.1rem] font-semibold tracking-tight text-[#0f172a]">
                            20.1 s
                        </p>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full w-[66%] rounded-full bg-[#f59e0b]" />
                        </div>
                    </div>
                </div>

                <div class="space-y-3 p-3 sm:p-4">
                    <div class="flex items-center justify-between rounded-[1rem] border border-[#dbe7fb] bg-white px-3 py-2.5 shadow-sm">
                        <div>
                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.16em] text-slate-500">
                                Mecz trwa
                            </p>
                            <p class="mt-1 text-[0.92rem] font-semibold text-[#0f172a]">
                                Kto odpowie poprawnie i najszybciej?
                            </p>
                        </div>
                        <span class="rounded-full bg-[#023ea4] px-2.5 py-1 text-[0.72rem] font-semibold text-white">
                            10 pytan
                        </span>
                    </div>

                    <div class="rounded-[1rem] border border-slate-200 bg-white p-3 shadow-sm">
                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.15em] text-slate-500">
                            Aktualne pytanie
                        </p>
                        <p class="mt-2 text-[0.94rem] font-semibold leading-snug text-[#0f172a]">
                            Czy kierujacy powinien ustapic pierwszenstwa pieszym znajdujacym sie przy przejsciu?
                        </p>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-[0.82rem] font-semibold text-emerald-700">
                                Ty: TAK
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-center text-[0.82rem] font-semibold text-slate-500">
                                Przeciwnik odpowiada...
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-[1.05fr_0.95fr] gap-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-center text-[0.78rem] font-semibold text-slate-500">
                            Nauka, emocje i wspolne tempo
                        </div>
                        <div class="rounded-xl bg-[#0f172a] px-3 py-2 text-center text-[0.8rem] font-semibold text-white shadow-[0_10px_20px_rgba(15,23,42,0.18)]">
                            Zapros kogos do meczu
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
