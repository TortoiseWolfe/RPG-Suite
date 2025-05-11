# RPG-Suite (Revised)

## Project Overview

RPG-Suite is a WordPress plugin for implementing RPG (Role-Playing Game) mechanics on WordPress sites with BuddyPress integration. Players can create characters and display them on their BuddyPress profiles.

## Core Design Principles (Updated)

1. **Clean BuddyPress Integration**: Works with BuddyPress and BuddyX theme through standard hooks
2. **Character Management**: Multiple characters per user with active character tracking
3. **Simplified Architecture**: Focus on maintainable code without unnecessary complexity
4. **WordPress Standards**: Follow WordPress coding standards and practices
5. **Proper Testing**: Test user features in browser environments, not CLI

## Implementation Lessons Learned

1. **Custom Capabilities**: Use custom capability type with map_meta_cap for character post type
2. **Autoloader Implementation**: Preserve underscores in class names during autoloading
3. **Hook Registration**: Use standard BuddyPress hooks with normal priorities
4. **URL Handling**: Use direct admin URLs to avoid conflicts with other plugins
5. **Testing Methodology**: Only test user-dependent features in browser environments

## System Architecture

The plugin is built around these core components:

1. **Character System**: Custom post type for character data with player relationship
2. **BuddyPress Integration**: Display active character on user profiles
3. **Character Switching**: Allow players to switch between multiple characters
4. **Event System**: Dispatch and subscribe to character-related events

## Installation (Development)

1. Clone this repository into your WordPress plugins directory
2. Activate the plugin through the WordPress admin
3. Set up BuddyPress with the BuddyX theme (recommended)
4. Create characters through the WordPress admin
5. Character information will automatically display on BuddyPress profiles

## Implementation Priorities

1. Character post type with custom capabilities
2. BuddyPress profile integration using standard hooks
3. Character switching functionality
4. Character management interface

## Character Display Options

RPG-Suite provides these ways to display character information:

1. **BuddyPress Profiles**: Characters display automatically in BuddyPress user profiles
2. **Shortcode**: Use `[rpg_character_display]` shortcode in any page or post
3. **Admin Interface**: View and manage characters in the WordPress admin

## Development Guidelines

1. **Testing**:
   - Test non-user features via CLI
   - Test user-dependent features in browser environments
   - Don't add complex solutions for non-existent problems

2. **Coding Style**:
   - Follow WordPress coding standards
   - Use proper docblocks for all functions and classes
   - Keep functions small and focused

3. **Integration**:
   - Use standard hooks with standard priorities
   - Avoid excessive DOM manipulation
   - Keep CSS/JS simple and maintainable

## License

This project is licensed under the GPL v2 or later.