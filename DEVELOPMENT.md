# RPG-Suite Development Guide (Revised)

This document outlines the core development approach for the RPG-Suite WordPress plugin, incorporating lessons learned from previous implementation.

## Implementation Phases

### Phase 1: Core Foundation

1. **Plugin Structure**
   - Create main plugin file with global access pattern
   - Set up activation/deactivation hooks
   - Implement proper autoloader that preserves underscores in class names

2. **Character System**
   - Register character post type with custom capability type
   - Set up relationship between players and characters
   - Use proper auth callbacks for character meta fields

3. **BuddyPress Integration**
   - Display active character on BuddyPress profiles using standard hooks
   - Ensure compatibility with BuddyX theme through standard hooks
   - Use direct admin URLs to avoid conflicts with other plugins

### Phase 2: Character Progression

1. **Multiple Characters**
   - Allow players to create multiple characters
   - Implement active character tracking
   - Use post meta for character ownership and active status

2. **Character Switching**
   - Allow character switching from profile
   - Store active character in character meta, not user meta
   - Test in browser environment with real user sessions

## Global Access Pattern

The plugin uses a global variable for access across the site:

```php
// In main plugin file
function rpg_suite_init() {
  global $rpg_suite;
  $rpg_suite = new RPG_Suite();
  $rpg_suite->run();
}

// Helper function
function rpg_suite() {
  global $rpg_suite;
  return $rpg_suite;
}
```

## Character Post Type

```php
register_post_type('rpg_character', [
  'labels' => [
    'name' => __('Characters', 'rpg-suite'),
    'singular_name' => __('Character', 'rpg-suite'),
  ],
  'public' => true,
  'has_archive' => true,
  'menu_icon' => 'dashicons-admin-users',
  'supports' => ['title', 'editor', 'thumbnail', 'author', 'custom-fields'],
  'capability_type' => 'rpg_character', // Custom capability type
  'map_meta_cap' => true,               // Enable capability mapping
]);
```

## BuddyPress Integration

```php
// Register hook for BuddyPress profiles with standard priority
add_action('bp_init', 'register_buddypress_hooks', 20);

function register_buddypress_hooks() {
  if (function_exists('buddypress')) {
    // Use standard hook points without excessive registrations
    add_action('bp_member_header_meta', 'display_character_in_profile');
    
    // BuddyX theme hook if needed
    if ('buddyx' === wp_get_theme()->get_template()) {
      add_action('buddyx_member_header_meta', 'display_character_in_profile');
    }
  }
}
```

## Meta Registration

```php
// Register character meta fields with proper auth callbacks
register_post_meta('rpg_character', '_rpg_attribute_fortitude', [
  'type' => 'string',
  'description' => 'Character fortitude attribute',
  'single' => true,
  'show_in_rest' => true,
  'auth_callback' => function($allowed, $meta_key, $post_id) {
    // Check post type AND capability
    return get_post_type($post_id) === 'rpg_character' && 
           current_user_can('edit_rpg_character', $post_id);
  }
]);
```

## URL Construction

```php
// Use direct admin URLs to avoid conflicts
function get_character_edit_url($character_id) {
  return admin_url('post.php?post=' . $character_id . '&action=edit');
}
```

## Testing Strategy

1. **CLI Testing**: Use for non-user-dependent features only
   - Autoloader functionality
   - Post type registration
   - Meta field registration

2. **Browser Testing**: Required for user-dependent features
   - BuddyPress profile display
   - Character switching
   - Character editing
   - Permission checks

3. **Test Environment**:
   - WordPress 6.8+
   - BuddyPress 14.3+
   - BuddyX theme 4.8+
   - PHP 8.2+