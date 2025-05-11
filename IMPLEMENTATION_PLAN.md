# RPG-Suite: Core Implementation Principles (Revised)

## Core Principles

1. **BuddyPress Integration First**: Design around BuddyPress profile display from the start
2. **Minimal Approach**: Use only what's required to accomplish each feature
3. **Practical Over Theoretical**: Choose working code over idealized architecture
4. **Direct Implementation**: Use WordPress and BuddyPress APIs directly
5. **Progressive Enhancement**: Start with one character per player, unlock multiple characters later
6. **Simple Testing**: Test user-dependent features in browser environments only

## Essential Structure

1. **Custom Post Type**: Use rpg_character post type with **custom** capabilities
2. **Character-Player Relationship**: Connect characters to players via post meta
3. **Focused BuddyPress Hooks**: Use primary profile hooks with standard priorities
4. **Global Access**: Simple global variable with access function
5. **Active Character Flag**: Track which character is currently active

## Critical Implementation Lessons

1. **Proper Autoloading**: Correctly handle class names with underscores
2. **Custom Capabilities**: Register post type with custom capability type and map_meta_cap
3. **Appropriate Testing**: Only test user features in browser environments, not CLI
4. **Avoid Overengineering**: Don't add complexity to solve non-existent problems
5. **URL Construction**: Use direct admin URLs to avoid conflicts with other plugins
6. **Standard Hooks**: Use standard BuddyPress hooks rather than excessive hook registrations

## MVP Features

1. BuddyPress profile display with character information
2. Character creation in admin
3. Character data stored in post meta
4. BuddyX theme compatibility
5. Character switching functionality

## Implementation Steps

1. Create plugin main file with global access
2. Register character post type with custom capabilities
3. Add character meta fields with proper auth callbacks
4. Implement BuddyPress profile integration using standard hooks
5. Add character switching functionality with proper browser testing