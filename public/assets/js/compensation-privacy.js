/**
 * Compensation / salary sections: hidden by default, revealed via View button.
 */
(function () {
    'use strict';

    function setSectionState(btn, showContent) {
        var targetId = btn.getAttribute('data-target');
        var placeholderId = btn.getAttribute('data-placeholder');
        if (!targetId) return;

        var target = document.getElementById(targetId);
        var placeholder = placeholderId ? document.getElementById(placeholderId) : null;
        if (!target) return;

        target.classList.toggle('hidden', !showContent);
        if (placeholder) {
            placeholder.classList.toggle('hidden', showContent);
        }

        var label = btn.querySelector('.js-comp-privacy-label');
        if (label) {
            label.textContent = showContent ? 'Hide' : 'View';
        }
        btn.setAttribute('aria-label', showContent ? 'Hide details' : 'View details');

        var eyeOpen = btn.querySelector('.js-eye-open');
        var eyeClosed = btn.querySelector('.js-eye-closed');
        if (eyeOpen && eyeClosed) {
            eyeOpen.classList.toggle('hidden', showContent);
            eyeClosed.classList.toggle('hidden', !showContent);
        }
    }

    function init() {
        document.querySelectorAll('.js-comp-privacy-view').forEach(function (btn) {
            if (btn.dataset.compPrivacyReady === '1') return;
            btn.dataset.compPrivacyReady = '1';
            setSectionState(btn, false);

            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                var target = targetId ? document.getElementById(targetId) : null;
                var isHidden = target ? target.classList.contains('hidden') : true;
                setSectionState(btn, isHidden);
                if (isHidden && target) {
                    target.dispatchEvent(new CustomEvent('comp-privacy-shown', { bubbles: true }));
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
