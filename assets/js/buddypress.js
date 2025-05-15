/**
 * RPG-Suite BuddyPress Integration JavaScript
 * 
 * Handles character switching functionality in BuddyPress profiles.
 */
(function($) {
    'use strict';
    
    // Character management for BuddyPress profiles
    const RPGSuiteBP = {
        init: function() {
            this.setupCharacterSwitcher();
        },
        
        setupCharacterSwitcher: function() {
            // Toggle character switcher dropdown
            $(document).on('click', '.rpg-suite-character-switch-button', function(e) {
                e.preventDefault();
                $('.rpg-suite-character-switcher-dropdown').toggleClass('active');
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.rpg-suite-character-switcher').length) {
                    $('.rpg-suite-character-switcher-dropdown').removeClass('active');
                }
            });
            
            // Character switch action
            $(document).on('click', '.rpg-suite-switch-to-character', function(e) {
                e.preventDefault();
                
                const characterId = $(this).data('character-id');
                const nonce = $(this).data('nonce');
                
                // Show loading state
                $('.rpg-suite-character-switcher').addClass('loading');
                
                $.ajax({
                    url: rpg_suite_bp.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'rpg_suite_switch_character',
                        character_id: characterId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload the page to show the new active character
                            window.location.reload();
                        } else {
                            // Show error message
                            alert(response.data.message || 'Error switching character');
                        }
                    },
                    error: function() {
                        alert('Network error when switching character');
                    },
                    complete: function() {
                        $('.rpg-suite-character-switcher').removeClass('loading');
                        $('.rpg-suite-character-switcher-dropdown').removeClass('active');
                    }
                });
            });
        },
        
        // Keyboard accessibility
        setupKeyboardAccess: function() {
            // Toggle dropdown with Enter key
            $(document).on('keydown', '.rpg-suite-character-switch-button', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    $(this).click();
                }
            });
            
            // Handle escape key to close dropdown
            $(document).on('keydown', function(e) {
                if ((e.key === 'Escape' || e.keyCode === 27) && $('.rpg-suite-character-switcher-dropdown').hasClass('active')) {
                    $('.rpg-suite-character-switcher-dropdown').removeClass('active');
                }
            });
            
            // Handle arrow keys for navigating the dropdown
            $(document).on('keydown', '.rpg-suite-character-switcher-dropdown .rpg-suite-character-item a', function(e) {
                const $items = $('.rpg-suite-character-switcher-dropdown .rpg-suite-character-item a');
                const index = $items.index(this);
                
                // Down arrow
                if (e.key === 'ArrowDown' || e.keyCode === 40) {
                    e.preventDefault();
                    if (index < $items.length - 1) {
                        $items.eq(index + 1).focus();
                    }
                }
                
                // Up arrow
                if (e.key === 'ArrowUp' || e.keyCode === 38) {
                    e.preventDefault();
                    if (index > 0) {
                        $items.eq(index - 1).focus();
                    } else {
                        $('.rpg-suite-character-switch-button').focus();
                    }
                }
                
                // Enter key to select
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    $(this).click();
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        RPGSuiteBP.init();
        RPGSuiteBP.setupKeyboardAccess();
    });
    
})(jQuery);