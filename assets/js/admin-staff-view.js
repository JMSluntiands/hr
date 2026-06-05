document.addEventListener('DOMContentLoaded', function () {
    const viewAdjustmentsBtn = document.getElementById('viewAdjustmentsBtn');
    const adjustmentsModal = document.getElementById('adjustmentsModal');
    const closeAdjustmentsModal = document.getElementById('closeAdjustmentsModal');

    if (viewAdjustmentsBtn && adjustmentsModal) {
        viewAdjustmentsBtn.addEventListener('click', function () {
            adjustmentsModal.classList.remove('hidden');
            adjustmentsModal.classList.add('flex');
        });
    }

    function closeModal() {
        if (!adjustmentsModal) return;
        adjustmentsModal.classList.remove('flex');
        adjustmentsModal.classList.add('hidden');
    }

    if (closeAdjustmentsModal) {
        closeAdjustmentsModal.addEventListener('click', closeModal);
    }

    if (adjustmentsModal) {
        adjustmentsModal.addEventListener('click', function (e) {
            if (e.target === adjustmentsModal) {
                closeModal();
            }
        });
    }
});
