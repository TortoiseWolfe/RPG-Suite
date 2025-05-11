# Database Schema

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

RPG-Suite leverages WordPress's built-in custom post types and meta tables for data storage. This approach simplifies development by using WordPress's existing database structure while providing the flexibility needed for the RPG system.

## Implementation Phases

The database schema will be implemented in phases, following our incremental development approach:

1. **Phase 1**: Basic character post type with minimal meta fields
2. **Phase 2**: Additional character attributes and class information
3. **Phase 3**: Advanced features like skills and inventions

## Custom Post Types

### Character Post Type (`rpg_character`)

| Field        | Type        | Description                          |
|--------------|-------------|--------------------------------------|
| ID           | bigint(20)  | Post ID (primary key)                |
| post_author  | bigint(20)  | User ID of character owner           |
| post_title   | text        | Character name                       |
| post_content | longtext    | Character biography/description      |
| post_status  | varchar(20) | Publication status (publish, draft)  |
| post_type    | varchar(20) | Always 'rpg_character'               |
| post_date    | datetime    | Character creation date              |
| post_modified| datetime    | Last modification date               |

## Post Meta - Initial Implementation

The initial implementation focuses on essential meta fields:

### Character Meta (Phase 1)

| Meta Key               | Type    | Description                             | Example                    |
|------------------------|---------|-----------------------------------------|----------------------------|
| _rpg_active            | boolean | Whether this is user's active character | 1                          |
| _rpg_class             | string  | Character class/profession              | "Aeronaut"                 |
| _rpg_attributes        | array   | Basic character attributes              | {"fortitude":"2d7",...}    |

### Registration of Meta Fields

Meta fields are registered with the WordPress REST API for block editor support:

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

## Post Meta - Future Phases

In later phases, we'll add more complex meta fields:

### Character Meta (Phase 2-3)

| Meta Key               | Type    | Description                             | Example                          |
|------------------------|---------|-----------------------------------------|----------------------------------|
| _rpg_skills            | array   | Character skills with d7 die codes      | {"Piloting":{"attribute":"Precision","value":"4d7+2"},...} |
| _rpg_invention_points  | integer | Points for creating inventions          | 12                               |
| _rpg_fate_tokens       | integer | Tokens for major game effects           | 3                                |

### User Meta (Phase 2-3)

| Meta Key                | Type    | Description                          | Example        |
|-------------------------|---------|--------------------------------------|----------------|
| _rpg_character_limit    | integer | Maximum number of characters allowed | 2              |

## Common Queries

### Get Active Character

```php
$args = [
    'post_type' => 'rpg_character',
    'author' => $user_id,
    'posts_per_page' => 1,
    'meta_query' => [
        [
            'key' => '_rpg_active',
            'value' => true,
        ]
    ]
];
$query = new WP_Query($args);
```

### Get User's Characters

```php
$args = [
    'post_type' => 'rpg_character',
    'author' => $user_id,
    'posts_per_page' => -1,
];
$query = new WP_Query($args);
```

## Data Sanitization

All data stored in the database will be properly sanitized:

```php
// Sanitize a text field
$class = sanitize_text_field($_POST['rpg_class']);

// Sanitize an array of attributes
$attributes = [];
foreach ($_POST['rpg_attributes'] as $key => $value) {
    $attributes[sanitize_key($key)] = sanitize_text_field($value);
}
```

## Performance Considerations

For optimal performance:

1. Use WordPress's built-in caching for frequently accessed data
2. Minimize complex meta queries
3. Use standard WordPress query patterns

## Migration Strategy

As the plugin evolves from phase to phase:

1. Store plugin version in the options table
2. On upgrade, check for database schema changes
3. Add new meta fields as needed
4. Provide default values for new fields