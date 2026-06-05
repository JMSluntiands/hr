@extends('layouts.admin')
@section('title', 'Employment Type')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Employment Type</h1>
    <p class="text-sm text-slate-500 mt-1">Manage master list of employment types (e.g., Regular Employee, Contractor)</p>
</div>

<section class="mb-6 bg-white rounded-xl shadow-sm border border-slate-100 p-5">
    <h2 class="text-sm font-semibold text-slate-700 mb-3">Add employment type</h2>
    <form method="POST" action="{{ route('admin.employment-types.store') }}" class="flex flex-col md:flex-row gap-3 items-stretch md:items-end">@csrf
        <div class="flex-1">
            <label class="block text-xs font-medium text-slate-600 mb-1">Employment type name</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Enter employment type name"
                   class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
        </div>
        <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-[#FA9800] hover:bg-[#e8870a] text-white text-sm font-medium shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add
        </button>
    </form>
</section>

<section class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-5 py-4 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-700">Employment type list</h2>
    </div>
    <div class="p-5 overflow-x-auto">
        <table id="employmentTypeTable" class="min-w-full text-sm w-full">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Name</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Created</th>
                    <th class="text-right px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $type)
                <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                    <td class="px-4 py-3 text-slate-700 font-medium">{{ $type->name }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $type->created_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" class="edit-type-btn px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium"
                                data-id="{{ $type->id }}" data-name="{{ $type->name }}">Edit</button>
                            <form method="POST" action="{{ route('admin.employment-types.destroy', $type->id) }}" class="inline"
                                  onsubmit="return confirm('Delete this employment type? This cannot be undone.');">@csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-12 text-center text-slate-500">No employment types found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('modals')
<div id="editTypeModal" class="hidden fixed inset-0 z-[200] items-center justify-center bg-black/50 p-4" aria-modal="true">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-800">Edit employment type</h3>
            <button type="button" id="closeEditTypeModal" class="p-1 rounded-md hover:bg-slate-100 text-slate-600 text-xl leading-none">&times;</button>
        </div>
        <form method="POST" id="editTypeForm" class="px-5 py-4 space-y-4">@csrf
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Employment type name</label>
                <input type="text" name="name" id="editTypeName" required
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" id="cancelEditType" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-[#FA9800] hover:bg-[#e8870a] text-white">Save changes</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $('#employmentTypeTable tbody tr').length && !$('#employmentTypeTable tbody tr td[colspan]').length) {
        $('#employmentTypeTable').DataTable({
            pageLength: 10,
            order: [[0, 'asc']],
            language: { search: '', searchPlaceholder: 'Search employment types…', emptyTable: 'No employment types found.' },
        });
    }

    const modal = document.getElementById('editTypeModal');
    const form = document.getElementById('editTypeForm');
    const openModal = () => { modal?.classList.remove('hidden'); modal?.classList.add('flex'); };
    const closeModal = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };

    document.querySelectorAll('.edit-type-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            if (form && id) {
                form.action = @json(url('/admin/employment-types')) + '/' + id;
                document.getElementById('editTypeName').value = this.dataset.name || '';
                openModal();
            }
        });
    });
    document.getElementById('closeEditTypeModal')?.addEventListener('click', closeModal);
    document.getElementById('cancelEditType')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
});
</script>
@endpush
