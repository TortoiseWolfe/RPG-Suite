# RPG-Suite Development Guide

This document outlines the development approach, architectural decisions, and implementation details for the RPG-Suite WordPress plugin.

## Development Approach

### Phase 1: Core Infrastructure

1. **Autoloader Setup**
   - Implement PSR-4 compliant class autoloader
   - Register autoloader in the main plugin file
   - Set up proper namespace structure

2. **Main Plugin Class**
   - Create the global plugin instance
   - Initialize subsystems in the correct order
   - Register activation/deactivation hooks

3. **Event System**
   - Implement Symfony-style event dispatcher
   - Create base event classes
   - Set up subscriber registration

4. **Character Post Type**
   - Register character post type
   - Define meta fields
   - Set up proper capability mapping

### Phase 2: Character Management

1. **Character Manager**
   - Create methods for getting/creating characters
   - Implement character-player relationship
   - Add active character tracking

2. **Random Character Generation**
   - Implement the four steampunk character classes
   - Create random name generation
   - Build attribute allocation based on class

3. **Character Death & Respawn**
   - Create death tracking and timestamps
   - Implement respawn mechanics with penalties
   - Add XP rewards for character lifetime

### Phase 3: BuddyPress Integration

1. **Profile Display Component**
   - Create BuddyPress integration class
   - Register BuddyPress hooks at the correct priority
   - Implement character display in profiles

2. **Theme Compatibility**
   - Add support for standard BuddyPress themes
   - Implement special handling for BuddyX theme
   - Create CSS for proper display

3. **Character Actions**
   - Add character switching in profiles
   - Implement character action buttons
   - Add character profile tab

### Phase 4: Progression System

1. **Experience Tracking**
   - Create user-level XP tracking
   - Implement XP awards for actions
   - Build XP history log

2. **Feature Unlocking**
   - Implement feature threshold checks
   - Create unlocking notification system
   - Add feature-based UI adaptation

3. **Admin Interface**
   - Build character management dashboard
   - Create progression visualization
   - Add feature unlocking information

## Architectural Decisions

### Global Access Pattern

The plugin uses a global variable for access across the site:

```php
// In main plugin file
function run_rpg_suite() {
    global $rpg_suite;
    $rpg_suite = new RPG\Suite\Includes\RPG_Suite();
    $GLOBALS['rpg_suite'] = $rpg_suite;
    $rpg_suite->run();
}

// Helper function
function rpg_suite() {
    global $rpg_suite;
    return $rpg_suite;
}
```

This approach ensures the plugin instance is accessible from anywhere, which is essential for template integration.

### Event-Driven Architecture

The plugin uses an event dispatcher to decouple subsystems:

1. **Event Classes**: Each event is a class that carries data
2. **Event Subscribers**: Subsystems implement the subscriber interface
3. **Event Dispatch**: The dispatcher triggers events for subscribers

This allows for:
- Clean separation between subsystems
- Extension through event listeners
- Filtering and modification of event data

### Experience-Based Feature System

Instead of role-based permissions, the plugin uses an experience-based progression system:

1. **User Experience**: Track XP at the user level, not character level
2. **Feature Thresholds**: Define XP requirements for each feature
3. **Access Checks**: Check XP levels for feature access
4. **UI Adaptation**: Show/hide UI elements based on unlocked features

This approach resolves permission issues while creating a compelling gameplay loop.

## Implementation Details

### Character Post Type Registration

```php
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
    
    // Standard capability type - using WordPress defaults 
    'capability_type' => 'post',
    'map_meta_cap' => true,
    
    // Menu integration
    'show_in_menu' => 'rpg-suite',
]);
```

### Random Character Generation

The random character generation system works as follows:

1. Select a random character class (Sky Captain, Inventor, etc.)
2. Generate attributes based on class-specific ranges
3. Assign starting equipment appropriate to the class
4. Generate a random name based on class themes
5. Create the character post and set metadata

```php
// Example implementation outline
function generate_random_character($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Check for existing character
    // Select random class
    // Generate attributes
    // Create character post
    // Set character metadata
    
    return $post_id;
}
```

### BuddyPress Hook Registration

BuddyPress integration requires careful hook timing:

```php
// Register with bp_init at priority 20
add_action('bp_init', [$this, 'initialize_buddypress_integration'], 20);

// In initialize_buddypress_integration method:
public function initialize_buddypress_integration() {
    if (function_exists('buddypress')) {
        // Add profile display hooks with multiple target points
        add_action('bp_member_header_inner_content', [$this, 'display_character_in_profile']);
        add_action('bp_before_member_header_meta', [$this, 'display_character_in_profile']);
        add_action('buddyx_member_header_meta', [$this, 'display_character_in_profile']);
        
        // Add character tab
        add_action('bp_setup_nav', [$this, 'add_character_profile_tab'], 100);
    }
}
```

### Feature Unlocking System

The feature unlocking system checks user XP against thresholds:

```php
// Feature thresholds
private $feature_thresholds = [
    'edit_character' => 1000,
    'character_respawn' => 2500,
    'multiple_characters' => 5000,
    'character_switching' => 7500,
    'advanced_customization' => 10000,
];

// Check if feature is unlocked
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
```

## Development Workflow

1. **Feature Branches**: Create a branch for each feature
2. **Commit Messages**: Use descriptive commit messages
3. **Code Review**: Pull requests for all significant changes
4. **Testing**: Test each feature with both BuddyPress and BuddyX
5. **Documentation**: Document all classes, methods, and hooks

## Debugging Approach

### BuddyPress Hook Debugging

To debug BuddyPress hooks in different themes:

```php
// Hook logging function
function log_buddypress_hook($hook_name) {
    error_log("BuddyPress hook fired: {$hook_name}");
}

// Register on potential hooks
foreach (['bp_member_header_inner_content', 'bp_before_member_header_meta', 'buddyx_member_header_meta'] as $hook) {
    add_action($hook, function() use ($hook) { 
        log_buddypress_hook($hook); 
    }, 1);
}
```

### Meta Data Debugging

For debugging character metadata:

```php
// Get all metadata for a character
function debug_character_meta($character_id) {
    $meta = get_post_meta($character_id);
    error_log("Character {$character_id} metadata: " . print_r($meta, true));
}
```

## Testing Strategy

1. **Character Creation**: Test random generation with all classes
2. **Character Management**: Test multiple characters per user
3. **BuddyPress Display**: Test with various themes and configurations
4. **Experience System**: Test XP awards and feature unlocking
5. **Death & Respawn**: Test character death and respawn mechanics
6. **Feature Access**: Test UI adaptation based on unlocked features

## Initial Implementation Tasks

1. Create plugin structure with autoloader
2. Implement main plugin class with global access
3. Create event system classes
4. Register character post type
5. Implement character manager
6. Add BuddyPress integration
7. Create experience tracking system
8. Implement random character generation
9. Add character death handling
10. Create basic admin interface