@extends('layouts.employee')

@section('title', 'My Profile')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">My Profile</h1>
            <p class="text-sm text-slate-500 mt-1">Your personal and employment information on file with HR</p>
        </div>
        <a href="{{ route('employee.settings') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#e8870a] font-medium text-sm shadow-sm transition-colors shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Account settings
        </a>
    </div>

    <p class="text-sm text-slate-500 -mt-2">
        Profile details are maintained by HR. Use
        <a href="{{ route('employee.requests.index') }}" class="text-[#c2410c] font-medium hover:text-[#FA9800]">Request COE</a>
        for Certificate of Employment updates.
    </p>

    @include('admin.staff.partials.show-hero')
    @include('admin.staff.partials.show-info')
</div>
@endsection
