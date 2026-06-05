@extends('layouts.employee')

@section('title', 'Dashboard')

@push('head')
<style>
    .emp-dash-hero {
        background: linear-gradient(135deg, #fff7ed 0%, #ffffff 45%, #f8fafc 100%);
        border: 1px solid rgba(250, 152, 0, 0.15);
    }
    .emp-quick-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .emp-quick-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px -8px rgba(250, 152, 0, 0.25);
        border-color: rgba(250, 152, 0, 0.45);
    }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    @if($isDefaultPassword)
    <div class="flex gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 text-sm">
        <svg class="w-5 h-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"/></svg>
        <p>Please change your default password in <a href="{{ route('employee.settings') }}" class="font-semibold text-[#c2410c] underline underline-offset-2">Settings</a>.</p>
    </div>
    @endif

    @if($unlinked)
    <div class="flex gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p>Your login is not linked to an employee record. Please contact HR.</p>
    </div>
    @endif

    @php
        $hour = (int) date('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $photoUrl = ($employeePhoto ?? null) ? asset('uploads/'.$employeePhoto) : null;
        $initials = strtoupper(substr($employee?->full_name ?? $employeeName ?? 'E', 0, 1));
        $quickLinks = [
            ['route' => 'employee.timeoff.index', 'label' => 'Leave Credits', 'desc' => 'View balance & request time off', 'color' => 'bg-blue-50 text-blue-600', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['route' => 'employee.reimbursements.index', 'label' => 'Reimbursements', 'desc' => 'Submit and track claims', 'color' => 'bg-emerald-50 text-emerald-600', 'icon' => 'M12 8c-1.657 0-3 .895-3 2m6 4H9m6-8H9m10 14H5a2 2 0 01-2-2V6a2 2 0 012-2h9l5 5v9a2 2 0 01-2 2z'],
            ['route' => 'employee.profile.show', 'label' => 'My Profile', 'desc' => 'Personal & work details', 'color' => 'bg-violet-50 text-violet-600', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['route' => 'employee.requests.index', 'label' => 'Request COE', 'desc' => 'Certificate of Employment', 'color' => 'bg-amber-50 text-amber-700', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['route' => 'employee.inventory', 'params' => ['view' => 'list'], 'label' => 'My Inventory', 'desc' => 'Assigned items & requests', 'color' => 'bg-teal-50 text-teal-600', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['route' => 'employee.settings', 'label' => 'Settings', 'desc' => 'Password & account', 'color' => 'bg-slate-100 text-slate-600', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ];
    @endphp

    <section class="emp-dash-hero rounded-2xl p-6 md:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            <div class="relative shrink-0">
                <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl overflow-hidden ring-4 ring-white shadow-md bg-gradient-to-br from-[#FA9800] to-[#e8870a] flex items-center justify-center">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="" class="w-full h-full object-cover">
                    @else
                        <span class="text-3xl md:text-4xl font-bold text-white">{{ $initials }}</span>
                    @endif
                </div>
                <span class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center">
                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-[#c2410c] uppercase tracking-wide">{{ $greeting }}</p>
                @if($employee)
                <h1 class="text-2xl md:text-3xl font-bold text-slate-800 mt-1 truncate">{{ $employee->full_name }}</h1>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    @if($employee->position)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/80 text-slate-700 border border-slate-200/80">{{ $employee->position }}</span>
                    @endif
                    @if($employee->department)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-[#9a3412]">{{ $employee->department }}</span>
                    @endif
                    @if($employee->status)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ strtolower($employee->status) === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $employee->status }}</span>
                    @endif
                </div>
                @else
                <h1 class="text-2xl font-bold text-slate-800 mt-1">Employee Portal</h1>
                <p class="text-slate-500 text-sm mt-1">Complete your profile setup with HR.</p>
                @endif
            </div>
            <div class="shrink-0 text-right hidden sm:block">
                <p class="text-xs text-slate-500 uppercase tracking-wide">Today</p>
                <p class="text-lg font-semibold text-slate-800">{{ now()->timezone('Asia/Manila')->format('M j, Y') }}</p>
                <p class="text-sm text-slate-500">{{ now()->timezone('Asia/Manila')->format('l') }}</p>
            </div>
        </div>
    </section>

    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-800">Quick access</h2>
            <span class="text-xs text-slate-500">Jump to a section</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($quickLinks as $item)
            <a href="{{ route($item['route'], $item['params'] ?? []) }}"
               class="emp-quick-card group flex gap-4 p-5 bg-white rounded-xl border border-slate-200/80 shadow-sm">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $item['color'] }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                    </svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block font-semibold text-slate-800 group-hover:text-[#c2410c] transition-colors">{{ $item['label'] }}</span>
                    <span class="block text-xs text-slate-500 mt-0.5 leading-snug">{{ $item['desc'] }}</span>
                </span>
                <svg class="w-4 h-4 shrink-0 self-center text-slate-300 group-hover:text-[#FA9800] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endforeach
        </div>
    </section>
</div>
@endsection
