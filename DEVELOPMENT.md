# RPG-Suite Development Guide

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

## Overview

RPG-Suite is a WordPress plugin that adds role-playing game functionality to WordPress sites using BuddyPress. It features a unique d7-based dice system set in a steampunk world of airships and mechanical inventions.

## Development Philosophy

Based on our lessons learned, we've adopted a **Simplicity First** approach. Rather than building a complex architecture upfront, we're implementing the plugin incrementally, starting with the most essential functionality:

1. First, establish a working character post type with proper editing
2. Then add character attributes and BuddyPress integration
3. Finally implement the more complex systems (dice, events, etc.)

## Current Focus Areas

We're focusing on two critical issues:

1. **Character Editing**: Ensuring characters can be properly created and edited in WordPress admin
2. **Editor Visibility**: Making sure text is visible (not white on white) in the WordPress editor

## Technical Implementation

### Character System

The character system uses WordPress custom post types with standard capabilities:

```php
register_post_type('rpg_character', [
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,  // Enable block editor support
    'capability_type' => 'post',  // Standard post capabilities
    'map_meta_cap' => true,
]);
```

Character attributes are stored as post meta:

- **_rpg_active**: Whether this is the user's active character (boolean)
- **_rpg_class**: Character class/profession (string)
- **_rpg_attributes**: Character attributes for the d7 system (object)

### Admin Visibility Fix

To ensure text is visible in the WordPress editor:

```php
function rpg_suite_admin_styles() {
    echo '<style>
        .editor-styles-wrapper {
            color: #333 !important;
        }
    </style>';
}
add_action('admin_head', 'rpg_suite_admin_styles');
```

### BuddyPress Integration

BuddyPress integration displays the active character on user profiles using standard hooks:

```php
add_action('bp_before_member_header_meta', 'rpg_suite_display_character');
```

Character switching is implemented through a simple interface on the profile page.

## Implementation Phases

### Phase 1: Core Functionality
- Working character post type with proper editing
- Basic character attributes and metadata
- Admin styles for text visibility

### Phase 2: Character Management
- Support for multiple characters per user
- Active character tracking
- Character limit (2 per user by default)

### Phase 3: BuddyPress Integration
- Display character on user profiles
- Character switching functionality
- BuddyX theme compatibility

### Phase 4: Advanced Features
- D7 dice system implementation
- Character skills and progression
- Event system for plugin communication

## Development Guidelines

### Testing Requirements
- Always test character creation/editing in a browser
- Verify visual appearance of all elements
- Test with different user roles
- Check compatibility with Yoast SEO and other plugins

### Code Organization
- Start with simple procedural approach
- Move to OOP after core functionality works
- Use WordPress coding standards
- Properly document all functions and hooks

### Debugging
- Be aware of potential debug output affecting headers
- Check for white text on white backgrounds
- Test all features with debug mode enabled

## Implementation Priority

1. **Fix character editing functionality** in WordPress admin
   - Use standard post capabilities
   - Enable block editor support
   - Add visible styles for the editor

2. **Implement character metadata**
   - Store character class and attributes
   - Add meta boxes to the editor
   - Validate and sanitize data

3. **Add BuddyPress integration**
   - Display character on profiles
   - Create character switching
   - Style for BuddyX theme

4. **Enable multiple characters**
   - Track active status
   - Enforce character limits
   - Allow character management

5. **Implement d7 system features**
   - Die code utilities
   - Character progression
   - Skills and inventions