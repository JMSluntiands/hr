@extends('layouts.admin')
@section('title', 'Accounts')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Accounts</h1>
    <p class="text-sm text-slate-500 mt-1">Create or manage employee login accounts</p>
</div>

@if($schemaMessage)
<div class="mb-6 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm">{{ $schemaMessage }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="p-6 overflow-x-auto">
        <table id="accountsTable" class="min-w-full text-sm w-full">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Email</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Last change password</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Role</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $a)
                <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                    <td class="px-4 py-3 text-slate-700">{{ $a->email }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        @if(!$a->has_account)
                            Not created
                        @elseif($a->has_last_change_column && $a->last_password_change)
                            {{ \Carbon\Carbon::parse($a->last_password_change)->format('M d, Y H:i') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($a->has_account)
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ strtolower($a->role) === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">{{ $a->role }}</span>
                        @else
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">no account</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @if(!$a->has_account)
                            <form method="POST" action="{{ route('admin.accounts.create-employee') }}" class="inline" onsubmit="return confirm('Create employee account and generate random password now?');">@csrf
                                <input type="hidden" name="employee_id" value="{{ $a->employee_id }}">
                                <x-hr-btn type="submit" variant="approve">Create account</x-hr-btn>
                            </form>
                            @else
                            @if(strtolower($a->role) === 'employee')
                            <form method="POST" action="{{ route('admin.accounts.reset-password', $a->id) }}" class="inline" onsubmit="return confirm('Generate a new random password for this employee account?');">@csrf
                                <x-hr-btn type="submit" variant="secondary">Reset password</x-hr-btn>
                            </form>
                            @endif
                            <x-hr-btn type="button" variant="primary" class="edit-role-btn"
                                data-id="{{ $a->id }}" data-email="{{ $a->email }}" data-role="{{ strtolower($a->role) }}">Edit role</x-hr-btn>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-12 text-center text-slate-500">No accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('modals')
<div id="editRoleModal" class="hidden fixed inset-0 z-[200] items-center justify-center bg-black/50 p-4" aria-modal="true">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-2">Edit role</h3>
        <p class="text-sm text-slate-500 mb-4" id="editRoleEmail"></p>
        <form method="POST" id="editRoleForm" class="space-y-4">@csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Role</label>
                <select name="role" id="editRoleSelect" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
                    <option value="admin">Admin</option>
                    <option value="employee">Employee</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="editRoleCancel" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#e8870a]">Save</button>
            </div>
        </form>
    </div>
</div>

@if($generatedPassword)
<div id="generatedPasswordModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 p-4" aria-modal="true">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-2">{{ $generatedMode === 'created' ? 'Account created' : 'Password reset' }}</h3>
        <p class="text-sm text-slate-500 mb-4">Copy this password now and give it to the employee.</p>
        <div class="mb-3">
            <p class="text-xs text-slate-500 mb-1">Email</p>
            <p class="text-sm font-medium text-slate-800">{{ $generatedEmail }}</p>
        </div>
        <div class="mb-5">
            <p class="text-xs text-slate-500 mb-1">Generated password</p>
            <div class="flex items-center gap-2">
                <input id="generatedPasswordField" type="text" readonly value="{{ $generatedPassword }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                <button type="button" id="copyGeneratedPasswordBtn" class="px-3 py-2 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Copy</button>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="button" id="generatedPasswordClose" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#e8870a]">Done</button>
        </div>
    </div>
</div>
@endif
@endpush

@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
$(function () {
    if ($('#accountsTable tbody tr').length && !$('#accountsTable tbody tr td[colspan]').length) {
        $('#accountsTable').DataTable({
            pageLength: 10,
            order: [[0, 'asc']],
            language: { search: '', searchPlaceholder: 'Search email…', emptyTable: 'No accounts found.' },
        });
    }

    const modal = document.getElementById('editRoleModal');
    const form = document.getElementById('editRoleForm');
    const openModal = () => { modal?.classList.remove('hidden'); modal?.classList.add('flex'); };
    const closeModal = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };

    document.querySelectorAll('.edit-role-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            if (form && id) {
                form.action = @json(url('/admin/accounts')) + '/' + id + '/role';
                document.getElementById('editRoleEmail').textContent = this.dataset.email || '';
                document.getElementById('editRoleSelect').value = (this.dataset.role || 'employee').toLowerCase();
                openModal();
            }
        });
    });
    document.getElementById('editRoleCancel')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    document.getElementById('generatedPasswordClose')?.addEventListener('click', function () {
        document.getElementById('generatedPasswordModal')?.classList.add('hidden');
    });
    document.getElementById('copyGeneratedPasswordBtn')?.addEventListener('click', function () {
        const field = document.getElementById('generatedPasswordField');
        if (!field) return;
        field.select();
        navigator.clipboard?.writeText(field.value);
        this.textContent = 'Copied';
        setTimeout(() => { this.textContent = 'Copy'; }, 1200);
    });
});
</script>
@endpush
