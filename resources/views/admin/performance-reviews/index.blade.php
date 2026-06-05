@extends('layouts.admin')
@section('title', 'Staff Performance Reviews')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Staff Performance Reviews</h1>
    <p class="text-sm text-slate-500 mt-1">Submissions from employees whose department has &quot;Additional performance review&quot; enabled under Department settings.</p>
</div>

<section class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-5 py-4 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-700">Submitted reviews</h2>
    </div>
    <div class="p-5 overflow-x-auto">
        <table id="perfReviewTable" class="min-w-full text-sm w-full">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Submitted</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Department</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Review date</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Staff (form)</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Supervisor</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Ratings (8 areas)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $r)
                <tr class="border-b border-slate-100 hover:bg-slate-50/80 align-top">
                    <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                        {{ $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('M j, Y g:i A') : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800">{{ $r->employee_full_name ?? '' }}</div>
                        <div class="text-xs text-slate-500">{{ $r->employee_code ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $r->employee_department ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ $r->review_date ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $r->staff_name ?? '' }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $r->supervisor_name ?? '' }}</td>
                    <td class="px-4 py-3 text-slate-700 whitespace-nowrap tabular-nums text-xs sm:text-sm">
                        {{ $performanceReviews->ratingsSummary($r) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">No performance reviews submitted yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<p class="mt-4 text-xs text-slate-500">Order: Accuracy · Cross-ref · Comprehension · Teamwork · Initiative to learn · Daily output · Task management · Communication of delays. Dashes indicate older rows before that competency existed. Full text is stored per submission in the database.</p>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $('#perfReviewTable tbody tr').length && !$('#perfReviewTable tbody tr td[colspan]').length) {
        $('#perfReviewTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: { search: '', searchPlaceholder: 'Search…', emptyTable: 'No performance reviews submitted yet.' },
        });
    }
});
</script>
@endpush
