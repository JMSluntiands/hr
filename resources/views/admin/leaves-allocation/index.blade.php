@extends('layouts.admin')
@section('title', 'Leave Allocation')
@section('content')
<h1 class="text-2xl font-semibold text-slate-800 mb-2">Leave Allocation {{ date('Y') }}</h1>
<p class="text-sm text-slate-500 mb-6">To edit allocations, use <a href="{{ url('/legacy/admin/leaves-allocation.php') }}" class="text-[#FA9800] underline">legacy allocation tool</a>.</p>
<div class="bg-white rounded-xl border overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Leave Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Total</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Used</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Remaining</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($allocations as $a)
            <tr>
                <td class="px-4 py-3">{{ $a->employee?->full_name }}</td>
                <td class="px-4 py-3">{{ $a->leave_type }}</td>
                <td class="px-4 py-3">{{ $a->total_days }}</td>
                <td class="px-4 py-3">{{ $a->used_days }}</td>
                <td class="px-4 py-3">{{ $a->remaining_days }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
