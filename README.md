# RPG-Suite

RPG-Suite is a WordPress plugin for implementing RPG (Role-Playing Game) mechanics on WordPress sites with BuddyPress integration. Players can create characters and display them on their BuddyPress profiles.

## Core Design Principles

1. **Clean BuddyPress Integration**: Works with BuddyPress and BuddyX theme through standard hooks
2. **Character Management**: Multiple characters per user with active character tracking
3. **Modern React UI**: React-based components for character management and display
4. **RESTful API**: Clean API endpoints for character data
5. **WordPress Standards**: Follow WordPress coding standards and practices
6. **Proper Testing**: Test user features in browser environments, not CLI

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
3. **Character Switching**: React-based UI for switching between multiple characters
4. **Event System**: Dispatch and subscribe to character-related events
5. **REST API**: RESTful endpoints for character data
6. **React Components**: Modern UI components for character management

## Installation (Development)

1. Clone this repository into your WordPress plugins directory
2. Build the React components:
   ```bash
   cd react-app
   npm install
   npm run build
   ```
3. Activate the plugin through the WordPress admin
4. Set up BuddyPress with the BuddyX theme (recommended)
5. Create characters through the WordPress admin
6. Character information will automatically display on BuddyPress profiles

## Implementation Priorities

1. Character post type with custom capabilities
2. BuddyPress profile integration using standard hooks
3. REST API endpoints for character data
4. React-based character switching functionality
5. React-based character sheet display

## Character Display Options

RPG-Suite provides these ways to display character information:

1. **BuddyPress Profiles**: Characters display automatically in BuddyPress user profiles
2. **Shortcode**: Use `[rpg_character_display]` shortcode in any page or post
3. **Admin Interface**: View and manage characters in the WordPress admin

## Development Guidelines

1. **Testing**:
   - Test non-user features via CLI
   - Test user-dependent features in browser environments
   - Test React components using React Testing Library
   - Don't add complex solutions for non-existent problems

2. **Coding Style**:
   - Follow WordPress coding standards for PHP
   - Follow React/ES6 best practices for JavaScript
   - Use proper docblocks for all functions and classes
   - Keep functions small and focused

3. **Integration**:
   - Use standard hooks with standard priorities
   - Use React components for UI elements
   - Keep CSS modular and maintainable

4. **React Development**:
   - Run `npm run dev` in the react-app directory for development mode
   - Run `npm run build` before deploying
   - Use the build script (`./build.sh`) for full plugin builds

5. **REST API**:
   - Use the WordPress REST API standards
   - Implement proper authentication and permissions
   - Document all endpoints thoroughly

## License

This project is licensed under the GPL v2 or later.