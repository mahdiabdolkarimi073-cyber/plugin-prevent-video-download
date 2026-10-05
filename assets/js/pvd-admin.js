/**
 * Prevent Video Download — Premium Admin Settings Page Script
 */

(function () {
    'use strict';

    // ===== Range slider with gradient fill =====
    var rangeInputs = document.querySelectorAll('.pvd-range');
    for (var i = 0; i < rangeInputs.length; i++) {
        (function (rangeInput) {
            var valueDisplay = rangeInput.nextElementSibling;
            var isValueDisplay = valueDisplay && valueDisplay.classList.contains('pvd-range-value');

            function updateRange() {
                var min = parseFloat(rangeInput.min) || 0;
                var max = parseFloat(rangeInput.max) || 1;
                var val = parseFloat(rangeInput.value);
                var pct = ((val - min) / (max - min)) * 100;
                rangeInput.style.setProperty('--pvd-range-fill', pct + '%');
                if (isValueDisplay) {
                    valueDisplay.textContent = rangeInput.value;
                }
            }

            rangeInput.addEventListener('input', updateRange);
            updateRange();
        })(rangeInputs[i]);
    }

    // ===== Master switch — toggle card visual states =====
    var masterSwitch = document.querySelector('.pvd-master-card .pvd-switch input');
    if (masterSwitch) {
        var cards = document.querySelectorAll('.pvd-card:not(.pvd-master-card)');
        var submitBtn = document.querySelector('.pvd-save-btn');
        var saveInfo = document.querySelector('.pvd-save-info');

        function updateCardStates() {
            var enabled = masterSwitch.checked;
            for (var i = 0; i < cards.length; i++) {
                if (enabled) {
                    cards[i].style.opacity = '1';
                    cards[i].style.pointerEvents = 'auto';
                    cards[i].style.filter = 'none';
                } else {
                    cards[i].style.opacity = '0.45';
                    cards[i].style.pointerEvents = 'none';
                    cards[i].style.filter = 'grayscale(0.5)';
                }
            }
            if (submitBtn) {
                submitBtn.style.opacity = enabled ? '1' : '0.5';
                submitBtn.style.pointerEvents = enabled ? 'auto' : 'none';
            }
            if (saveInfo) {
                saveInfo.style.opacity = enabled ? '1' : '0.5';
            }
        }

        masterSwitch.addEventListener('change', updateCardStates);
        updateCardStates();
    }

    // ===== Status badge dynamic update =====
    var masterCheckbox = document.querySelector('.pvd-master-card .pvd-switch input');
    if (masterCheckbox) {
        var badge = document.querySelector('.pvd-status-badge');
        var badgeText = badge ? badge.querySelector('span:last-child') : null;

        function updateBadge() {
            if (!badge) return;
            if (masterCheckbox.checked) {
                badge.className = 'pvd-status-badge pvd-status-on';
                if (badgeText) badgeText.textContent = 'حفاظت فعال';
            } else {
                badge.className = 'pvd-status-badge pvd-status-off';
                if (badgeText) badgeText.textContent = 'حفاظت غیرفعال';
            }
        }

        masterCheckbox.addEventListener('change', updateBadge);
    }

    // ===== Smooth scroll for sidebar quick actions (if any) =====
    var quickBtns = document.querySelectorAll('.pvd-quick-btn');
    for (var q = 0; q < quickBtns.length; q++) {
        quickBtns[q].addEventListener('click', function (e) {
            var href = this.getAttribute('href');
            if (href && href.indexOf('#') === 0 && href.length > 1) {
                e.preventDefault();
                var target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    }
})();
