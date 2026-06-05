@extends('layouts.module-select')

@section('title', 'Select Module - Admin')

@section('content')
@php
    $modules = [
        'hr' => [
            'title' => 'HR Management',
            'desc' => 'Employee records, leave requests, documents, and HR dashboard.',
            'icon' => 'fa-users',
            'color' => 'from-amber-500 to-orange-600',
        ],
        'workforce' => [
            'title' => 'Workforce',
            'desc' => 'Time keeping, payslips, and workforce reports.',
            'icon' => 'fa-clock-o',
            'color' => 'from-slate-600 to-slate-800',
            'building' => true,
        ],
        'inventory' => [
            'title' => 'Inventory',
            'desc' => 'Stocks, allocations, item requests, and decommission.',
            'icon' => 'fa-cubes',
            'color' => 'from-emerald-500 to-teal-600',
        ],
        'permission' => [
            'title' => 'Permissions',
            'desc' => 'Department-scoped approval access for admin users.',
            'icon' => 'fa-shield',
            'color' => 'from-violet-500 to-purple-700',
        ],
    ];
@endphp

<div class="w-full max-w-2xl">
    <div class="bg-white rounded-2xl md:rounded-3xl shadow-2xl overflow-hidden">
        <div class="px-6 md:px-8 pt-8 pb-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#FA9800] mb-1">Administrator</p>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800">Welcome{{ session('name') ? ', '.e(session('name')) : '' }}</h1>
            <p class="text-slate-500 mt-2 text-sm md:text-base">Choose which system you want to open.</p>
        </div>

        <form method="POST" action="{{ route('admin.module-select') }}" class="p-4 md:p-6 space-y-3">
            @csrf
            @foreach($modules as $key => $mod)
            <button type="submit" name="module" value="{{ $key }}"
                class="group w-full text-left flex items-start gap-4 p-4 md:p-5 rounded-xl border-2 border-slate-100
                       hover:border-[#FA9800] hover:bg-amber-50/80 hover:shadow-md
                       focus:outline-none focus:ring-2 focus:ring-[#FA9800]/40 focus:border-[#FA9800]
                       transition-all duration-200">
                <span class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br {{ $mod['color'] }} text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                    <i class="fa {{ $mod['icon'] }} text-lg" aria-hidden="true"></i>
                </span>
                <span class="flex-1 min-w-0 pt-0.5">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="block text-base md:text-lg font-semibold text-slate-800 group-hover:text-[#c2410c]">{{ $mod['title'] }}</span>
                        @if(!empty($mod['building']))
                        <span class="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">Building</span>
                        @endif
                    </span>
                    <span class="block text-sm text-slate-500 mt-1 leading-snug">{{ $mod['desc'] }}</span>
                </span>
                <span class="flex-shrink-0 self-center text-slate-300 group-hover:text-[#FA9800] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            </button>
            @endforeach
        </form>

        @if(session('error'))
        <p class="mx-6 mb-4 px-4 py-3 rounded-lg text-sm text-amber-800 bg-amber-50 border border-amber-200">{{ session('error') }}</p>
        @endif
        @error('module')
        <p class="px-6 pb-6 text-red-600 text-sm">{{ $message }}</p>
        @enderror
    </div>
</div>
@endsection
