@extends('layouts.admin')
@section('title', 'Admin Dashboard')

@push('head')
<style>
    .hr-dash-hero {
        background: linear-gradient(135deg, #1e1e2d 0%, #2d2d42 45%, #FA9800 120%);
    }
    .hr-stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hr-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px -8px rgba(30, 30, 45, 0.15);
    }
    .hr-quick-card {
        transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .hr-quick-card:hover {
        transform: translateY(-1px);
        border-color: rgba(250, 152, 0, 0.45);
        box-shadow: 0 8px 20px -6px rgba(250, 152, 0, 0.2);
    }
</style>
@endpush

@section('content')
@php
    $deptMax = collect($stats['departments'])->max('count') ?: 1;
@endphp

{{-- Hero --}}
<div class="hr-dash-hero rounded-2xl p-6 md:p-8 mb-8 text-white shadow-lg relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3 blur-2xl pointer-events-none"></div>
    <div class="absolute bottom-0 left-1/4 w-48 h-48 bg-[#FA9800]/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-white/70 text-sm font-medium mb-1">{{ now()->format('l, F j, Y') }}</p>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Welcome back, {{ $displayName }}</h1>
            <p class="text-white/75 text-sm mt-2 max-w-xl">Here’s what needs your attention today across employees, requests, and approvals.</p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <a href="{{ route('admin.staff.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-[#1e1e2d] text-sm font-semibold shadow-md hover:bg-white/95 transition-colors">
                <svg class="w-4 h-4 text-[#FA9800]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add employee
            </a>
            <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-sm font-medium hover:bg-white/15 transition-colors">
                View all staff
            </a>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-8">
    <div class="hr-stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active employees</p>
                <p class="text-3xl font-bold text-[#FA9800] mt-2">{{ number_format($stats['totalEmployees']) }}</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-[#FA9800]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
        </div>
        <p class="text-xs text-slate-400 mt-3">Currently active in the system</p>
    </div>

    <div class="hr-stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Document requests</p>
                <p class="text-3xl font-bold text-slate-800 mt-2">{{ number_format($stats['openRequests']) }}</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
        </div>
        <p class="text-xs text-slate-400 mt-3">Pending review</p>
    </div>

    <div class="hr-stat-card bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending approvals</p>
                <p class="text-3xl font-bold text-slate-800 mt-2">{{ number_format($stats['pendingApprovals']) }}</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </span>
        </div>
        <p class="text-xs text-slate-400 mt-3">
            Leaves {{ $stats['pendingLeaves'] }} · Reimb. {{ $stats['pendingReimbursements'] }}
        </p>
    </div>

    <div class="hr-stat-card bg-gradient-to-br from-[#FA9800] to-[#e8870a] rounded-2xl p-5 text-white shadow-md shadow-amber-200/50">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Action needed</p>
                <p class="text-3xl font-bold mt-2">{{ number_format($stats['pendingApprovals'] + $stats['openRequests']) }}</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </span>
        </div>
        <p class="text-xs text-white/80 mt-3">Total open items to review</p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-5 mb-8">
    {{-- Quick actions --}}
    <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold text-slate-800">Quick actions</h2>
            <span class="text-xs text-slate-400">Shortcuts</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            @php
                $quickLinks = [
                    ['Staff list', route('admin.staff.index'), 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', null],
                    ['Leave request', route('admin.leave-requests.index'), 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', $stats['pendingLeaves'] ?: null],
                    ['Leave history', route('admin.leaves.history.index'), 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', null],
                    ['Reimbursements', route('admin.reimbursements.index'), 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', $stats['pendingReimbursements'] ?: null],
                    ['Document requests', route('admin.documents.index'), 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', $stats['pendingDocuments'] ?: null],
                    ['Activity log', route('admin.activity-log.index'), 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', null],
                    ['Leave summary', route('admin.leaves-summary.index'), 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', null],
                ];
            @endphp
            @foreach($quickLinks as [$label, $href, $iconPath, $badge])
            <a href="{{ $href }}" class="hr-quick-card group flex items-center gap-3 p-4 rounded-xl border border-slate-100 bg-slate-50/50">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white border border-slate-100 text-[#FA9800] group-hover:bg-amber-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/></svg>
                </span>
                <span class="flex-1 min-w-0">
                    <span class="block text-sm font-medium text-slate-800 group-hover:text-[#FA9800] transition-colors">{{ $label }}</span>
                </span>
                @if($badge)
                <span class="shrink-0 min-w-[1.25rem] h-5 px-1.5 flex items-center justify-center rounded-full bg-[#FA9800] text-white text-xs font-semibold">{{ $badge > 99 ? '99+' : $badge }}</span>
                @endif
                <svg class="w-4 h-4 text-slate-300 group-hover:text-[#FA9800] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Departments --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold text-slate-800">Departments</h2>
            <span class="text-xs font-medium text-[#FA9800]">{{ count($stats['departments']) }} teams</span>
        </div>
        @forelse($stats['departments'] as $row)
        <div class="mb-4 last:mb-0">
            <div class="flex justify-between text-sm mb-1.5">
                <span class="font-medium text-slate-700 truncate pr-2">{{ $row->department ?: 'Unassigned' }}</span>
                <span class="text-slate-500 shrink-0">{{ $row->count }}</span>
            </div>
            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-[#FA9800] to-[#ffc266] transition-all" style="width: {{ min(100, round(($row->count / $deptMax) * 100)) }}%"></div>
            </div>
        </div>
        @empty
        <div class="text-center py-8 text-slate-400">
            <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <p class="text-sm">No department data yet</p>
        </div>
        @endforelse
    </div>
</div>

{{-- Recent activity --}}
@if(count($stats['recentActivity']))
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-800">Recent activity</h2>
        <a href="{{ route('admin.activity-log.index') }}" class="text-sm font-medium text-[#FA9800] hover:underline">View all</a>
    </div>
    <ul class="divide-y divide-slate-50">
        @foreach($stats['recentActivity'] as $log)
        @php
            $action = strtolower((string) ($log->action ?? ''));
            $dotClass = 'bg-slate-300';
            if (str_contains($action, 'login')) {
                $dotClass = 'bg-emerald-400';
            } elseif (str_contains($action, 'add') || str_contains($action, 'approve')) {
                $dotClass = 'bg-[#FA9800]';
            } elseif (str_contains($action, 'edit') || str_contains($action, 'update')) {
                $dotClass = 'bg-blue-400';
            } elseif (str_contains($action, 'decline') || str_contains($action, 'reject')) {
                $dotClass = 'bg-red-400';
            }
            $when = $log->created_at ? \Carbon\Carbon::parse($log->created_at) : null;
        @endphp
        <li class="px-6 py-4 flex gap-4 hover:bg-slate-50/80 transition-colors">
            <span class="mt-1.5 h-2.5 w-2.5 rounded-full shrink-0 {{ $dotClass }}"></span>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-slate-800">
                    <span class="font-medium">{{ $log->user_name ?? 'System' }}</span>
                    <span class="text-slate-500"> · {{ $log->action ?? 'Activity' }}</span>
                </p>
                @if(!empty($log->description))
                <p class="text-sm text-slate-500 mt-0.5 truncate">{{ $log->description }}</p>
                @endif
            </div>
            <time class="text-xs text-slate-400 shrink-0 whitespace-nowrap" datetime="{{ $when?->toIso8601String() }}">
                {{ $when ? $when->diffForHumans() : '—' }}
            </time>
        </li>
        @endforeach
    </ul>
</div>
@endif
@endsection
