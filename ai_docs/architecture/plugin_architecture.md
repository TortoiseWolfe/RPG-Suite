# RPG-Suite Plugin Architecture

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

## High-Level Architecture

RPG-Suite follows a modular architecture with clear separation of concerns. The architectural structure has been simplified based on lessons learned, focusing first on core functionality before adding complexity.

### Directory Structure

```
RPG-Suite/
├── rpg-suite.php              # Plugin main file
├── uninstall.php              # Clean uninstallation procedures
├── includes/                  # Core plugin functionality
│   ├── class-autoloader.php   # PSR-4 autoloader
│   ├── class-rpg-suite.php    # Main plugin class
│   ├── class-activator.php    # Plugin activation logic
│   ├── class-deactivator.php  # Plugin deactivation logic
│   ├── core/                  # Core functionality
│   ├── character/             # Character management
│   ├── admin/                 # Admin functionality
│   ├── integrations/          # Third-party integrations
│   └── assets/                # Frontend assets
```

## Development Approach

The architecture is designed to be implemented in phases, with a focus on getting the core functionality working first before adding more complex features:

1. **Phase 1: Core Plugin Structure**
   - Main plugin class
   - Autoloader
   - Post type registration
   - Basic admin interface

2. **Phase 2: Character System**
   - Character management
   - Character post editing
   - Character metadata handling

3. **Phase 3: BuddyPress Integration**
   - Profile display
   - Character switching

4. **Phase 4: Advanced Features**
   - Event system
   - Die code utility
   - Template system

## Component Responsibilities

### Main Plugin Class (`RPG_Suite`)
- Initializes the plugin
- Provides global access point via `$rpg_suite`
- Registers core hooks
- Initializes key components as public properties

### Autoloader
- Handles class loading
- Maps namespaces to directories
- Uses PSR-4 convention

### Character System
- Registers the character post type with appropriate capabilities
- Handles character metadata storage and retrieval
- Manages active character status for users
- Supports multiple characters per player (limit of 2 by default)

### BuddyPress Integration
- Displays active character on BuddyPress profiles
- Hooks into BuddyPress at the correct time in the lifecycle
- Provides character switching UI

## WordPress Integration

### Character Post Type
- Uses standard post capabilities for simplicity
- Ensures proper post editing in WordPress admin
- Registers meta fields for character attributes

```
register_post_type('rpg_character', [
    'labels' => [...],
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,  // Enable block editor support
    'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
    'has_archive' => false,
    'capability_type' => 'post',  // Use standard post capabilities
    'map_meta_cap' => true,
]);
```

### Meta Fields
- Register character attributes as post meta
- Use standard meta API for compatibility

## Global Accessibility

Components are designed to be accessible throughout the plugin:

- Public properties on main plugin class
- Global `$rpg_suite` variable
- Consistent initialization order

## Implementation Priorities

1. **Post Type Functionality**
   - Ensure character post type is properly registered
   - Verify editing works correctly in WordPress admin
   - Ensure text is properly visible in editor

2. **Character Management**
   - Store character ownership
   - Allow multiple characters per player
   - Track active character status

3. **BuddyPress Display**
   - Show active character on profile
   - Enable character switching

## CSS Implementation

- Admin styles ensure proper text visibility in editor
- BuddyPress styles use appropriate specificity
- Avoid excessive !important declarations

## Testing Strategy

- Test post type functionality first in browser environment
- Verify actual user experience
- Ensure compatibility with Yoast SEO and other common plugins

## Coding Standards

The plugin follows WordPress Coding Standards:
- WordPress naming conventions for functions and hooks
- PSR-4 for class names and namespaces
- Comprehensive inline documentation