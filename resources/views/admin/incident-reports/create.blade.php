@extends('layouts.admin')
@section('title', 'Add Incident Report - Admin')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Add incident report</h1>
        <p class="text-sm text-slate-500 mt-1">Create a new report on behalf of the organization.</p>
    </div>
    <a href="{{ $listUrl }}" class="text-sm font-medium text-[#FA9800] hover:text-amber-700">List of incident →</a>
</div>

@if (!$tableReady)
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
    Run <code class="bg-white/80 px-1 rounded">database/setup_incident_reports_table.php</code> once, then refresh.
</div>
@endif

@if(session('incident_report_flash'))
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700">{{ session('incident_report_flash') }}</div>
@endif

@if($tableReady)
<section class="w-full min-w-0">
    @include('partials.incident-report-form', [
        'formAction' => $formAction,
        'record' => $record,
        'mode' => $mode,
        'submitLabel' => $submitLabel,
        'cancelUrl' => $cancelUrl,
        'typeDescriptions' => $typeDescriptions,
        'extraHiddenHtml' => '',
    ])
</section>
@endif
@endsection
