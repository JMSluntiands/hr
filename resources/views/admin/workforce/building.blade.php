@extends('layouts.module-select')

@section('title', 'Workforce — In Progress')

@section('content')
<div class="w-full max-w-lg">
    <div class="bg-white rounded-2xl md:rounded-3xl shadow-2xl overflow-hidden text-center">
        <div class="h-1.5 bg-gradient-to-r from-slate-500 via-slate-400 to-slate-600"></div>
        <div class="px-6 md:px-10 py-10 md:py-12">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-600 to-slate-800 text-white flex items-center justify-center shadow-lg mb-6">
                <i class="fa fa-clock-o text-2xl" aria-hidden="true"></i>
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Workforce Module</p>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 mb-2">In Progress</h1>
            <p class="text-slate-500 text-sm md:text-base leading-relaxed max-w-sm mx-auto">
                Ang Workforce module (time keeping, payslips, at reports) ay ginagawa pa. Balik ka na lang mamaya.
            </p>
            <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                </span>
                Building
            </div>
            <a href="{{ route('admin.module-select') }}"
               class="mt-8 inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-[#FA9800] text-white text-sm font-medium hover:bg-[#e88a00] transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Main Menu
            </a>
        </div>
    </div>
</div>
@endsection
