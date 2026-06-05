document.addEventListener('DOMContentLoaded', function () {
    const statusSelect = document.getElementById('status');
    const inactiveFields = document.getElementById('inactiveEmploymentFields');
    const dateInactiveInput = document.getElementById('date_inactive');
    const resignationInput = document.getElementById('resignation_letter');
    const form = document.getElementById('employeeForm');
    const overlay = document.getElementById('submitLoadingOverlay');
    const hasExistingResignation = window.hrStaffEdit?.hasExistingResignation === true;

    function syncInactiveFields() {
        if (!statusSelect || !inactiveFields) return;
        inactiveFields.classList.toggle('hidden', statusSelect.value !== 'Inactive');
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', syncInactiveFields);
        syncInactiveFields();
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (statusSelect && statusSelect.value === 'Inactive') {
                if (dateInactiveInput && !dateInactiveInput.value) {
                    e.preventDefault();
                    alert('Please set the date inactive.');
                    dateInactiveInput.focus();
                    return;
                }
                if (resignationInput && !resignationInput.files.length && !hasExistingResignation) {
                    e.preventDefault();
                    alert('Please attach the resignation letter.');
                    resignationInput.focus();
                    return;
                }
            }
            if (overlay) {
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
            }
        });
    }
});
