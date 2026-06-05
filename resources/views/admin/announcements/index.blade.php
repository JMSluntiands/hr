@extends('layouts.admin')
@section('title', 'Announcements - Admin')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Announcements</h1>
    <p class="text-sm text-slate-500 mt-1">Admin announcement module</p>
</div>

<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase tracking-wide mb-4">
        In Progress
    </div>
    <p class="text-sm text-slate-600">
        Announcement management is currently in progress. This page will include create, publish, and archive features soon.
    </p>
</section>
@endsection
