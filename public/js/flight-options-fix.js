/**
 * FLIGHT OPTIONS FIX - Remove disabled attributes before form submission
 * This fixes the bug where flight options were not being saved because
 * disabled inputs are not included in form submissions.
 */
(function flightOptionsFix() {
    'use strict';
    
    var form = document.getElementById('edit-voyage-form');
    if (!form) {
        console.warn('Flight Options Fix: Form #edit-voyage-form not found');
        return;
    }
    
    console.log('✅ Flight Options Fix: Initializing...');
    
    /**
     * Remove disabled attribute from flight_options inputs
     * - Skip template inputs (in #flight-opt-templates)
     * - Skip drawer inputs (in #day-builder-root)
     * - Skip index -1 inputs (template clones)
     */
    function cleanFlightOptionsBeforeSubmit() {
        var templatesContainer = document.getElementById('flight-opt-templates');
        var drawerContainer = document.getElementById('day-builder-root');
        var count = 0;
        
        document.querySelectorAll('[name^="flight_options["]').forEach(function(el) {
            // Skip inputs inside template container
            if (templatesContainer && templatesContainer.contains(el)) {
                return;
            }
            
            // Skip inputs inside drawer/day-builder
            if (drawerContainer && drawerContainer.contains(el)) {
                return;
            }
            
            // Skip inputs with index -1 (template clones)
            if (el.name && el.name.indexOf('[-1]') !== -1) {
                return;
            }
            
            // Remove disabled attribute
            if (el.hasAttribute('disabled')) {
                el.removeAttribute('disabled');
                count++;
            }
        });
        
        if (count > 0) {
            console.log('✈ Flight Options: Enabled ' + count + ' inputs for submission');
        }
        
        return count;
    }
    
    // Hook into form submission (capture phase to run early)
    form.addEventListener('submit', function() {
        var enabledCount = cleanFlightOptionsBeforeSubmit();
        if (enabledCount > 0) {
            console.log('✈ Flight Options: Releasing form with ' + enabledCount + ' flight option inputs');
        }
    }, true);
    
    // Also clean on button clicks (in case JavaScript form submission is triggered)
    document.addEventListener('click', function(e) {
        var submitBtn = e.target.closest('[type="submit"][form="edit-voyage-form"], #edit-voyage-submit-btn, [form="edit-voyage-form"][type="submit"]');
        if (submitBtn) {
            console.log('✈ Flight Options: Pre-cleaning before submit button click');
            cleanFlightOptionsBeforeSubmit();
        }
    }, true);
    
    console.log('✅ Flight Options Fix: Initialized successfully');
})();
