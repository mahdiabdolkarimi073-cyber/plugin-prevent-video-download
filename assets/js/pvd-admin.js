/**
 * Prevent Video Download — Admin Settings Page Script
 */

(function () {
    'use strict';

    // Update opacity value display in real-time
    var rangeInput = document.querySelector('.pvd-range');
    if (rangeInput) {
        var valueDisplay = rangeInput.nextElementSibling;
        if (valueDisplay && valueDisplay.classList.contains('pvd-range-value')) {
            rangeInput.addEventListener('input', function () {
                valueDisplay.textContent = rangeInput.value;
            });
        }
    }

    // Master switch — toggle all other cards visual state
    var masterSwitch = document.querySelector('.pvd-master-card .pvd-switch input');
    if (masterSwitch) {
        var cards = document.querySelectorAll('.pvd-card:not(.pvd-master-card)');
        var submitBtn = document.querySelector('.pvd-save-btn');

        function updateCardStates() {
            var enabled = masterSwitch.checked;
            for (var i = 0; i < cards.length; i++) {
                if (enabled) {
                    cards[i].style.opacity = '1';
                    cards[i].style.pointerEvents = 'auto';
                } else {
                    cards[i].style.opacity = '0.5';
                    cards[i].style.pointerEvents = 'none';
                }
            }
            if (submitBtn) {
                submitBtn.style.opacity = enabled ? '1' : '0.6';
            }
        }

        masterSwitch.addEventListener('change', updateCardStates);
        updateCardStates();
    }

    // Update status badge dynamically
    var masterCheckbox = document.querySelector('.pvd-master-card .pvd-switch input');
    if (masterCheckbox) {
        var badge = document.querySelector('.pvd-status-badge');
        var badgeDot = badge ? badge.querySelector('.pvd-status-dot') : null;
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
})();
