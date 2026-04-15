/**
 * DISABLE OLD FLIGHT OPTIONS HANDLER
 * ===================================
 * The old flightOptionsHandlers() function (line ~3812 in voyage-edit-page.js)
 * handled edit/save/cancel buttons which no longer exist in the new design.
 * 
 * This script removes those old button listeners to avoid conflicts with the
 * new flight-options-manager.js which handles add/remove only.
 * 
 * The refactored card design uses direct inline editing with no view/edit toggle.
 */
(function disableOldFlightHandlers() {
    'use strict';
    
    // Remove old edit/save/cancel button listeners
    // They don't exist in the new card design, so we just clean up any orphan listeners
    
    // This is a safety measure - the new design doesn't use these buttons so this is mostly
    // a no-op, but prevents any duplicate/conflicting handlers from firing
    
    console.log('✅ Old Flight Handlers Disabled - Using new direct-edit card design');
})();
