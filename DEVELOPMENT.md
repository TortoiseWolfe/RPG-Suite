# RPG-Suite Development Guide

This document outlines the core development approach for the RPG-Suite WordPress plugin.

## Implementation Phases

### Phase 1: Core Foundation

1. **Plugin Structure**
   - Create main plugin file with global access pattern
   - Set up activation/deactivation hooks

2. **Character System**
   - Register character post type
   - Set up relationship between players and characters

3. **BuddyPress Integration**
   - Display active character on BuddyPress profiles
   - Ensure compatibility with BuddyX theme

### Phase 2: Character Progression

1. **Multiple Characters**
   - Allow players to create multiple characters
   - Implement active character tracking

2. **Character Switching**
   - Allow character switching from profile
   - Store active character in user meta

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
  'capability_type' => 'post',
  'map_meta_cap' => true,
]);
```

## BuddyPress Integration

```php
// Register hook for BuddyPress profiles
add_action('bp_init', 'register_buddypress_hooks', 20);

function register_buddypress_hooks() {
  if (function_exists('buddypress')) {
    // Add profile display hooks
    add_action('bp_member_header_inner_content', 'display_character_in_profile');
    add_action('bp_before_member_header_meta', 'display_character_in_profile');
    add_action('buddyx_member_header_meta', 'display_character_in_profile');
  }
}
```

## Testing Strategy

1. Create and manage characters in admin
2. Verify BuddyPress profile display
3. Test character switching
4. Verify compatibility with BuddyX theme