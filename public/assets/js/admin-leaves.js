(function () {
    function statusClass(status) {
        if (status === 'Approved') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        if (status === 'Rejected') return 'bg-red-100 text-red-700 border-red-200';
        if (status === 'Cancelled') return 'bg-slate-200 text-slate-700 border-slate-200';
        return 'bg-amber-100 text-amber-700 border-amber-200';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function readLeaveData(el) {
        const d = el.dataset;
        return {
            employee: d.employee || '—',
            type: d.type || '—',
            start: d.start || '—',
            end: d.end || '—',
            days: d.days || '0',
            reason: d.reason || '',
            status: d.status || 'Pending',
            approved: d.approved || '—',
            approvedAt: d.approvedAt || '',
            created: d.created || '—',
            rejection: d.rejection || '',
            cancellation: d.cancellation || '',
        };
    }

    function buildViewHtml(data) {
        const days = parseInt(data.days, 10) || 0;
        const status = data.status;
        const sc = statusClass(status);

        let historyHtml = '<div class="border-t border-slate-200 pt-4 mt-2"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">History</p><ul class="space-y-3">';
        historyHtml += '<li class="flex gap-3"><span class="mt-1.5 h-2 w-2 rounded-full bg-blue-400 shrink-0"></span><div><p class="font-medium text-slate-800">Submitted</p><p class="text-slate-500 text-xs">' + escapeHtml(data.created) + '</p></div></li>';

        if (status === 'Approved') {
            historyHtml += '<li class="flex gap-3"><span class="mt-1.5 h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span><div><p class="font-medium text-slate-800">Approved by ' + escapeHtml(data.approved) + '</p><p class="text-slate-500 text-xs">' + escapeHtml(data.approvedAt || '—') + '</p></div></li>';
        }
        if (status === 'Rejected') {
            historyHtml += '<li class="flex gap-3"><span class="mt-1.5 h-2 w-2 rounded-full bg-red-500 shrink-0"></span><div><p class="font-medium text-slate-800">Declined</p><p class="text-slate-600 text-xs">' + escapeHtml(data.rejection || '—') + '</p></div></li>';
        }
        if (status === 'Cancelled') {
            historyHtml += '<li class="flex gap-3"><span class="mt-1.5 h-2 w-2 rounded-full bg-slate-500 shrink-0"></span><div><p class="font-medium text-slate-800">Cancelled</p><p class="text-slate-600 text-xs">' + escapeHtml(data.cancellation || '—') + '</p></div></li>';
        }
        if (status === 'Pending') {
            historyHtml += '<li class="flex gap-3"><span class="mt-1.5 h-2 w-2 rounded-full bg-amber-400 shrink-0"></span><div><p class="font-medium text-amber-800">Awaiting approval</p></div></li>';
        }
        historyHtml += '</ul></div>';

        return (
            '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">' +
            '<div class="bg-slate-50 rounded-lg p-3 border"><p class="text-xs text-slate-500 uppercase mb-1">Employee</p><p class="font-semibold">' + escapeHtml(data.employee) + '</p></div>' +
            '<div class="bg-slate-50 rounded-lg p-3 border"><p class="text-xs text-slate-500 uppercase mb-1">Leave type</p><p class="font-semibold">' + escapeHtml(data.type) + '</p></div>' +
            '</div>' +
            '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">' +
            '<div class="bg-blue-50 rounded-lg p-3 border border-blue-100"><p class="text-xs text-blue-600 uppercase mb-1">Start</p><p class="font-semibold text-blue-900">' + escapeHtml(data.start) + '</p></div>' +
            '<div class="bg-blue-50 rounded-lg p-3 border border-blue-100"><p class="text-xs text-blue-600 uppercase mb-1">Return</p><p class="font-semibold text-blue-900">' + escapeHtml(data.end) + '</p></div>' +
            '</div>' +
            '<div class="bg-purple-50 rounded-lg p-3 border border-purple-100"><p class="text-xs text-purple-600 uppercase mb-1">Total days</p><p class="text-xl font-bold text-purple-900">' + days + ' day' + (days !== 1 ? 's' : '') + '</p></div>' +
            '<div class="bg-slate-50 rounded-lg p-3 border"><p class="text-xs text-slate-500 uppercase mb-1">Remarks</p><p class="text-slate-700 leading-relaxed">' + escapeHtml(data.reason || '—') + '</p></div>' +
            '<div class="flex flex-wrap gap-2 items-center"><span class="text-xs text-slate-500 uppercase">Status</span><span class="inline-flex px-3 py-1 rounded-full text-xs font-medium border ' + sc + '">' + escapeHtml(status) + '</span></div>' +
            historyHtml
        );
    }

    function openViewModal(data) {
        const body = document.getElementById('viewLeaveModalBody');
        const modal = document.getElementById('viewLeaveModal');
        if (!body || !modal) return;
        body.innerHTML = buildViewHtml(data);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeViewModal() {
        const modal = document.getElementById('viewLeaveModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function openDeclineModal(id) {
        const form = document.getElementById('declineLeaveForm');
        const modal = document.getElementById('declineLeaveModal');
        if (!form || !modal || !id || !window.hrLeaveRoutes) return;
        form.action = window.hrLeaveRoutes.decline + '/' + id + (window.hrLeaveRoutes.declineSuffix || '/decline');
        const ta = form.querySelector('textarea');
        if (ta) ta.value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDeclineModal() {
        const modal = document.getElementById('declineLeaveModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function leavePayloadFromButton(btn) {
        if (btn.dataset.employee) {
            return readLeaveData(btn);
        }
        const tr = btn.closest('tr.leave-row');
        return tr ? readLeaveData(tr) : null;
    }

    document.addEventListener('click', function (e) {
        const viewBtn = e.target.closest('.view-leave-btn');
        if (viewBtn) {
            e.preventDefault();
            e.stopPropagation();
            const data = leavePayloadFromButton(viewBtn);
            if (data) openViewModal(data);
            return;
        }

        const declineBtn = e.target.closest('.decline-leave-btn');
        if (declineBtn) {
            e.preventDefault();
            openDeclineModal(declineBtn.dataset.id);
        }
    });

    document.getElementById('closeViewLeaveModal')?.addEventListener('click', closeViewModal);
    document.getElementById('viewLeaveModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeViewModal();
    });

    document.getElementById('cancelDeclineLeave')?.addEventListener('click', closeDeclineModal);
    document.getElementById('declineLeaveModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeDeclineModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeViewModal();
            closeDeclineModal();
        }
    });

    window.hrOpenLeaveViewModal = openViewModal;
})();
