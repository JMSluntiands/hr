@extends('layouts.employee')
@section('title', 'My Reimbursement')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">My Reimbursement</h1>
        <p class="text-sm text-slate-500">Submit and track expense reimbursements.</p>
    </div>
    <button type="button" onclick="document.getElementById('reimModal').classList.remove('hidden')" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg text-sm font-medium">New Request</button>
</div>
<div class="bg-white rounded-xl border overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50"><tr>
            <th class="px-4 py-3 text-left text-xs uppercase">Date</th>
            <th class="px-4 py-3 text-left text-xs uppercase">Type</th>
            <th class="px-4 py-3 text-left text-xs uppercase">Amount</th>
            <th class="px-4 py-3 text-left text-xs uppercase">Status</th>
        </tr></thead>
        <tbody class="divide-y">
            @foreach($rows as $r)
            <tr>
                <td class="px-4 py-3">{{ $r->created_at }}</td>
                <td class="px-4 py-3">{{ $r->expense_type }}</td>
                <td class="px-4 py-3">₱{{ number_format((float)$r->amount, 2) }}</td>
                <td class="px-4 py-3">{{ $r->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div id="reimModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <form id="reimForm" enctype="multipart/form-data" class="bg-white rounded-xl p-6 max-w-lg w-full space-y-3 max-h-[90vh] overflow-y-auto">
        @csrf
        <h2 class="font-semibold text-lg">New reimbursement</h2>
        <input type="text" name="expense_type" placeholder="Expense type" required class="w-full border rounded-lg px-3 py-2">
        <textarea name="expense_description" placeholder="Description" required rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
        <input type="date" name="purchased_date" required class="w-full border rounded-lg px-3 py-2">
        <input type="number" name="amount" step="0.01" min="0.01" placeholder="Amount" required class="w-full border rounded-lg px-3 py-2">
        <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm">
        <div class="flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('reimModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg">Submit</button>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>
$('#reimForm').on('submit', function(e) {
    e.preventDefault();
    var base = $('meta[name=app-url]').attr('content').replace(/\/$/, '');
    var fd = new FormData(this);
    $.ajax({
        url: base + '/employee/reimbursements',
        method: 'POST',
        data: fd, processData: false, contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content') },
        success: function(r) { alert(r.message); if (r.status === 'success') location.reload(); }
    });
});
</script>
@endpush
