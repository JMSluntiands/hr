@extends('layouts.employee')
@section('title', 'My Reimbursement')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">My Reimbursement</h1>
        <p class="text-sm text-slate-500">Fill up reimbursement request form and track status.</p>
    </div>
    <button type="button" id="openModalBtn" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg text-sm font-medium hover:bg-[#d18a15]">New Request</button>
</div>
<div class="bg-white rounded-xl border overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50"><tr>
            <th class="px-4 py-3 text-left text-xs uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs uppercase">Expense Type</th>
            <th class="px-4 py-3 text-left text-xs uppercase">Amount</th>
            <th class="px-4 py-3 text-left text-xs uppercase">Status</th>
            <th class="px-4 py-3 text-left text-xs uppercase">Reason (if declined)</th>
        </tr></thead>
        <tbody class="divide-y">
            @forelse($rows as $r)
            @php
                $status = (string) $r->status;
                $hasAdminReceipt = !empty($r->admin_receipt_path);
                $displayStatus = 'For Review';
                $badge = 'bg-amber-100 text-amber-700';
                if ($status === 'Rejected') {
                    $displayStatus = 'Declined';
                    $badge = 'bg-red-100 text-red-700';
                } elseif ($status === 'Approved' && !$hasAdminReceipt) {
                    $displayStatus = 'For Reimburse';
                    $badge = 'bg-blue-100 text-blue-700';
                } elseif ($status === 'Approved' && $hasAdminReceipt) {
                    $displayStatus = 'Completed';
                    $badge = 'bg-emerald-100 text-emerald-700';
                }
            @endphp
            <tr>
                <td class="px-4 py-3">{{ \App\Support\HrDateTime::formatDate($r->created_at) }}</td>
                <td class="px-4 py-3">{{ $r->expense_type }}</td>
                <td class="px-4 py-3">PHP {{ number_format((float)$r->amount, 2) }}</td>
                <td class="px-4 py-3"><span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ $displayStatus }}</span></td>
                <td class="px-4 py-3 text-slate-600">{{ $r->rejection_reason ?? '' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No reimbursement requests yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="formModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">Reimbursement Request Form</h2>
            <button id="closeModalBtn" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form id="reimbursementForm" class="p-6 space-y-4" enctype="multipart/form-data">
            @csrf
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <input type="checkbox" id="bulkToggle" name="is_bulk" value="1" class="h-4 w-4 rounded border-slate-300 text-[#FA9800] focus:ring-[#FA9800]/30 cursor-pointer">
                <label for="bulkToggle" class="text-sm font-medium text-slate-700 cursor-pointer select-none">Bulk Reimbursement <span class="text-xs text-slate-500 font-normal">(submit multiple items in one request)</span></label>
            </div>

            <div id="singleFields">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Expense Type <span class="text-red-500">*</span></label>
                    <select name="expense_type" id="singleExpenseType" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                        <option value="">Select Expense Type</option>
                        <option>Office Supplies</option>
                        <option>Travel</option>
                        <option>Training Materials</option>
                        <option>Equipment</option>
                        <option>Birthday Treat</option>
                        <option>Meal Treat</option>
                        <option>Miscellaneous</option>
                    </select>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Expense Description <span class="text-red-500">*</span></label>
                    <textarea name="expense_description" id="singleDescription" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Purchased Date <span class="text-red-500">*</span></label>
                        <input type="date" name="purchased_date" id="singleDate" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Amount (PHP) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="singleAmount" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Upload Receipt / Photo</label>
                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm">
                </div>
            </div>

            <div id="bulkFields" class="hidden space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-700">Items</p>
                    <button type="button" id="addBulkRow" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg bg-[#FA9800] text-white hover:bg-[#d18a15]">+ Add Item</button>
                </div>
                <div id="bulkContainer" class="space-y-3"></div>
                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 mt-2">
                    <span class="text-sm font-semibold text-slate-700">Total Amount</span>
                    <span class="text-sm font-bold text-slate-800" id="bulkTotal">PHP 0.00</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Notes (optional, applies to all)</label>
                    <textarea name="bulk_notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" id="cancelModalBtn" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg">Cancel</button>
                <button type="submit" id="submitBtn" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#d18a15]">Submit</button>
            </div>
            <div id="formMessage" class="hidden text-sm rounded-lg px-3 py-2"></div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function () {
    var bulkRowIndex = 0;
    var expenseOptions = '<option value="">Select</option><option>Office Supplies</option><option>Travel</option><option>Training Materials</option><option>Equipment</option><option>Birthday Treat</option><option>Meal Treat</option><option>Miscellaneous</option>';

    function makeBulkRow() {
        var i = bulkRowIndex++;
        return '<div class="bulk-row rounded-lg border border-slate-200 bg-white p-4 relative" data-idx="' + i + '">' +
            '<button type="button" class="removeBulkRow absolute top-2 right-2 text-slate-400 hover:text-red-500 text-lg leading-none" title="Remove">&times;</button>' +
            '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">' +
                '<div><label class="block text-xs font-medium text-slate-600 mb-1">Expense Type <span class="text-red-500">*</span></label>' +
                '<select name="bulk_expense_type[]" required class="w-full px-2 py-1.5 text-sm border border-slate-300 rounded-lg">' + expenseOptions + '</select></div>' +
                '<div><label class="block text-xs font-medium text-slate-600 mb-1">Purchased Date <span class="text-red-500">*</span></label>' +
                '<input type="date" name="bulk_purchased_date[]" required class="w-full px-2 py-1.5 text-sm border border-slate-300 rounded-lg"></div>' +
                '<div><label class="block text-xs font-medium text-slate-600 mb-1">Amount (PHP) <span class="text-red-500">*</span></label>' +
                '<input type="number" step="0.01" min="0.01" name="bulk_amount[]" required class="bulk-amount w-full px-2 py-1.5 text-sm border border-slate-300 rounded-lg"></div>' +
            '</div>' +
            '<div class="mt-3"><label class="block text-xs font-medium text-slate-600 mb-1">Expense Description <span class="text-red-500">*</span></label>' +
            '<input type="text" name="bulk_expense_description[]" required placeholder="Brief description" class="w-full px-2 py-1.5 text-sm border border-slate-300 rounded-lg"></div>' +
            '<div class="mt-3"><label class="block text-xs font-medium text-slate-600 mb-1">Receipt (optional)</label>' +
            '<input type="file" name="bulk_receipt_' + i + '" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-xs"></div>' +
        '</div>';
    }

    function recalcTotal() {
        var total = 0;
        $('#bulkContainer .bulk-amount').each(function () {
            var v = parseFloat($(this).val());
            if (!isNaN(v) && v > 0) total += v;
        });
        $('#bulkTotal').text('PHP ' + total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    $('#bulkToggle').on('change', function () {
        var isBulk = this.checked;
        if (isBulk) {
            $('#singleFields').addClass('hidden').find('select, input, textarea').prop('required', false);
            $('#bulkFields').removeClass('hidden');
            if ($('#bulkContainer .bulk-row').length === 0) {
                $('#bulkContainer').append(makeBulkRow()).append(makeBulkRow());
            }
        } else {
            $('#bulkFields').addClass('hidden').find('select, input, textarea').prop('required', false);
            $('#singleFields').removeClass('hidden');
            $('#singleExpenseType, #singleDescription, #singleDate, #singleAmount').prop('required', true);
        }
    });

    $('#addBulkRow').on('click', function () { $('#bulkContainer').append(makeBulkRow()); });
    $(document).on('click', '.removeBulkRow', function () {
        if ($('#bulkContainer .bulk-row').length > 1) { $(this).closest('.bulk-row').remove(); recalcTotal(); }
    });
    $(document).on('input change', '.bulk-amount', recalcTotal);

    function hideModal() {
        $('#formModal').removeClass('flex').addClass('hidden');
        $('#reimbursementForm')[0].reset();
        $('#bulkToggle').prop('checked', false).trigger('change');
        $('#bulkContainer').empty();
        bulkRowIndex = 0;
        recalcTotal();
        $('#formMessage').addClass('hidden').text('');
    }

    $('#openModalBtn').on('click', function () { $('#formModal').removeClass('hidden').addClass('flex'); });
    $('#closeModalBtn, #cancelModalBtn').on('click', hideModal);
    $('#formModal').on('mousedown', function (e) {
        $(this).data('backdropMousedown', e.target.id === 'formModal');
    }).on('mouseup', function (e) {
        if (e.target.id === 'formModal' && $(this).data('backdropMousedown')) hideModal();
        $(this).data('backdropMousedown', false);
    });

    $('#reimbursementForm').on('submit', function (e) {
        e.preventDefault();
        var base = $('meta[name=app-url]').attr('content').replace(/\/$/, '');
        var fd = new FormData(this);
        var isBulk = $('#bulkToggle').is(':checked');

        if (isBulk) {
            $('#bulkContainer input[type="file"]').each(function () {
                var name = $(this).attr('name');
                if (this.files && this.files[0]) fd.append(name, this.files[0]);
            });
        }

        $('#submitBtn').prop('disabled', true).text('Submitting...');
        $('#formMessage').addClass('hidden').removeClass('bg-red-50 text-red-700 bg-emerald-50 text-emerald-700').text('');

        $.ajax({
            url: base + '/employee/reimbursements',
            method: 'POST',
            data: fd, processData: false, contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content') },
            success: function (res) {
                $('#submitBtn').prop('disabled', false).text('Submit');
                if (res.status === 'success') {
                    $('#formMessage').removeClass('hidden').addClass('bg-emerald-50 text-emerald-700').text(res.message || 'Submitted.');
                    setTimeout(function () { location.reload(); }, 1000);
                } else {
                    $('#formMessage').removeClass('hidden').addClass('bg-red-50 text-red-700').text(res.message || 'Submission failed.');
                }
            },
            error: function () {
                $('#submitBtn').prop('disabled', false).text('Submit');
                $('#formMessage').removeClass('hidden').addClass('bg-red-50 text-red-700').text('Submission failed. Please try again.');
            }
        });
    });
});
</script>
@endpush
