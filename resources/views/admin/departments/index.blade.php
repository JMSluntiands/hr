@extends('layouts.admin')
@section('title', 'Departments')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Departments</h1>
    <p class="text-sm text-slate-500 mt-1">Manage master list of departments used in employees</p>
</div>

<section class="mb-6 bg-white rounded-xl shadow-sm border border-slate-100 p-5">
    <h2 class="text-sm font-semibold text-slate-700 mb-3">Add department</h2>
    <form method="POST" action="{{ route('admin.departments.store') }}" class="flex flex-col md:flex-row gap-3 items-stretch md:items-end flex-wrap">@csrf
        <div class="flex-1 min-w-[12rem]">
            <label class="block text-xs font-medium text-slate-600 mb-1">Department name</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Enter department name"
                   class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
        </div>
        @if($hasPerfReviewCol)
        <label class="flex items-center gap-2 shrink-0 pb-0.5 md:self-end cursor-pointer">
            <input type="checkbox" name="additional_performance_review" value="1" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
            <span class="text-xs text-slate-600 whitespace-nowrap">Additional performance review</span>
        </label>
        @endif
        <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-[#FA9800] hover:bg-[#e8870a] text-white text-sm font-medium shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add
        </button>
    </form>
</section>

<section class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-5 py-4 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-700">Department list</h2>
    </div>
    <div class="p-5 overflow-x-auto">
        <table id="departmentTable" class="min-w-full text-sm w-full">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Name</th>
                    @if($hasPerfReviewCol)
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Performance form</th>
                    @endif
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Created</th>
                    <th class="text-right px-4 py-2 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                    <td class="px-4 py-3 text-slate-700 font-medium">{{ $dept->name }}</td>
                    @if($hasPerfReviewCol)
                    <td class="px-4 py-3 text-sm">
                        @if($dept->additional_performance_review)
                        <span class="text-emerald-600 font-medium">On</span>
                        @else
                        <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    @endif
                    <td class="px-4 py-3 text-slate-500">{{ $dept->created_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" class="edit-dept-btn px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium"
                                data-id="{{ $dept->id }}" data-name="{{ $dept->name }}"
                                @if($hasPerfReviewCol) data-perf="{{ $dept->additional_performance_review ? '1' : '0' }}" @endif>
                                Edit
                            </button>
                            <form method="POST" action="{{ route('admin.departments.destroy', $dept->id) }}" class="inline"
                                  onsubmit="return confirm('Delete this department? This cannot be undone.');">@csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ $hasPerfReviewCol ? 4 : 3 }}" class="px-4 py-12 text-center text-slate-500">No departments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('modals')
<div id="editDeptModal" class="hidden fixed inset-0 z-[200] items-center justify-center bg-black/50 p-4" aria-modal="true">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-800">Edit department</h3>
            <button type="button" id="closeEditDeptModal" class="p-1 rounded-md hover:bg-slate-100 text-slate-600 text-xl leading-none">&times;</button>
        </div>
        <form method="POST" id="editDeptForm" class="px-5 py-4 space-y-4">@csrf
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Department name</label>
                <input type="text" name="name" id="editDeptName" required
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
            </div>
            @if($hasPerfReviewCol)
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="additional_performance_review" id="editDeptPerf" value="1" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                <span class="text-xs text-slate-600">Additional performance review</span>
            </label>
            @endif
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" id="cancelEditDept" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
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
    if (window.jQuery && $('#departmentTable tbody tr').length && !$('#departmentTable tbody tr td[colspan]').length) {
        $('#departmentTable').DataTable({
            pageLength: 10,
            order: [[0, 'asc']],
            language: { search: '', searchPlaceholder: 'Search departments…', emptyTable: 'No departments found.' },
        });
    }

    const modal = document.getElementById('editDeptModal');
    const form = document.getElementById('editDeptForm');
    const perfInput = document.getElementById('editDeptPerf');
    const openModal = () => { modal?.classList.remove('hidden'); modal?.classList.add('flex'); };
    const closeModal = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };

    document.querySelectorAll('.edit-dept-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            if (form && id) {
                form.action = @json(url('/admin/departments')) + '/' + id;
                document.getElementById('editDeptName').value = this.dataset.name || '';
                if (perfInput) perfInput.checked = this.dataset.perf === '1';
                openModal();
            }
        });
    });
    document.getElementById('closeEditDeptModal')?.addEventListener('click', closeModal);
    document.getElementById('cancelEditDept')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
});
</script>
@endpush
