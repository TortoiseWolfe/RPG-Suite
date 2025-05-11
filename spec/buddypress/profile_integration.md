# BuddyPress Profile Integration Specification - REVISED

## Purpose
This specification defines how RPG-Suite integrates with BuddyPress to display character information on user profiles and provide character management functionality.

## Requirements
1. Display active character information in BuddyPress profile header
2. Provide character switching interface on profile
3. Ensure compatibility with BuddyX theme
4. Register hooks at appropriate times in BuddyPress lifecycle
5. Make character data accessible through global plugin instance

## Lessons Learned from Previous Implementation
1. **Simplify Hook Registration**: Use only standard BuddyPress hooks with normal priorities
2. **Avoid Excessive Fallbacks**: Focus on one primary display approach rather than multiple fallbacks
3. **Use Browser Testing Only**: Test user-dependent features in browsers, not CLI environments
4. **Direct Admin URLs**: Use admin_url() directly to construct edit URLs to avoid conflicts
5. **Simplify CSS/JS**: Keep styling and JavaScript simple without excessive !important declarations

## Component Structure

### BuddyPress Integration Class

The BuddyPress Integration class should:
1. Be named `RPG_Suite_BuddyPress_Integration`
2. Be defined in file `class-buddypress-integration.php`
3. Have dependencies on:
   - RPG_Suite_Character_Manager
   - RPG_Suite_Event_Dispatcher
4. Initialize the integration with these steps:
   - Check if BuddyPress is active
   - Register a focused set of hooks on 'bp_init' with priority 20
   - Register CSS and JS assets
5. Implement methods:
   - initialize_hooks(): Register necessary profile display hooks
   - display_active_character(): Show character information
   - add_character_switch_button(): Add the character switcher UI
   - handle_character_switch_ajax(): Process AJAX requests for character switching
   - enqueue_assets(): Register and enqueue CSS/JS files

## Revised Hook Registration Approach

```php
/**
 * Initialize BuddyPress hooks
 */
public function initialize_hooks() {
    // Primary display hook - most compatible across themes
    add_action('bp_after_member_header', array($this, 'display_active_character'), 20);
    
    // BuddyX theme specific hook if needed
    if ($this->is_buddyx_theme) {
        add_action('buddyx_member_header', array($this, 'display_active_character'), 20);
    }
    
    // Character switch button
    add_action('bp_member_header_actions', array($this, 'add_character_switch_button'), 20);
    
    // Register AJAX handler for character switching
    add_action('wp_ajax_rpg_suite_switch_character', array($this, 'handle_character_switch_ajax'));
    
    // Enqueue necessary styles and scripts
    add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 20);
}
```

## Character Display Implementation

The display_active_character() method should:

1. Get the displayed user ID using bp_displayed_user_id()
2. Retrieve the active character using the character manager
3. Display a message if no character is active
4. If a character exists, display:
   - Character name
   - Character class
   - Character attributes
5. Use proper HTML structure with appropriate CSS classes
6. Apply proper escaping for all output

```php
/**
 * Display active character in profile
 */
public function display_active_character() {
    // Only display on BP profile pages
    if (!function_exists('bp_is_user') || !bp_is_user()) {
        return;
    }
    
    $user_id = bp_displayed_user_id();
    $active_character = $this->character_manager->get_active_character($user_id);
    
    if (!$active_character) {
        return;
    }
    
    // Get character attributes
    $attributes = array(
        'fortitude' => get_post_meta($active_character->ID, '_rpg_attribute_fortitude', true),
        'precision' => get_post_meta($active_character->ID, '_rpg_attribute_precision', true),
        'intellect' => get_post_meta($active_character->ID, '_rpg_attribute_intellect', true),
        'charisma'  => get_post_meta($active_character->ID, '_rpg_attribute_charisma', true),
    );
    
    // Character class
    $character_class = get_post_meta($active_character->ID, '_rpg_class', true);
    
    // HTML structure with appropriate classes
    ?>
    <div class="rpg-suite-character-display">
        <h4><?php echo esc_html__('Active Character', 'rpg-suite'); ?></h4>
        <div class="rpg-suite-character-name">
            <?php echo esc_html($active_character->post_title); ?>
            <?php if ($character_class): ?>
                <span class="rpg-suite-character-class">(<?php echo esc_html($character_class); ?>)</span>
            <?php endif; ?>
        </div>
        
        <div class="rpg-suite-attributes">
            <?php foreach ($attributes as $name => $value): ?>
                <?php if ($value): ?>
                    <div class="rpg-suite-attribute">
                        <span class="rpg-suite-attribute-name"><?php echo esc_html(ucfirst($name)); ?>:</span>
                        <span class="rpg-suite-attribute-value"><?php echo esc_html($value); ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <?php if (get_current_user_id() === $user_id || current_user_can('edit_rpg_character', $active_character->ID)): ?>
            <div class="rpg-suite-character-actions">
                <?php
                // Use direct admin URL to avoid conflicts
                $edit_url = admin_url('post.php?post=' . $active_character->ID . '&action=edit');
                ?>
                <a href="<?php echo esc_url($edit_url); ?>" class="rpg-suite-edit-character button">
                    <?php echo esc_html__('Edit Character', 'rpg-suite'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
```

## Character Switching Implementation

The add_character_switch_button() method should:

1. Only display for the profile owner
2. Retrieve the user's characters from the character manager
3. Only show if the user has multiple characters
4. Display a dropdown with all available characters
5. Indicate which character is currently active
6. Include proper nonces for security

```php
/**
 * Add character switch button
 */
public function add_character_switch_button() {
    // Only display on BP profile pages
    if (!function_exists('bp_is_user') || !bp_is_user()) {
        return;
    }
    
    $user_id = bp_displayed_user_id();
    
    // Only show for profile owner
    if (get_current_user_id() !== $user_id) {
        return;
    }
    
    $characters = $this->character_manager->get_user_characters($user_id);
    
    // Only show if user has multiple characters
    if (count($characters) <= 1) {
        return;
    }
    
    // Create nonce for security
    $nonce = wp_create_nonce('rpg_suite_switch_character');
    
    ?>
    <div class="generic-button rpg-suite-character-switcher">
        <a href="#" class="rpg-suite-character-switch-button">
            <?php echo esc_html__('Switch Character', 'rpg-suite'); ?>
        </a>
        
        <div class="rpg-suite-character-switcher-dropdown">
            <h4><?php echo esc_html__('Select Character', 'rpg-suite'); ?></h4>
            <ul class="rpg-suite-character-list">
                <?php foreach ($characters as $character): ?>
                    <?php
                    $is_active = (bool) get_post_meta($character->ID, '_rpg_active', true);
                    $class = $is_active ? 'rpg-suite-character-item active' : 'rpg-suite-character-item';
                    ?>
                    <li class="<?php echo esc_attr($class); ?>">
                        <a href="#" class="rpg-suite-switch-to-character" 
                            data-character-id="<?php echo esc_attr($character->ID); ?>"
                            data-nonce="<?php echo esc_attr($nonce); ?>">
                            <span class="rpg-suite-character-name"><?php echo esc_html($character->post_title); ?></span>
                            <?php if ($is_active): ?>
                                <span class="rpg-suite-active-indicator">(<?php echo esc_html__('Active', 'rpg-suite'); ?>)</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
}
```

## CSS and JavaScript Implementation

### CSS

The CSS should:
1. Use a simple, clean design without excessive overrides
2. Avoid overuse of !important declarations
3. Use a consistent naming scheme with rpg-suite- prefix
4. Support basic responsive design principles

### JavaScript

The JavaScript should:
1. Be kept simple and focused on core functionality
2. Implement character switching functionality
3. Use standard jQuery patterns
4. Avoid excessive DOM manipulation
5. Focus on user interaction without debugging code

```javascript
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
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        RPGSuiteBP.init();
    });
    
})(jQuery);
```

## Implementation Notes

1. **Simplified Hook Registration**: Use only the necessary hooks, avoiding excessive registrations
2. **Browser Testing**: Always test user-dependent features in a browser environment with real user sessions
3. **Direct Admin URLs**: Use admin_url() directly, not get_edit_post_link(), to avoid conflicts
4. **Clean CSS/JS**: Keep styling and scripts simple and focused, avoiding overengineering
5. **Proper Capability Checks**: Use post-specific capabilities when checking permissions