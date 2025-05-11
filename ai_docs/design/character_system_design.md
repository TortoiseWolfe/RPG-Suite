# Character Management System Design

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

## Overview
The character management system is a core component of RPG-Suite that handles creation, storage, retrieval, and management of player characters using a unique d7-based system designed specifically for a steampunk world.

## Implementation Approach

Based on lessons learned, we're implementing the character system with these priorities:

1. First, establish a working custom post type with proper editing
2. Then implement basic character metadata
3. Finally add the more advanced features like the d7 system and character classes

## Data Model

### Character Post Type
Characters are stored as a custom post type with the following structure:

| Field | Description |
|-------|-------------|
| ID | WordPress post ID, unique identifier |
| post_title | Character name |
| post_content | Character description/biography |
| post_author | WordPress user ID (character owner) |
| post_status | Publication status (publish, draft, etc.) |

### Character Meta
Core character data stored in post meta:

| Meta Key | Type | Description |
|----------|------|-------------|
| _rpg_active | boolean | Whether this character is the user's active character |
| _rpg_attributes | array | Basic attributes for the d7 system |
| _rpg_class | string | Character class (Aeronaut, Mechwright, etc.) |

## Post Type Registration

The character post type is registered with standard post capabilities for simplicity and reliability:

```php
register_post_type('rpg_character', [
    'labels' => [
        'name' => __('Characters', 'rpg-suite'),
        'singular_name' => __('Character', 'rpg-suite'),
        // Other labels...
    ],
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,  // Block editor support
    'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
    'has_archive' => false,
    'capability_type' => 'post',  // Standard post capabilities
    'map_meta_cap' => true,
]);
```

## Steampunk d7 System

### Attributes
Core attributes for our unique d7 system:

- **Fortitude**: Physical strength and endurance
- **Precision**: Dexterity and hand-eye coordination
- **Intellect**: Intelligence and technical knowledge
- **Charisma**: Social ability and leadership

Each attribute uses a die code notation (e.g., "3d7+2").

### Character Classes

Four steampunk character classes:

1. **Aeronaut**: Airship pilots and navigators
2. **Mechwright**: Engineers who create mechanical devices
3. **Aethermancer**: Scientists who manipulate aether energy
4. **Diplomat**: Negotiators who navigate politics

## Core Functionality

### Character Manager
Simplified API for character operations:

```php
// Core operations
create_character($user_id, $data)
get_character($character_id)
get_user_characters($user_id)
get_active_character($user_id)
set_active_character($user_id, $character_id)
update_character($character_id, $data)
delete_character($character_id)
```

### Meta Field Registration

Register meta fields with proper authorization:

```php
register_post_meta('rpg_character', '_rpg_active', [
    'type' => 'boolean',
    'single' => true,
    'default' => false,
    'show_in_rest' => true,
    'auth_callback' => function($allowed, $meta_key, $post_id) {
        return current_user_can('edit_post', $post_id);
    }
]);

register_post_meta('rpg_character', '_rpg_class', [
    'type' => 'string',
    'single' => true,
    'default' => '',
    'show_in_rest' => true,
    'auth_callback' => function($allowed, $meta_key, $post_id) {
        return current_user_can('edit_post', $post_id);
    }
]);

register_post_meta('rpg_character', '_rpg_attributes', [
    'type' => 'object',
    'single' => true,
    'default' => [
        'fortitude' => '2d7',
        'precision' => '2d7',
        'intellect' => '2d7',
        'charisma' => '2d7'
    ],
    'show_in_rest' => [
        'schema' => [
            'type' => 'object',
            'properties' => [
                'fortitude' => ['type' => 'string'],
                'precision' => ['type' => 'string'],
                'intellect' => ['type' => 'string'],
                'charisma' => ['type' => 'string']
            ]
        ]
    ],
    'auth_callback' => function($allowed, $meta_key, $post_id) {
        return current_user_can('edit_post', $post_id);
    }
]);
```

### Meta Box Registration

Register meta boxes for character editing:

```php
add_meta_box(
    'rpg_character_class',
    __('Character Class', 'rpg-suite'),
    [$this, 'render_class_meta_box'],
    'rpg_character',
    'side',
    'high'
);

add_meta_box(
    'rpg_character_attributes',
    __('Character Attributes', 'rpg-suite'),
    [$this, 'render_attributes_meta_box'],
    'rpg_character',
    'normal',
    'high'
);
```

## User Flows

### Character Creation
1. User creates a new character post
2. User sets character class and attributes
3. System saves character metadata
4. If it's the user's first character, it's automatically set as active

### Character Activation
1. User views their characters
2. User selects a character to activate
3. Previous active character is deactivated
4. Selected character is marked as active

### Character Management
1. User views list of their characters
2. User can edit, delete, or activate each character

## Character Limit

### Default Limit
By default, users can have a maximum of 2 characters.

### Implementation
When creating a character, check the current count for the user against their limit (default is 2). If they've reached their limit, prevent creation and show an appropriate error message.

## BuddyPress Integration

The character management system integrates with BuddyPress by:

1. Displaying the active character on user profiles
2. Providing a character switching interface
3. Using BuddyPress hooks to render character information

The integration checks if the current page is a BuddyPress user profile, retrieves the active character for the displayed user, and renders the character information using appropriate templates and styles.

## Implementation Phases

The character system will be implemented in these phases:

1. **Phase 1: Basic Post Type**
   - Register character post type with standard capabilities
   - Ensure proper editing in WordPress admin
   - Add basic meta fields

2. **Phase 2: Character Management**
   - Implement active character tracking
   - Add character limit enforcement
   - Create character list views

3. **Phase 3: BuddyPress Integration**
   - Display active character on profiles
   - Add character switching interface

4. **Phase 4: Advanced Features**
   - Implement full d7 system
   - Add character skills
   - Add invention system