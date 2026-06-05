@extends('layouts.employee')
@section('title', 'My Request')
@section('content')
<h1 class="text-2xl font-semibold text-slate-800 mb-2">My Request</h1>
<p class="text-sm text-slate-500 mb-6">Leave and document requests. COE and advanced flows use the legacy form.</p>
@if(!$employee)
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">Your account is not linked to an employee record.</div>
@endif
<div class="grid gap-6 lg:grid-cols-2">
    <div class="bg-white rounded-xl border p-5">
        <h2 class="font-semibold mb-3">Leave requests</h2>
        <a href="{{ route('employee.timeoff.index') }}" class="text-[#FA9800] text-sm hover:underline">Submit leave via My Leave Credits →</a>
        <ul class="mt-4 space-y-2 text-sm">
            @foreach($leaves as $l)
            <li class="flex justify-between border-b border-slate-50 py-2"><span>{{ $l->leave_type }}</span><span>{{ $l->status }}</span></li>
            @endforeach
        </ul>
    </div>
    <div class="bg-white rounded-xl border p-5">
        <h2 class="font-semibold mb-3">Document requests</h2>
        <a href="{{ url('/legacy/employee/request.php') }}" class="text-[#FA9800] text-sm hover:underline">Open full request form (COE, certificates) →</a>
        <ul class="mt-4 space-y-2 text-sm">
            @foreach($documents as $d)
            <li class="flex justify-between border-b border-slate-50 py-2"><span>{{ $d->document_type }}</span><span>{{ $d->status }}</span></li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
