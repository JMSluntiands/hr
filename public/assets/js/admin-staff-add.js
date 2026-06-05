document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-hr-digits-max]').forEach(function (input) {
        var max = parseInt(input.getAttribute('data-hr-digits-max'), 10) || 9;
        input.addEventListener('input', function () {
            input.value = input.value.replace(/[^0-9]/g, '').slice(0, max);
        });
    });

    const form = document.getElementById('employeeForm');
    const phoneInput = document.getElementById('phone');
    const dailyCompInput = document.getElementById('basic_salary_daily');
    const monthlyGrossPreview = document.getElementById('basic_salary_monthly_preview');
    const annualGrossPreview = document.getElementById('basic_salary_annual_preview');
    const primaryAddressInput = document.getElementById('address');
    const emergencySameAsPrimary = document.getElementById('emergencySameAsPrimary');
    const emergencyAddressWrap = document.getElementById('emergencyAddressWrap');
    const emergencyAddressInput = document.getElementById('emergency_contact_address');

    function syncEmergencyAddressMode() {
        if (!emergencySameAsPrimary || !emergencyAddressWrap || !emergencyAddressInput) return;
        if (emergencySameAsPrimary.checked) {
            emergencyAddressWrap.classList.add('hidden');
            emergencyAddressInput.value = '';
        } else {
            emergencyAddressWrap.classList.remove('hidden');
        }
    }

    if (emergencySameAsPrimary) {
        emergencySameAsPrimary.addEventListener('change', syncEmergencyAddressMode);
        syncEmergencyAddressMode();
    }

    function updateCompensationPreview() {
        if (!dailyCompInput || !monthlyGrossPreview || !annualGrossPreview) return;
        const daily = parseFloat(dailyCompInput.value);
        const safeDaily = Number.isFinite(daily) && daily >= 0 ? daily : 0;
        const monthly = safeDaily * 26;
        const annual = monthly * 12;
        monthlyGrossPreview.value = monthly.toFixed(2);
        annualGrossPreview.value = annual.toFixed(2);
    }

    if (dailyCompInput) {
        dailyCompInput.addEventListener('input', updateCompensationPreview);
        updateCompensationPreview();
    }

    if (form && phoneInput) {
        form.addEventListener('submit', function (e) {
            phoneInput.value = phoneInput.value.replace(/[^0-9]/g, '');

            if (phoneInput.value.length !== 9) {
                e.preventDefault();
                alert('Phone number must be exactly 9 digits after 09 (e.g., 123456789)');
                phoneInput.focus();
                return false;
            }

            const fullPhoneInput = document.createElement('input');
            fullPhoneInput.type = 'hidden';
            fullPhoneInput.name = 'phone';
            fullPhoneInput.value = '09' + phoneInput.value;
            form.appendChild(fullPhoneInput);

            if (emergencySameAsPrimary && emergencySameAsPrimary.checked && emergencyAddressInput && primaryAddressInput) {
                emergencyAddressInput.value = primaryAddressInput.value.trim();
            }

            phoneInput.disabled = true;
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 9) {
                this.value = this.value.slice(0, 9);
            }
        });

        phoneInput.addEventListener('paste', function (e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            this.value = paste.replace(/[^0-9]/g, '').slice(0, 9);
        });
    }

    function formatGovId(input, pattern) {
        if (!input) return;
        input.addEventListener('input', function () {
            let value = this.value.replace(/[^0-9]/g, '');
            if (pattern.max && value.length > pattern.max) {
                value = value.slice(0, pattern.max);
            }
            this.value = pattern.format(value);
        });
    }

    formatGovId(document.getElementById('sss'), {
        max: 10,
        format: function (value) {
            if (!value.length) return '';
            if (value.length <= 2) return value;
            if (value.length <= 9) return value.slice(0, 2) + '-' + value.slice(2);
            return value.slice(0, 2) + '-' + value.slice(2, 9) + '-' + value.slice(9);
        },
    });

    formatGovId(document.getElementById('philhealth'), {
        max: 12,
        format: function (value) {
            if (!value.length) return '';
            if (value.length <= 2) return value;
            if (value.length <= 11) return value.slice(0, 2) + '-' + value.slice(2);
            return value.slice(0, 2) + '-' + value.slice(2, 11) + '-' + value.slice(11);
        },
    });

    formatGovId(document.getElementById('pagibig'), {
        max: 12,
        format: function (value) {
            if (!value.length) return '';
            if (value.length <= 4) return value;
            if (value.length <= 8) return value.slice(0, 4) + '-' + value.slice(4);
            return value.slice(0, 4) + '-' + value.slice(4, 8) + '-' + value.slice(8);
        },
    });

    formatGovId(document.getElementById('tin'), {
        max: 12,
        format: function (value) {
            if (!value.length) return '';
            if (value.length <= 3) return value;
            if (value.length <= 6) return value.slice(0, 3) + '-' + value.slice(3);
            if (value.length <= 9) return value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6);
            return value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 9) + '-' + value.slice(9);
        },
    });
});
