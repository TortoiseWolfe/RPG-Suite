# RPG-Suite: Core Implementation Principles

## Core Principles

1. **BuddyPress Integration First**: Design around BuddyPress profile display from the start
2. **Minimal Approach**: Use only what's required to accomplish each feature
3. **Practical Over Theoretical**: Choose working code over idealized architecture
4. **Direct Implementation**: Use WordPress and BuddyPress APIs directly
5. **Progressive Enhancement**: Start with one character per player, unlock multiple characters later

## Essential Structure

1. **Custom Post Type**: Use rpg_character post type with standard capabilities
2. **Character-Player Relationship**: Connect characters to players via post meta
3. **Multiple BuddyPress Hooks**: Target all profile hooks for theme compatibility
4. **Global Access**: Simple global variable with access function
5. **Active Character Flag**: Track which character is currently active

## MVP Features

1. BuddyPress profile display with character information
2. Character creation in admin
3. Character data stored in post meta
4. BuddyX theme compatibility
5. Character switching functionality

## Implementation Steps

1. Create plugin main file with global access
2. Register character post type
3. Add character meta fields
4. Implement BuddyPress profile integration
5. Add character switching functionality