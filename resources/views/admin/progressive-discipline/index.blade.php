@extends('layouts.admin')
@section('title', 'Progressive Discipline - Admin')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Progressive Discipline</h1>
        <p class="text-sm text-slate-500 mt-1">Track warnings and escalation records per employee</p>
    </div>
</div>

@if (!$tableReady)
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
    The table is not ready yet. Run <code class="bg-white/80 px-1 rounded">database/setup_progressive_discipline_table.php</code> once, then refresh this page.
</div>
@endif

@if(session('progressive_discipline_msg'))
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700">{{ session('progressive_discipline_msg') }}</div>
@endif

@if($tableReady)
<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-700 mb-4">Add Discipline Record</h2>
    <form method="post" action="{{ route('admin.progressive-discipline.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Employee</label>
            <select name="employee_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
                <option value="">Select employee</option>
                @foreach($employees as $emp)
                    <option value="{{ (int) $emp->id }}"@selected((int) old('employee_id') === (int) $emp->id)>
                        {{ ($emp->full_name ?? 'Unknown') . ' (' . ($emp->employee_id ?? 'N/A') . ')' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Incident Date</label>
            <input type="date" name="incident_date" value="{{ old('incident_date') }}" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Offense Type</label>
            <input type="text" name="offense_type" value="{{ old('offense_type') }}" maxlength="120" placeholder="Late attendance, policy violation..." required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Discipline Level</label>
            <select name="discipline_level" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
                @foreach($disciplineLevels as $level)
                    <option value="{{ $level }}"@selected(old('discipline_level') === $level)>{{ $level }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-medium text-slate-700 mb-1">Incident Description</label>
            <textarea name="description" rows="3" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">{{ old('description') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-medium text-slate-700 mb-1">Action Taken / Notes</label>
            <textarea name="action_taken" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">{{ old('action_taken') }}</textarea>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Next Review Date (Optional)</label>
            <input type="date" name="next_review_date" value="{{ old('next_review_date') }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 transition-colors">
                Save Record
            </button>
        </div>
    </form>
</section>

<section class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Discipline History</h2>
        <span class="text-xs text-slate-500">{{ $records->count() }} record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Date</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Offense</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Level</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                @php
                    $status = $rec->status ?? 'Active';
                    $statusClass = match ($status) {
                        'Resolved' => 'bg-emerald-100 text-emerald-700',
                        'Escalated' => 'bg-red-100 text-red-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-600">
                        {{ !empty($rec->incident_date) ? \Carbon\Carbon::parse($rec->incident_date)->format('M d, Y') : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-700">{{ $rec->full_name ?? 'Unknown Employee' }}</div>
                        <div class="text-xs text-slate-500">{{ $rec->emp_code ?? 'N/A' }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $rec->offense_type ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $rec->discipline_level ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ $status }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <form method="post" action="{{ route('admin.progressive-discipline.update-status', $rec->id) }}" class="flex gap-2 items-center">
                            @csrf
                            <select name="status" class="px-2 py-1 border border-slate-200 rounded text-xs">
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}"@selected($status === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-2.5 py-1 rounded bg-slate-700 text-white text-xs hover:bg-slate-800">Update</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">No discipline records yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
