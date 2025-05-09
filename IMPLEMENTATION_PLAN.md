# RPG-Suite: Core Implementation Principles

## Core Principles

1. **BuddyPress Integration First**: Design around BuddyPress profile display from the start
2. **Minimal Approach**: Use only what's required to accomplish each feature
3. **Practical Over Theoretical**: Choose working code over idealized architecture
4. **Direct Implementation**: Use WordPress and BuddyPress APIs directly without abstraction layers
5. **Progressive Enhancement**: Start with one character per player, unlock additional features through gameplay

## Essential Structure

1. **Character Progression**: Start with one character, unlock multiple characters later
2. **Basic Post Type**: Use standard WordPress capabilities
3. **Direct File Inclusion**: No autoloaders or complex class loading
4. **Multiple BuddyPress Hooks**: Target all profile hooks for theme compatibility
5. **Global Access**: Simple global variable with access function

## Implementation Features

1. BuddyPress profile display with character information
2. Basic character creation in admin
3. Character data stored in post meta
4. Compatible with BuddyX theme
5. Character switching functionality for advanced players

## Character Storage

1. Custom post type with standard capabilities
2. User relationship via post meta
3. Character class and basic attributes
4. Active character flag for multiple character support

