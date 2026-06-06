@extends('layouts.employee')
@section('title', 'Time off / Leave')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Time off / Leave</h1>
        <p class="text-sm text-slate-500 mt-1">Submit a request for HR to review. Total leave hours update as you fill in dates and times.</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6">
        <h2 class="font-semibold text-slate-800 mb-4">New leave request</h2>
        <form id="leaveForm" class="grid gap-4 sm:grid-cols-2 max-w-2xl">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Leave type</label>
                <select name="leave_type" required class="w-full border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#FA9800]/20 focus:border-[#FA9800]">
                    <option value="Vacation Leave">Vacation Leave</option>
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Bereavement Leave">Bereavement Leave</option>
                    <option value="Emergency Leave">Emergency Leave</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Start date</label>
                <input type="date" name="start_date" id="start_date" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#FA9800]/20 focus:border-[#FA9800]">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Start time</label>
                <input type="time" name="start_time" id="start_time" value="09:00" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#FA9800]/20 focus:border-[#FA9800]">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Return date</label>
                <input type="date" name="end_date" id="end_date" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#FA9800]/20 focus:border-[#FA9800]">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Return time</label>
                <input type="time" name="end_time" id="end_time" value="17:00" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#FA9800]/20 focus:border-[#FA9800]">
            </div>

            <div class="sm:col-span-2">
                <div id="leaveHoursSummary" class="rounded-xl border border-amber-200/80 bg-gradient-to-r from-amber-50 to-orange-50/80 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800/80 mb-1">Total leave (preview)</p>
                    <p id="leaveHoursValue" class="text-2xl font-bold text-slate-800">—</p>
                    <p id="leaveHoursHint" class="text-xs text-slate-500 mt-1">Select dates and times to see total hours.</p>
                    <p id="leaveHoursError" class="text-xs text-red-600 mt-1 hidden"></p>
                </div>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Reason</label>
                <textarea name="reason" required rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#FA9800]/20 focus:border-[#FA9800]"></textarea>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" id="leaveSubmitBtn" class="px-5 py-2.5 bg-[#FA9800] hover:bg-[#e8870a] text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Submit request
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <h2 class="font-semibold text-slate-800 p-4 border-b border-slate-100">My leave requests ({{ $year }})</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Schedule</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Hours</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requests as $r)
                    @php
                        $startT = $r->start_time ? substr((string) $r->start_time, 0, 5) : null;
                        $endT = $r->end_time ? substr((string) $r->end_time, 0, 5) : null;
                        $hoursLabel = $r->total_hours
                            ? \App\Support\LeaveHoursCalculator::formatHoursShort((float) $r->total_hours)
                            : (($r->total_days ?? $r->days) ? (int) ($r->total_days ?? $r->days).' day(s)' : '—');
                    @endphp
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $r->leave_type }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            <span>{{ $r->start_date }}@if($startT) {{ $startT }}@endif</span>
                            <span class="text-slate-400"> → </span>
                            <span>{{ $r->end_date }}@if($endT) {{ $endT }}@endif</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-[#c2410c]">{{ $hoursLabel }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusClass = match (strtolower($r->status ?? '')) {
                                    'approved' => 'bg-emerald-100 text-emerald-800',
                                    'rejected', 'declined' => 'bg-red-100 text-red-800',
                                    default => 'bg-amber-100 text-amber-800',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ $r->status }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-slate-500">No leave requests yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function () {
    var HOURS_PER_DAY = 8;
    var $startDate = $('#start_date');
    var $endDate = $('#end_date');
    var $startTime = $('#start_time');
    var $endTime = $('#end_time');
    var $value = $('#leaveHoursValue');
    var $hint = $('#leaveHoursHint');
    var $error = $('#leaveHoursError');
    var $submit = $('#leaveSubmitBtn');

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function parseLocalDateTime(dateStr, timeStr) {
        if (!dateStr || !timeStr) return null;
        var parts = dateStr.split('-').map(Number);
        var tp = timeStr.split(':').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2], tp[0], tp[1] || 0, 0);
    }

    function formatLabel(hours, dayEquiv, calendarDays) {
        var h = Math.floor(hours);
        var m = Math.round((hours - h) * 60);
        var timePart = m > 0 ? h + ' hr ' + m + ' min' : h + ' hr' + (h === 1 ? '' : 's');
        var equiv = dayEquiv === 1 ? '1 day' : dayEquiv + ' days';
        if (calendarDays > 1) {
            return timePart + ' (' + equiv + ' at ' + HOURS_PER_DAY + ' hrs/day · spans ' + calendarDays + ' calendar days)';
        }
        return timePart + ' (' + equiv + ' at ' + HOURS_PER_DAY + ' hrs/day)';
    }

    function updateLeaveHours() {
        $error.addClass('hidden').text('');
        var sd = $startDate.val();
        var ed = $endDate.val();
        var st = $startTime.val();
        var et = $endTime.val();

        if (!sd || !ed || !st || !et) {
            $value.text('—');
            $hint.text('Select dates and times to see total hours.');
            $submit.prop('disabled', false);
            return;
        }

        if (ed < sd) {
            $value.text('—');
            $error.removeClass('hidden').text('Return date must be on or after start date.');
            $submit.prop('disabled', true);
            return;
        }

        var start = parseLocalDateTime(sd, st);
        var end = parseLocalDateTime(ed, et);

        if (!start || !end || end <= start) {
            $value.text('—');
            $error.removeClass('hidden').text('Return date & time must be after start date & time.');
            $submit.prop('disabled', true);
            return;
        }

        var minutes = Math.round((end - start) / 60000);
        var startDay = new Date(start.getFullYear(), start.getMonth(), start.getDate());
        var returnDay = new Date(end.getFullYear(), end.getMonth(), end.getDate());
        var calendarSpan = Math.round((returnDay - startDay) / 86400000);
        var calendarDays = calendarSpan + 1;
        var hours;
        if (calendarSpan === 0) {
            hours = Math.round(minutes / 60 * 100) / 100;
            hours = Math.min(HOURS_PER_DAY, hours);
        } else {
            hours = Math.round(calendarSpan * HOURS_PER_DAY * 100) / 100;
        }
        var dayEquiv = Math.max(1, Math.ceil(hours / HOURS_PER_DAY));

        $value.text(hours.toFixed(2) + ' hours');
        $hint.text(formatLabel(hours, dayEquiv, calendarDays));
        $submit.prop('disabled', false);
    }

    $('#leaveForm').on('input change', '#start_date, #end_date, #start_time, #end_time', updateLeaveHours);

    $('#leaveForm').on('submit', function(e) {
        e.preventDefault();
        if ($submit.prop('disabled')) return;
        var base = $('meta[name=app-url]').attr('content').replace(/\/$/, '');
        $.ajax({
            url: base + '/employee/leaves',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content') },
            success: function(r) { alert(r.message); if (r.status === 'success') location.reload(); },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message;
                if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                alert(msg || 'Error');
            }
        });
    });

    $startDate.on('change', function () {
        if (!$endDate.val() || $endDate.val() < $(this).val()) {
            $endDate.val($(this).val());
        }
    });

    updateLeaveHours();
})();
</script>
@endpush
