# RPG-Suite Implementation Plan

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

## Overview
This document outlines the step-by-step implementation plan for the RPG-Suite WordPress plugin, a roleplaying game system focused on character management with BuddyPress integration. Based on lessons learned, we're taking an incremental approach that focuses on getting core functionality working before adding complexity.

## Revised Implementation Phases

### Phase 1: Essential Plugin Structure
1. Create the main plugin file (rpg-suite.php)
2. Implement basic character post type registration
3. Add admin styles to ensure text visibility
4. Create meta boxes for character attributes

### Phase 2: Character System Core
1. Implement character metadata handling
2. Set up character ownership system
3. Implement active character tracking
4. Enable character limit (2 characters per player)

### Phase 3: BuddyPress Integration
1. Implement display of active character on profiles
2. Create character switching interface
3. Add proper BuddyPress hooks

### Phase 4: Advanced Features
1. Implement autoloader
2. Create main plugin class for global access
3. Implement event system
4. Create die code utility
5. Add advanced character features

## Detailed Implementation Steps

### Phase 1: Essential Plugin Structure

#### 1.1 Main Plugin File
- Create rpg-suite.php with plugin header
- Implement post type registration with standard capabilities
- Register activation and deactivation hooks
- Add admin styles for text visibility

#### Main Plugin File Requirements:

* Create primary plugin file with proper WordPress headers
* Register character post type with appropriate settings
* Ensure proper visibility in WordPress admin interface
* Set up appropriate hooks for initialization
* Establish proper user capabilities for editing

#### Character Post Type Requirements:

* Public visibility for frontend display
* REST API support for block editor compatibility
* Standard post capabilities for reliable permissions
* Support for essential post features (title, editor, etc.)
* Proper rewrite rules for permalinks

#### 1.2 Meta Fields
- Register meta fields for character attributes
- Create meta boxes for the admin editor
- Add basic validation and sanitization

#### 1.3 Activation/Deactivation
- Implement simple activation functions
- Register flush rewrite rules
- Handle basic cleanup on deactivation

### Phase 2: Character System Core

#### 2.1 Character Metadata
- Register character class and attributes meta
- Implement meta saving and retrieval
- Add validation for attribute values

#### 2.2 Active Character Handling
- Add functions to track active character
- Create system to set/get active character
- Implement active status changes

#### 2.3 Character Limit
- Track character count per user
- Enforce the two-character limit
- Allow admin override

### Phase 3: BuddyPress Integration

#### 3.1 Profile Display
- Add hooks for BuddyPress profile display
- Create character display in profile header
- Style character information for BuddyX theme

#### BuddyPress Integration Requirements:

* Hook into appropriate BuddyPress display locations
* Retrieve active character for displayed user
* Display character name, class, and attributes
* Style character information for visual clarity
* Ensure security through proper data escaping
* Only show character data on appropriate profile pages

#### 3.2 Character Switching
- Create interface for listing characters
- Implement switching functionality
- Add character management screen

### Phase 4: Advanced Features

#### 4.1 Autoloader
- Implement PSR-4 compatible autoloader
- Convert procedural code to OOP
- Organize classes in appropriate directories

#### 4.2 Main Plugin Class
- Create central plugin class
- Expose components as public properties
- Set up global access via $rpg_suite variable

#### 4.3 Event System
- Implement basic event dispatcher
- Add event subscribers
- Connect character actions to events

#### 4.4 Die Code Utility
- Create die code parsing
- Implement d7 dice system
- Add character attribute handling

## Revised Development Order

For efficient implementation, the development will follow this order:

1. **Phase 1**: Focus on getting the character post type working correctly in WordPress admin
2. **Phase 2**: Implement basic character metadata and active character handling
3. **Phase 3**: Add BuddyPress integration to display characters on profiles
4. **Phase 4**: Refactor to OOP and add advanced features only after core functionality works

This revised order ensures we have a working minimal version before adding complexity.

## MVP Features
The essential MVP features are:

1. Working character post type with proper editing in WordPress admin
2. Basic character attributes and class selection
3. Multiple characters per user with one active
4. Character display on BuddyPress profiles
5. Character switching functionality

## Testing Strategy

Testing will focus on specific functionality in appropriate environments:

1. **Character Editing**: Test in browser with WordPress admin
2. **Text Visibility**: Verify text is visible in the editor
3. **Profile Display**: Test BuddyPress integration in browser
4. **Compatibility**: Test with other plugins like Yoast SEO

## Core Implementation Principles

1. **Simplicity First**: Start with the simplest implementation that works
2. **STANDARD CAPABILITIES ONLY**: Always use standard post capabilities for custom post types to prevent editing errors
3. **CONSISTENT CAPABILITY CHECKS**: Always use 'edit_post' for capability checks, never custom capabilities like 'edit_rpg_character'
4. **Incremental Complexity**: Only add architectural patterns after basics work
5. **Browser Testing**: Test all features in a browser environment
6. **Visual Verification**: Ensure all UI elements are properly visible
7. **Standard WordPress Patterns**: Follow WordPress conventions for reliability
8. **EXPLICIT CAPABILITY MAPPING**: Always set 'map_meta_cap' to true and explicitly define capabilities array

## Critical Implementation Lessons

1. **Post Type Registration**: ALWAYS use standard post capabilities ('post') for custom post types
2. **Prevent "Item Doesn't Exist" Errors**: Explicitly define capabilities array with standard WordPress capabilities
3. **Consistent Capability Checks**: Always use 'edit_post', never custom capabilities like 'edit_rpg_character'
4. **Auth Callbacks**: Use standard edit_post capability in all meta field auth callbacks
5. **Admin Styling**: Ensure text is visible in the editor
6. **Testing Environment**: Always test in a browser, not CLI
7. **Incremental Approach**: Get basic functionality working before adding complexity
8. **Proper Hook Timing**: Register hooks at the appropriate time in the WordPress lifecycle

## Next Steps

Implementation will begin with Phase 1: Essential Plugin Structure, focusing on getting the character post type working correctly in the WordPress admin.