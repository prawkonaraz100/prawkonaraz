<script setup lang="ts">
import emblemImg from "../../images/session/question-source-emblem-crop.png";
</script>

<template>
    <div class="h-full w-full bg-white font-sans text-left relative overflow-hidden transition-transform duration-500 group-hover:scale-[1.02] border-l-[5px] border-[#1e293b]">
        <div class="h-full w-full p-4 sm:p-5 flex flex-col justify-center relative z-10">
            <div class="flex justify-between items-start">
                <div class="flex-1 pr-2">
                    <p class="text-[0.55rem] sm:text-[0.6rem] font-bold uppercase tracking-widest text-slate-500 mb-1">
                        Bieżąca sesja
                    </p>
                    <h3 class="text-[1.05rem] sm:text-[1.15rem] font-bold text-[#0f172a] leading-tight mb-1.5 tracking-tight">
                        gov.pl
                    </h3>
                    <p class="text-[0.55rem] sm:text-[0.6rem] font-bold uppercase tracking-wider text-slate-500 leading-[1.4]">
                        Oficjalna baza pytań<br />państwowych
                    </p>
                </div>
                <div class="w-10 h-10 sm:w-12 sm:h-12 shrink-0 ml-1">
                    <img :src="emblemImg" alt="Godło" class="w-full h-full object-contain opacity-90" />
                </div>
            </div>

            <hr class="my-3 sm:my-4 border-slate-100" />

            <div class="flex items-center justify-between">
                <div class="text-[0.55rem] sm:text-[0.6rem] font-bold uppercase tracking-widest text-slate-500 leading-tight">
                    Numer<br />źródłowy
                </div>
                <div class="border border-slate-200 px-3 py-1 sm:px-4 sm:py-1.5 bg-white text-[0.85rem] sm:text-[0.95rem] font-bold text-[#0f172a] shadow-sm">
                    13383
                </div>
            </div>
        </div>
        
        <!-- Subtle decorative gradient -->
        <div class="absolute -bottom-6 -right-6 w-24 h-24 bg-blue-50 rounded-full blur-2xl opacity-50 pointer-events-none"></div>
    </div>
</template>
