# RPG-Suite Implementation Plan

This document outlines the detailed implementation plan for the RPG-Suite WordPress plugin, focusing on the MVP (Minimum Viable Product) features.

## MVP Components

1. **Core Plugin Structure**
   - PSR-4 autoloader
   - Main plugin class
   - Global access pattern
   - Event system

2. **Character Management**
   - Character post type
   - Random character generation
   - Character-player relationship
   - Active character tracking

3. **Experience System**
   - User XP tracking
   - Feature unlocking based on XP
   - XP history logging
   - Feature access checks

4. **BuddyPress Integration**
   - Character display in profiles
   - Theme compatibility
   - Character switching UI
   - Character profile tab

## Implementation Sequence

### 1. Core Plugin Structure (Week 1)

#### Day 1-2: Plugin Initialization
- Create plugin bootstrap file (rpg-suite.php)
- Implement autoloader
- Create main plugin class
- Set up global access pattern

```php
// rpg-suite.php
<?php
/**
 * Plugin Name: RPG Suite
 * Description: A modular WordPress plugin for implementing RPG mechanics with BuddyPress integration
 * Version: 0.1.0
 * Author: RPG Suite Team
 * Text Domain: rpg-suite
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('RPG_SUITE_VERSION', '0.1.0');
define('RPG_SUITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RPG_SUITE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader
require_once RPG_SUITE_PLUGIN_DIR . 'includes/class-autoloader.php';
$autoloader = new RPG\Suite\Includes\Autoloader();
$autoloader->register();

// Activation/deactivation hooks
register_activation_hook(__FILE__, [RPG\Suite\Includes\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [RPG\Suite\Includes\Deactivator::class, 'deactivate']);

/**
 * Run the plugin
 */
function run_rpg_suite() {
    global $rpg_suite;
    
    // Initialize the plugin
    $rpg_suite = new RPG\Suite\Includes\RPG_Suite();
    
    // Store reference in global for access
    $GLOBALS['rpg_suite'] = $rpg_suite;
    
    // Run the plugin
    $rpg_suite->run();
}

/**
 * Helper function to access the plugin instance
 */
function rpg_suite() {
    global $rpg_suite;
    return $rpg_suite;
}

// Start the plugin
run_rpg_suite();
```

#### Day 3-4: Event System
- Implement event dispatcher
- Create base event class
- Set up event subscriber interface
- Implement WordPress hook integration

```php
// src/Core/class-event.php
namespace RPG\Suite\Core;

class Event {
    protected $propagation_stopped = false;
    
    public function stopPropagation() {
        $this->propagation_stopped = true;
    }
    
    public function isPropagationStopped() {
        return $this->propagation_stopped;
    }
}
```

```php
// src/Core/class-event-dispatcher.php
namespace RPG\Suite\Core;

class Event_Dispatcher {
    protected $listeners = [];
    
    public function dispatch($event_name, Event $event = null) {
        if (null === $event) {
            $event = new Event();
        }
        
        if (isset($this->listeners[$event_name])) {
            foreach ($this->listeners[$event_name] as $listener) {
                call_user_func($listener, $event);
                
                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }
        
        // Integration with WordPress actions
        do_action("rpg_suite_{$event_name}", $event);
        
        return $event;
    }
    
    public function addListener($event_name, $listener, $priority = 10) {
        if (!isset($this->listeners[$event_name])) {
            $this->listeners[$event_name] = [];
        }
        
        $this->listeners[$event_name][$priority] = $listener;
        ksort($this->listeners[$event_name]);
        
        return $this;
    }
}
```

#### Day 5: Core Class
- Create core subsystem class
- Set up subsystem initialization
- Implement WordPress hooks

```php
// src/Core/class-core.php
namespace RPG\Suite\Core;

class Core {
    protected $event_dispatcher;
    
    public function __construct() {
        $this->event_dispatcher = new Event_Dispatcher();
    }
    
    public function init() {
        // Register hooks
        add_action('init', [$this, 'register_post_types'], 10);
        add_action('bp_init', [$this, 'initialize_buddypress_integration'], 20);
        
        // Dispatch initialization event
        $this->event_dispatcher->dispatch('core_initialized');
    }
    
    public function register_post_types() {
        // Register character post type
    }
    
    public function initialize_buddypress_integration() {
        if (function_exists('buddypress')) {
            // Initialize BuddyPress integration
        }
    }
    
    public function get_event_dispatcher() {
        return $this->event_dispatcher;
    }
}
```

### 2. Character Management (Week 2)

#### Day 1-2: Character Post Type
- Register character post type
- Define metadata fields
- Set up capabilities

```php
// src/Character/class-character-post-type.php
namespace RPG\Suite\Character;

class Character_Post_Type {
    public function register() {
        register_post_type('rpg_character', [
            'labels' => [
                'name' => __('Characters', 'rpg-suite'),
                'singular_name' => __('Character', 'rpg-suite'),
                // Other labels...
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-admin-users',
            'supports' => ['title', 'editor', 'thumbnail', 'author', 'custom-fields'],
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'character'],
            
            // Use standard capability mapping
            'capability_type' => 'post',
            'map_meta_cap' => true,
            
            // Menu integration
            'show_in_menu' => 'rpg-suite',
        ]);
        
        // Register meta fields
        $this->register_meta_fields();
    }
    
    protected function register_meta_fields() {
        register_post_meta('rpg_character', 'character_owner', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
        ]);
        
        register_post_meta('rpg_character', 'character_is_active', [
            'type' => 'boolean',
            'single' => true,
            'default' => false,
            'show_in_rest' => true,
        ]);
        
        register_post_meta('rpg_character', 'character_is_alive', [
            'type' => 'boolean',
            'single' => true,
            'default' => true,
            'show_in_rest' => true,
        ]);
        
        register_post_meta('rpg_character', 'character_class', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
        
        register_post_meta('rpg_character', 'character_level', [
            'type' => 'integer',
            'single' => true,
            'default' => 1,
            'show_in_rest' => true,
        ]);
        
        // Additional meta fields...
    }
}
```

#### Day 3-4: Character Manager
- Create character manager class
- Implement character CRUD operations
- Add character-player relationship
- Implement active character tracking

```php
// src/Character/class-character-manager.php
namespace RPG\Suite\Character;

class Character_Manager {
    public function get_user_characters($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return get_posts([
            'post_type' => 'rpg_character',
            'meta_query' => [
                [
                    'key' => 'character_owner',
                    'value' => $user_id,
                ],
            ],
            'posts_per_page' => -1,
        ]);
    }
    
    public function get_active_character($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $characters = get_posts([
            'post_type' => 'rpg_character',
            'meta_query' => [
                [
                    'key' => 'character_owner',
                    'value' => $user_id,
                ],
                [
                    'key' => 'character_is_active',
                    'value' => '1',
                ],
                [
                    'key' => 'character_is_alive',
                    'value' => '1',
                ],
            ],
            'posts_per_page' => 1,
        ]);
        
        if (!empty($characters)) {
            return $characters[0];
        }
        
        return null;
    }
    
    public function set_active_character($character_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Verify ownership
        $owner_id = get_post_meta($character_id, 'character_owner', true);
        if ($owner_id != $user_id) {
            return false;
        }
        
        // Get all user's characters
        $characters = $this->get_user_characters($user_id);
        
        // Set all to inactive
        foreach ($characters as $character) {
            update_post_meta($character->ID, 'character_is_active', false);
        }
        
        // Set new active character
        update_post_meta($character_id, 'character_is_active', true);
        
        return true;
    }
}
```

#### Day 5: Random Character Generation
- Create character class definitions
- Implement random attribute generation
- Add random name generation
- Create default character creation function

```php
// src/Character/class-character-generator.php
namespace RPG\Suite\Character;

class Character_Generator {
    // Character class definitions
    protected $character_classes = [
        'sky-captain' => [
            'name' => 'Sky Captain',
            'image' => 'sky-captain.jpg',
            'description' => 'Master of airship navigation and aerial combat.',
            'attribute_ranges' => [
                'strength' => ['min' => 8, 'max' => 14],
                'dexterity' => ['min' => 12, 'max' => 18],
                'constitution' => ['min' => 10, 'max' => 16],
                'intelligence' => ['min' => 12, 'max' => 16],
                'wisdom' => ['min' => 8, 'max' => 14],
                'charisma' => ['min' => 10, 'max' => 16],
            ],
            'name_prefixes' => ['Cap', 'Sky', 'Wind', 'Storm', 'Cloud'],
            'name_suffixes' => ['rider', 'hawk', 'pierce', 'blast', 'sail'],
        ],
        // Other classes...
    ];
    
    public function create_random_character($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Choose random class
        $class_keys = array_keys($this->character_classes);
        $class = $class_keys[array_rand($class_keys)];
        $class_info = $this->character_classes[$class];
        
        // Generate random attributes
        $attributes = [];
        foreach ($class_info['attribute_ranges'] as $attr => $range) {
            $attributes[$attr] = rand($range['min'], $range['max']);
        }
        
        // Generate random name
        $name = $this->generate_random_name($class);
        
        // Create character post
        $post_id = wp_insert_post([
            'post_title' => $name,
            'post_content' => sprintf(
                __('A %s in the steampunk world. %s', 'rpg-suite'),
                $class_info['name'],
                $class_info['description']
            ),
            'post_status' => 'publish',
            'post_type' => 'rpg_character',
        ]);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Set character metadata
        update_post_meta($post_id, 'character_owner', $user_id);
        update_post_meta($post_id, 'character_is_active', true);
        update_post_meta($post_id, 'character_is_alive', true);
        update_post_meta($post_id, 'character_class', $class);
        update_post_meta($post_id, 'character_level', 1);
        update_post_meta($post_id, 'character_experience', 0);
        update_post_meta($post_id, 'character_creation_date', current_time('mysql'));
        
        // Set attributes
        foreach ($attributes as $key => $value) {
            update_post_meta($post_id, 'attribute_' . $key, $value);
        }
        
        return $post_id;
    }
    
    protected function generate_random_name($class) {
        $class_info = $this->character_classes[$class];
        $prefix = $class_info['name_prefixes'][array_rand($class_info['name_prefixes'])];
        $suffix = $class_info['name_suffixes'][array_rand($class_info['name_suffixes'])];
        
        return $prefix . ' ' . $suffix;
    }
}
```

### 3. Experience System (Week 3)

#### Day 1-2: User XP Tracking
- Create experience manager class
- Implement XP awards
- Add XP history logging
- Create feature threshold definitions

```php
// src/Experience/class-experience-manager.php
namespace RPG\Suite\Experience;

class Experience_Manager {
    // Feature unlocking thresholds
    protected $feature_thresholds = [
        'edit_character' => 1000,
        'character_respawn' => 2500,
        'multiple_characters' => 5000,
        'character_switching' => 7500,
        'advanced_customization' => 10000,
    ];
    
    public function award_experience($user_id, $amount, $reason = '') {
        // Get current XP
        $current_xp = (int)get_user_meta($user_id, 'rpg_experience_points', true);
        $new_xp = $current_xp + $amount;
        
        // Update XP
        update_user_meta($user_id, 'rpg_experience_points', $new_xp);
        
        // Log the award
        $log_entry = [
            'amount' => $amount,
            'reason' => $reason,
            'timestamp' => current_time('mysql'),
            'total' => $new_xp,
        ];
        
        $log = get_user_meta($user_id, 'rpg_experience_log', true);
        if (!is_array($log)) {
            $log = [];
        }
        
        $log[] = $log_entry;
        update_user_meta($user_id, 'rpg_experience_log', $log);
        
        // Check for newly unlocked features
        $unlocked = [];
        foreach ($this->feature_thresholds as $feature => $threshold) {
            if ($current_xp < $threshold && $new_xp >= $threshold) {
                $unlocked[] = $feature;
            }
        }
        
        // Trigger notifications for newly unlocked features
        if (!empty($unlocked)) {
            do_action('rpg_features_unlocked', $user_id, $unlocked);
        }
        
        return [
            'previous_xp' => $current_xp,
            'new_xp' => $new_xp,
            'gained' => $amount,
            'unlocked_features' => $unlocked,
        ];
    }
    
    public function has_unlocked_feature($feature, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Admin always has all features
        if (user_can($user_id, 'administrator')) {
            return true;
        }
        
        // Get user XP
        $xp = (int)get_user_meta($user_id, 'rpg_experience_points', true);
        
        // Check if user has enough XP for this feature
        if (isset($this->feature_thresholds[$feature])) {
            return $xp >= $this->feature_thresholds[$feature];
        }
        
        return false;
    }
    
    public function get_user_experience($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return [
            'total' => (int)get_user_meta($user_id, 'rpg_experience_points', true),
            'log' => get_user_meta($user_id, 'rpg_experience_log', true) ?: [],
        ];
    }
}
```

#### Day 3-4: Feature Manager
- Create feature manager class
- Implement feature unlocking notification
- Add feature access checks
- Create UI adaptation helpers

```php
// src/Experience/class-feature-manager.php
namespace RPG\Suite\Experience;

class Feature_Manager {
    protected $experience_manager;
    
    public function __construct($experience_manager) {
        $this->experience_manager = $experience_manager;
        
        // Register notification hook
        add_action('rpg_features_unlocked', [$this, 'handle_feature_unlock'], 10, 2);
    }
    
    public function handle_feature_unlock($user_id, $features) {
        // Store newly unlocked features for notification
        $new_unlocks = get_user_meta($user_id, 'rpg_new_unlocks', true);
        if (!is_array($new_unlocks)) {
            $new_unlocks = [];
        }
        
        $new_unlocks = array_merge($new_unlocks, $features);
        update_user_meta($user_id, 'rpg_new_unlocks', $new_unlocks);
        
        // Maybe show admin notification
        if (is_admin() && get_current_user_id() == $user_id) {
            add_action('admin_notices', [$this, 'show_feature_unlock_notice']);
        }
    }
    
    public function show_feature_unlock_notice() {
        $user_id = get_current_user_id();
        $new_unlocks = get_user_meta($user_id, 'rpg_new_unlocks', true);
        
        if (!is_array($new_unlocks) || empty($new_unlocks)) {
            return;
        }
        
        // Display notification
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . __('You\'ve unlocked new RPG features!', 'rpg-suite') . '</p>';
        echo '<ul>';
        
        foreach ($new_unlocks as $feature) {
            echo '<li>' . $this->get_feature_name($feature) . '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
        
        // Clear notification
        delete_user_meta($user_id, 'rpg_new_unlocks');
    }
    
    public function get_feature_name($feature) {
        $names = [
            'edit_character' => __('Character Editing', 'rpg-suite'),
            'character_respawn' => __('Character Respawn', 'rpg-suite'),
            'multiple_characters' => __('Multiple Characters', 'rpg-suite'),
            'character_switching' => __('Character Switching', 'rpg-suite'),
            'advanced_customization' => __('Advanced Customization', 'rpg-suite'),
        ];
        
        return isset($names[$feature]) ? $names[$feature] : $feature;
    }
    
    public function render_feature_progress($feature) {
        $user_id = get_current_user_id();
        $thresholds = $this->experience_manager->get_feature_thresholds();
        
        if (!isset($thresholds[$feature])) {
            return '';
        }
        
        $threshold = $thresholds[$feature];
        $current_xp = (int)get_user_meta($user_id, 'rpg_experience_points', true);
        $unlocked = ($current_xp >= $threshold);
        $progress = min(100, round(($current_xp / $threshold) * 100));
        
        ob_start();
        ?>
        <div class="rpg-feature-progress">
            <div class="rpg-feature-name"><?php echo $this->get_feature_name($feature); ?></div>
            <div class="rpg-progress-bar">
                <div class="rpg-progress" style="width: <?php echo $progress; ?>%"></div>
            </div>
            <div class="rpg-progress-text">
                <?php if ($unlocked): ?>
                    <span class="rpg-unlocked"><?php _e('Unlocked!', 'rpg-suite'); ?></span>
                <?php else: ?>
                    <?php echo $current_xp; ?> / <?php echo $threshold; ?> XP
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
```

#### Day 5: Character Death & Respawn
- Implement character death tracking
- Create respawn mechanics
- Add XP rewards for character lifetime

```php
// src/Character/class-character-death.php
namespace RPG\Suite\Character;

class Character_Death {
    protected $experience_manager;
    
    public function __construct($experience_manager) {
        $this->experience_manager = $experience_manager;
    }
    
    public function kill_character($character_id, $death_cause = '') {
        // Verify the character exists
        $character = get_post($character_id);
        if (!$character || $character->post_type !== 'rpg_character') {
            return new \WP_Error('invalid_character', __('Invalid character ID.', 'rpg-suite'));
        }
        
        // Get the character owner
        $owner_id = get_post_meta($character_id, 'character_owner', true);
        
        // Verify the character is alive
        $is_alive = get_post_meta($character_id, 'character_is_alive', true);
        if (!$is_alive) {
            return new \WP_Error('character_already_dead', __('This character is already dead.', 'rpg-suite'));
        }
        
        // Mark the character as dead
        update_post_meta($character_id, 'character_is_alive', false);
        update_post_meta($character_id, 'character_death_cause', $death_cause);
        update_post_meta($character_id, 'character_death_date', current_time('mysql'));
        
        // Update user's death count
        $death_count = (int)get_user_meta($owner_id, 'rpg_character_deaths', true);
        update_user_meta($owner_id, 'rpg_character_deaths', $death_count + 1);
        
        // Calculate experience earned from this character's life
        $character_level = (int)get_post_meta($character_id, 'character_level', true);
        
        // Calculate days alive
        $creation_date = get_post_meta($character_id, 'character_creation_date', true);
        $days_alive = 1; // Default to 1 day if creation date not set
        
        if ($creation_date) {
            $creation_timestamp = strtotime($creation_date);
            $now = current_time('timestamp');
            $days_alive = max(1, floor(($now - $creation_timestamp) / DAY_IN_SECONDS));
        }
        
        // Award XP based on character level and days alive
        $xp_award = ($character_level * 50) + ($days_alive * 10);
        $this->experience_manager->award_experience(
            $owner_id, 
            $xp_award, 
            sprintf(__('Character death: %s (Level %d, %d days)', 'rpg-suite'), 
                $character->post_title,
                $character_level,
                $days_alive
            )
        );
        
        return [
            'character_id' => $character_id,
            'character_name' => $character->post_title,
            'days_alive' => $days_alive,
            'level' => $character_level,
            'xp_awarded' => $xp_award,
        ];
    }
    
    public function respawn_character($character_id) {
        // Verify the character exists
        $character = get_post($character_id);
        if (!$character || $character->post_type !== 'rpg_character') {
            return new \WP_Error('invalid_character', __('Invalid character ID.', 'rpg-suite'));
        }
        
        // Get the character owner
        $owner_id = get_post_meta($character_id, 'character_owner', true);
        
        // Verify the current user owns the character
        if (get_current_user_id() != $owner_id) {
            return new \WP_Error('not_owner', __('You do not own this character.', 'rpg-suite'));
        }
        
        // Verify the character is dead
        $is_alive = get_post_meta($character_id, 'character_is_alive', true);
        if ($is_alive) {
            return new \WP_Error('character_not_dead', __('This character is not dead and cannot be respawned.', 'rpg-suite'));
        }
        
        // Verify the user has unlocked the respawn feature
        if (!$this->experience_manager->has_unlocked_feature('character_respawn')) {
            return new \WP_Error('feature_locked', __('You have not unlocked the character respawn feature yet.', 'rpg-suite'));
        }
        
        // Apply respawn penalties
        $character_level = (int)get_post_meta($character_id, 'character_level', true);
        $character_xp = (int)get_post_meta($character_id, 'character_experience', true);
        
        // XP penalty: lose 20% of character XP
        $xp_penalty = ceil($character_xp * 0.2);
        update_post_meta($character_id, 'character_experience', max(0, $character_xp - $xp_penalty));
        
        // Mark character as alive again
        update_post_meta($character_id, 'character_is_alive', true);
        update_post_meta($character_id, 'character_respawn_count', (int)get_post_meta($character_id, 'character_respawn_count', true) + 1);
        update_post_meta($character_id, 'character_respawn_date', current_time('mysql'));
        
        return [
            'success' => true,
            'character_id' => $character_id,
            'character_name' => $character->post_title,
            'xp_penalty' => $xp_penalty,
        ];
    }
}
```

### 4. BuddyPress Integration (Week 4)

#### Day 1-2: Profile Integration
- Create profile integration component
- Implement character display in profiles
- Add multiple hook points for theme compatibility

```php
// src/Core/Components/class-profile-integration.php
namespace RPG\Suite\Core\Components;

class Profile_Integration {
    protected $character_manager;
    
    public function __construct($character_manager) {
        $this->character_manager = $character_manager;
        
        // Register hooks
        $this->register_hooks();
    }
    
    protected function register_hooks() {
        // Primary hook for inside the profile card
        add_action('bp_member_header_inner_content', [$this, 'display_character_in_profile']);
        
        // Fallback hooks for various themes
        add_action('bp_before_member_header_meta', [$this, 'display_character_in_profile']);
        add_action('bp_member_header_actions', [$this, 'display_character_in_profile']);
        
        // BuddyX specific hooks
        add_action('buddyx_member_header_actions', [$this, 'display_character_in_profile']);
        add_action('buddyx_member_header_meta', [$this, 'display_character_in_profile']);
        
        // Add character tab in profile
        add_action('bp_setup_nav', [$this, 'add_character_profile_tab'], 100);
        
        // Enqueue CSS
        add_action('wp_enqueue_scripts', [$this, 'enqueue_profile_css']);
    }
    
    public function display_character_in_profile() {
        // Check if we already output the character info to avoid duplicates
        static $displayed = false;
        if ($displayed) {
            return;
        }
        
        $user_id = bp_displayed_user_id();
        $active_character = $this->character_manager->get_active_character($user_id);
        
        if (!$active_character) {
            if (bp_is_my_profile()) {
                // Prompt to create character
                echo '<div class="rpg-character-prompt">';
                echo '<p>' . __('You haven\'t created a character yet!', 'rpg-suite') . '</p>';
                echo '<a href="' . admin_url('admin.php?page=rpg-character-management') . '" class="button">';
                echo __('Create Your Character', 'rpg-suite') . '</a>';
                echo '</div>';
            }
            return;
        }
        
        // Get character details
        $character_class = get_post_meta($active_character->ID, 'character_class', true);
        $character_level = get_post_meta($active_character->ID, 'character_level', true);
        
        // Display character
        echo '<div class="rpg-character-profile">';
        echo '<h3>' . esc_html($active_character->post_title) . '</h3>';
        echo '<div class="rpg-character-details">';
        echo '<span class="rpg-character-class">' . esc_html(ucfirst($character_class)) . '</span>';
        echo '<span class="rpg-character-level">' . sprintf(__('Level %d', 'rpg-suite'), $character_level) . '</span>';
        echo '</div>';
        
        // Display character image
        echo '<div class="rpg-character-avatar">';
        if (has_post_thumbnail($active_character->ID)) {
            echo get_the_post_thumbnail($active_character->ID, 'thumbnail');
        } else {
            echo '<img src="' . esc_url(RPG_SUITE_PLUGIN_URL . 'assets/images/classes/' . $character_class . '.jpg') . '" alt="Character" />';
        }
        echo '</div>';
        
        // Character actions for profile owner
        if (bp_is_my_profile()) {
            echo '<div class="rpg-character-actions">';
            
            // Character management
            echo '<a href="' . admin_url('admin.php?page=rpg-character-management') . '" class="button">';
            echo __('Character Management', 'rpg-suite') . '</a>';
            
            echo '</div>';
        }
        
        echo '</div>';
        
        $displayed = true;
    }
    
    public function add_character_profile_tab() {
        // Only add if we're on a user profile
        if (!bp_is_user()) {
            return;
        }
        
        $user_id = bp_displayed_user_id();
        $active_character = $this->character_manager->get_active_character($user_id);
        
        // Only add if user has a character
        if (!$active_character) {
            return;
        }
        
        // Add tab
        bp_core_new_nav_item([
            'name' => __('Character', 'rpg-suite'),
            'slug' => 'character',
            'position' => 70,
            'screen_function' => [$this, 'character_screen_function'],
            'default_subnav_slug' => 'view',
        ]);
    }
    
    public function character_screen_function() {
        add_action('bp_template_content', [$this, 'character_screen_content']);
        bp_core_load_template('members/single/plugins');
    }
    
    public function character_screen_content() {
        $user_id = bp_displayed_user_id();
        $active_character = $this->character_manager->get_active_character($user_id);
        
        if (!$active_character) {
            echo '<div class="rpg-no-character">';
            echo '<p>' . __('No active character found.', 'rpg-suite') . '</p>';
            echo '</div>';
            return;
        }
        
        // Show character details
        $character_class = get_post_meta($active_character->ID, 'character_class', true);
        $character_level = get_post_meta($active_character->ID, 'character_level', true);
        
        echo '<div class="rpg-character-sheet">';
        echo '<h2>' . esc_html($active_character->post_title) . '</h2>';
        
        echo '<div class="rpg-character-meta">';
        echo '<p>' . sprintf(__('Level %d %s', 'rpg-suite'), $character_level, ucfirst($character_class)) . '</p>';
        echo '</div>';
        
        echo '<div class="rpg-character-description">';
        echo wpautop($active_character->post_content);
        echo '</div>';
        
        // Show character attributes
        echo '<div class="rpg-character-attributes">';
        echo '<h3>' . __('Attributes', 'rpg-suite') . '</h3>';
        echo '<ul>';
        
        $attributes = [
            'strength' => __('Strength', 'rpg-suite'),
            'dexterity' => __('Dexterity', 'rpg-suite'),
            'constitution' => __('Constitution', 'rpg-suite'),
            'intelligence' => __('Intelligence', 'rpg-suite'),
            'wisdom' => __('Wisdom', 'rpg-suite'),
            'charisma' => __('Charisma', 'rpg-suite'),
        ];
        
        foreach ($attributes as $key => $label) {
            $value = get_post_meta($active_character->ID, 'attribute_' . $key, true);
            echo '<li><strong>' . $label . ':</strong> ' . $value . '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
        
        echo '</div>';
    }
    
    public function enqueue_profile_css() {
        // Only enqueue on BuddyPress pages
        if (!function_exists('is_buddypress') || !is_buddypress()) {
            return;
        }
        
        wp_enqueue_style(
            'rpg-suite-profile',
            RPG_SUITE_PLUGIN_URL . 'assets/css/profile.css',
            [],
            RPG_SUITE_VERSION
        );
    }
}
```

#### Day 3-4: Character Switching UI
- Implement character switching in profiles
- Add character action buttons
- Create character switching handler

```php
// src/Character/class-character-switcher.php
namespace RPG\Suite\Character;

class Character_Switcher {
    protected $character_manager;
    protected $experience_manager;
    
    public function __construct($character_manager, $experience_manager) {
        $this->character_manager = $character_manager;
        $this->experience_manager = $experience_manager;
        
        // Register hooks
        add_action('init', [$this, 'register_switching_handler']);
        add_action('bp_member_header_actions', [$this, 'add_character_switching_ui'], 20);
        add_action('buddyx_member_header_actions', [$this, 'add_character_switching_ui'], 20);
    }
    
    public function register_switching_handler() {
        if (isset($_POST['rpg_action']) && $_POST['rpg_action'] === 'switch_character') {
            $this->handle_character_switch();
        }
    }
    
    protected function handle_character_switch() {
        // Verify nonce
        if (!isset($_POST['rpg_nonce']) || !wp_verify_nonce($_POST['rpg_nonce'], 'rpg_switch_character')) {
            wp_die(__('Security check failed.', 'rpg-suite'));
        }
        
        // Check character ID
        if (!isset($_POST['character_id']) || !is_numeric($_POST['character_id'])) {
            wp_die(__('Invalid character ID.', 'rpg-suite'));
        }
        
        $character_id = intval($_POST['character_id']);
        
        // Set as active character
        $result = $this->character_manager->set_active_character($character_id);
        
        if (!$result) {
            wp_die(__('Failed to switch character.', 'rpg-suite'));
        }
        
        // Redirect back to profile
        wp_redirect(bp_loggedin_user_domain());
        exit;
    }
    
    public function add_character_switching_ui() {
        // Only show for the profile owner
        if (!bp_is_my_profile()) {
            return;
        }
        
        // Only if feature is unlocked
        if (!$this->experience_manager->has_unlocked_feature('character_switching')) {
            return;
        }
        
        // Get user's living characters
        $characters = $this->character_manager->get_living_characters();
        
        // Only show if user has multiple characters
        if (count($characters) <= 1) {
            return;
        }
        
        // Get current active character
        $active_character = $this->character_manager->get_active_character();
        if (!$active_character) {
            return;
        }
        
        echo '<div class="rpg-character-switcher">';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="rpg_action" value="switch_character">';
        wp_nonce_field('rpg_switch_character', 'rpg_nonce');
        
        echo '<select name="character_id">';
        foreach ($characters as $character) {
            $selected = ($character->ID == $active_character->ID) ? 'selected' : '';
            echo '<option value="' . esc_attr($character->ID) . '" ' . $selected . '>' . esc_html($character->post_title) . '</option>';
        }
        echo '</select>';
        
        echo '<button type="submit" class="button">' . __('Switch Character', 'rpg-suite') . '</button>';
        echo '</form>';
        echo '</div>';
    }
}
```

#### Day 5: Theme CSS and Testing
- Create CSS for profile integration
- Test with different BuddyPress themes
- Finalize theme compatibility

```php
// assets/css/profile.css

/* Base styles for character display */
.rpg-character-profile {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    position: relative;
    z-index: 10;
    clear: both;
    font-family: 'Special Elite', 'Courier New', monospace;
}

.rpg-character-profile h3 {
    margin-top: 0;
    margin-bottom: 5px;
    font-family: 'Special Elite', 'Courier New', monospace;
    color: #6B4226;
}

.rpg-character-details {
    margin-bottom: 10px;
}

.rpg-character-class {
    font-weight: bold;
    margin-right: 10px;
}

.rpg-character-level {
    color: #B87333;
}

.rpg-character-avatar {
    float: left;
    margin-right: 15px;
    margin-bottom: 10px;
}

.rpg-character-avatar img {
    max-width: 100px;
    height: auto;
    border: 2px solid #B87333;
    border-radius: 5px;
}

.rpg-character-actions {
    clear: both;
    padding-top: 10px;
    text-align: right;
}

.rpg-character-actions .button {
    margin-left: 5px;
}

/* Character switching form */
.rpg-character-switcher {
    margin-top: 10px;
}

.rpg-character-switcher select {
    margin-right: 5px;
}

/* BuddyX theme specific styles */
.buddyx-user-container .rpg-character-profile {
    margin: 0;
    border-top: 1px solid #e5e5e5;
    padding-top: 15px;
    margin-top: 15px;
    border-radius: 0;
    background: transparent;
    border-left: 0;
    border-right: 0;
    border-bottom: 0;
}

.buddyx-user-details .rpg-character-profile h3 {
    font-size: 1.1em;
    margin-bottom: 10px;
}

/* Make sure our content stays within the card */
.buddyx-user-container .buddyx-user-info {
    overflow: visible !important;
}

/* Character sheet styles */
.rpg-character-sheet {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
}

.rpg-character-sheet h2 {
    margin-top: 0;
    color: #6B4226;
    font-family: 'Special Elite', 'Courier New', monospace;
}

.rpg-character-meta {
    margin-bottom: 20px;
    font-size: 18px;
}

.rpg-character-description {
    margin-bottom: 20px;
}

.rpg-character-attributes h3 {
    margin-top: 20px;
    margin-bottom: 10px;
    color: #6B4226;
}

.rpg-character-attributes ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.rpg-character-attributes li {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}
```

## Week 5: Admin Interface & Final Integration

#### Day 1-2: Admin Dashboard
- Create admin menu structure
- Implement character management page
- Add character creation page

#### Day 3-4: Progress Visualization
- Create XP tracking dashboard
- Implement feature unlock visualization
- Add character history display

#### Day 5: Final Testing & Documentation
- Test all features with BuddyPress
- Verify BuddyX theme compatibility
- Complete documentation

## Implementation Milestones

1. **Core Structure** (Week 1)
   - Working autoloader
   - Main plugin class with globals
   - Event system implementation
   - Character post type registration

2. **Character System** (Week 2)
   - Character creation and management
   - Random character generation
   - Character metadata structure
   - Character-player relationship

3. **Experience System** (Week 3)
   - XP tracking and awards
   - Feature unlocking system
   - Character death & respawn
   - Feature-based UI adaptation

4. **BuddyPress Integration** (Week 4)
   - Character display in profiles
   - Theme compatibility
   - Character switching UI
   - Character profile tab

5. **Admin Interface** (Week 5)
   - Character management dashboard
   - XP visualization
   - Feature unlock indication
   - Documentation and final testing