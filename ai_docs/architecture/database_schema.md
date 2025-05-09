# Database Schema

RPG-Suite uses WordPress custom post types and meta tables for data storage. This approach leverages WordPress's existing database structure while providing the flexibility needed for the RPG system.

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

### Invention Post Type (`rpg_invention`)

| Field        | Type        | Description                          |
|--------------|-------------|--------------------------------------|
| ID           | bigint(20)  | Post ID (primary key)                |
| post_author  | bigint(20)  | User ID of invention creator         |
| post_title   | text        | Invention name                       |
| post_content | longtext    | Invention description                |
| post_status  | varchar(20) | Publication status (publish, draft)  |
| post_type    | varchar(20) | Always 'rpg_invention'               |
| post_date    | datetime    | Invention creation date              |
| post_modified| datetime    | Last modification date               |

## Post Meta Tables

### Character Meta

| Meta Key               | Type    | Description                             | Example                          |
|------------------------|---------|-----------------------------------------|----------------------------------|
| _rpg_active            | boolean | Whether this is user's active character | 1                                |
| _rpg_class             | string  | Character class/profession              | "Aeronaut"                       |
| _rpg_attributes        | array   | Character attributes with d7 die codes  | {"Fortitude":"3d7+1",...}        |
| _rpg_skills            | array   | Character skills with d7 die codes      | {"Piloting":{"attribute":"Precision","value":"4d7+2"},...} |
| _rpg_derived_stats     | array   | Derived statistics                      | {"Movement":8,"Vitality":24,...} |
| _rpg_invention_points  | integer | Points for creating inventions          | 12                               |
| _rpg_fate_tokens       | integer | Tokens for major game effects           | 3                                |
| _rpg_creation_complete | boolean | Whether character creation is complete  | 1                                |

### Invention Meta

| Meta Key               | Type    | Description                              | Example                          |
|------------------------|---------|------------------------------------------|----------------------------------|
| _rpg_character_id      | integer | ID of the inventing character            | 42                               |
| _rpg_complexity        | integer | Complexity level of the invention        | 18                               |
| _rpg_components        | array   | Components required for the invention    | ["brass gears","aether crystal"] |
| _rpg_effects           | string  | Effects of the invention when used       | "Creates a small force field"    |
| _rpg_invention_type    | string  | Category of invention                    | "Defensive"                      |

## User Meta

| Meta Key                | Type    | Description                              | Example                          |
|-------------------------|---------|------------------------------------------|----------------------------------|
| _rpg_character_limit    | integer | Maximum number of characters allowed     | 3                                |
| _rpg_settings           | array   | User preferences for the RPG system      | {"dice_animation":true,...}      |

## Indexes and Performance

To optimize database queries, the following indexes are recommended:

1. On `wp_postmeta` table:
   - Index on `meta_key` and `meta_value` for active character queries
   - Index on `meta_key` and `post_id` for character attribute queries

2. Query Optimization:
   - Use `meta_query` with proper indexing when fetching characters
   - Cache frequently accessed character data using WordPress Transients API

## Example Queries

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

### Get Character Inventions

```php
$args = [
    'post_type' => 'rpg_invention',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_rpg_character_id',
            'value' => $character_id,
        ]
    ]
];
$query = new WP_Query($args);
```

## Database Upgrade Procedures

The plugin includes a version-tracking system that handles database schema changes:

1. Plugin version stored in options table
2. On activation, check stored version against current version
3. If different, run appropriate upgrade procedures
4. Update stored version number

## Data Sanitization and Validation

All data stored in the database is properly sanitized:

1. Character attributes: validated against die code format
2. User inputs: sanitized using WordPress sanitization functions
3. Meta values: serialized data is carefully handled to prevent object injection

## Backup and Restore

The plugin supports export/import of character data:

1. Export character data to JSON format
2. Import character data from JSON
3. Character data included in standard WordPress database backups