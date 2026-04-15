/**
 * Flight Options Manager - Simplified Version
 * ============================================
 * - Removes edit/save/cancel buttons (direct inline editing)
 * - Simplifies cloning logic (no disabled attributes)
 * - No visual view/edit toggle
 * - Direct form submission to server
 */
(function flightOptionsManager() {
    'use strict';
    
    console.log('✅ Flight Options Manager: Initializing...');
    
    var form = document.getElementById('edit-voyage-form');
    var templatesEl = document.getElementById('flight-opt-templates');
    var nextIndexEl = document.getElementById('flight-opt-next-index');
    
    if (!form) {
        console.warn('Flight Options Manager: Form not found');
        return;
    }
    
    /**
     * Get next available index for new flight options
     */
    function getNextIndex() {
        if (!nextIndexEl) return 0;
        var n = parseInt(nextIndexEl.value, 10) || 0;
        nextIndexEl.value = n + 1;
        return n;
    }
    
    /**
     * Add new flight option from template clone
     */
    function addFlightOption(type, dayNumber) {
        if (!templatesEl || !form) return false;
        
        var templateWrapper = templatesEl.querySelector('[data-flight-tpl="' + type + '"]');
        if (!templateWrapper) return false;
        
        var templateCard = templateWrapper.querySelector('.flight-opt-card');
        if (!templateCard) return false;
        
        // Clone template
        var newCard = templateCard.cloneNode(true);
        var newIndex = getNextIndex();
        
        // Update index in cloned card
        newCard.setAttribute('data-flight-opt-index', newIndex);
        
        // Update all input names from [-1] to [newIndex]
        newCard.querySelectorAll('[name^="flight_options["]').forEach(function(el) {
            var oldName = el.name;
            el.name = oldName.replace(/flight_options\[-1\]/, 'flight_options[' + newIndex + ']');
            // IMPORTANT: Remove any disabled attribute so inputs are sent to server
            if (el.hasAttribute('disabled')) {
                el.removeAttribute('disabled');
            }
        });
        
        // For day_number_edit field (segment only), update the day value from data attribute
        var daySelect = newCard.querySelector('[name="flight_options[' + newIndex + '][day_number_edit]"]');
        if (daySelect && dayNumber) {
            daySelect.value = dayNumber;
            // Also update the hidden day_number field
            var hiddenDay = newCard.querySelector('input[name="flight_options[' + newIndex + '][day_number]"]');
            if (hiddenDay) {
                hiddenDay.value = dayNumber;
            }
        }
        
        // Find the appropriate container and append
        var container = form.querySelector('.flight-opt-cards-' + type);
        if (container) {
            container.appendChild(newCard);
            console.log('✈ Added new ' + type + ' flight: index=' + newIndex);
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove flight option card
     */
    function removeFlightOption(card) {
        if (!card) return;
        var index = card.getAttribute('data-flight-opt-index');
        card.remove();
        console.log('✈ Removed flight: index=' + index);
    }
    
    /**
     * Initialize event listeners
     */
    function initializeListeners() {
        // "Add flight" buttons
        form.addEventListener('click', function(e) {
            if (e.target.closest('.btn-add-flight-opt')) {
                var btn = e.target.closest('.btn-add-flight-opt');
                var type = btn.getAttribute('data-type');
                var dayNumber = parseInt(btn.getAttribute('data-day'), 10) || 1;
                addFlightOption(type, dayNumber);
                e.preventDefault();
                return false;
            }
            
            // "Remove flight" buttons
            if (e.target.closest('.flight-opt-remove')) {
                if (!confirm('Supprimer ce vol?')) return;
                var card = e.target.closest('.flight-opt-card');
                if (card) removeFlightOption(card);
                e.preventDefault();
                return false;
            }
        }, true); // capture phase
    }
    
    // Initialize
    initializeListeners();
    console.log('✅ Flight Options Manager: Ready');
})();
