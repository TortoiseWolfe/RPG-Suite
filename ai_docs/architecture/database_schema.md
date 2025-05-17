# Database Schema

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

RPG-Suite leverages WordPress's built-in custom post types and meta tables for data storage, with additional caching layers for performance optimization. The schema is designed to support both traditional WordPress queries and React-based real-time updates.

## Implementation Phases

The database schema implementation follows our revised approach:

1. **Phase 1**: Fix character post type and basic meta fields
2. **Phase 2**: Add caching tables and API metadata
3. **Phase 3**: Optimize for React real-time updates
4. **Phase 4**: Advanced features and performance tuning

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

## Post Meta - Current Implementation

### Character Meta (Phase 1-2)

| Meta Key               | Type    | Description                             | Example                    |
|------------------------|---------|-----------------------------------------|----------------------------|
| _rpg_active            | boolean | Whether this is user's active character | 1                          |
| _rpg_class             | string  | Character class/profession              | "Aeronaut"                 |
| _rpg_attributes        | array   | Basic character attributes              | {"fortitude":"2d7",...}    |
| _rpg_last_modified     | string  | ISO timestamp for cache invalidation    | "2025-05-17T12:00:00Z"     |
| _rpg_revision_id       | string  | Revision ID for React updates           | "abc123"                   |
| _rpg_cache_version     | int     | Cache version for invalidation          | 2                          |

### Registration of Meta Fields with REST API Support

Meta fields are registered with enhanced REST API support for React:

```php
register_post_meta('rpg_character', '_rpg_active', [
    'type' => 'boolean',
    'single' => true,
    'default' => false,
    'show_in_rest' => true,
    'auth_callback' => function($allowed, $meta_key, $post_id) {
        return current_user_can('edit_rpg_character', $post_id);
    }
]);

register_post_meta('rpg_character', '_rpg_class', [
    'type' => 'string',
    'single' => true,
    'default' => '',
    'show_in_rest' => true,
    'auth_callback' => function($allowed, $meta_key, $post_id) {
        return current_user_can('edit_rpg_character', $post_id);
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
        return current_user_can('edit_rpg_character', $post_id);
    }
]);

// Caching and versioning meta
register_post_meta('rpg_character', '_rpg_last_modified', [
    'type' => 'string',
    'single' => true,
    'show_in_rest' => true,
    'default' => '',
    'auth_callback' => '__return_true'
]);

register_post_meta('rpg_character', '_rpg_revision_id', [
    'type' => 'string',
    'single' => true,
    'show_in_rest' => true,
    'default' => '',
    'auth_callback' => '__return_true'
]);
```

## Caching Schema

### Options Table Entries

| Option Name                          | Type   | Description                        |
|-------------------------------------|--------|------------------------------------|
| rpg_suite_cache_version             | int    | Global cache version               |
| rpg_suite_character_cache_keys      | array  | List of cached character IDs       |
| rpg_suite_api_cache_endpoints       | array  | Cached API endpoint versions       |

### Transients for Caching

| Transient Key Pattern               | Type   | Description                        | TTL    |
|------------------------------------|--------|------------------------------------|--------|
| rpg_character_{id}                 | object | Full character data                | 1 hour |
| rpg_user_characters_{user_id}      | array  | User's character list              | 1 hour |
| rpg_active_character_{user_id}     | int    | User's active character ID         | 1 hour |
| rpg_character_revision_{id}_{rev}  | object | Character revision data            | 1 day  |

### Custom Cache Table (Optional)

For high-performance installations:

```sql
CREATE TABLE {$wpdb->prefix}rpg_cache (
    cache_key VARCHAR(191) PRIMARY KEY,
    cache_value LONGTEXT,
    expiration DATETIME,
    group_name VARCHAR(50),
    INDEX idx_expiration (expiration),
    INDEX idx_group (group_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## API Response Optimization

### REST API Headers

```php
// Add caching headers to REST responses
add_filter('rest_post_dispatch', function($response, $server, $request) {
    if (strpos($request->get_route(), '/rpg-suite/v1') === 0) {
        $response->header('X-RPG-Cache-Version', get_option('rpg_suite_cache_version', 1));
        $response->header('Cache-Control', 'private, max-age=300');
        $response->header('X-RPG-Revision', get_post_meta($request['id'], '_rpg_revision_id', true));
    }
    return $response;
}, 10, 3);
```

## Real-time Update Schema

### Character Update Events

| Event Type        | Payload                                        | Description                    |
|------------------|------------------------------------------------|--------------------------------|
| character.updated | {id, field, value, revision_id, timestamp}     | Single field update            |
| character.switched| {user_id, old_id, new_id, timestamp}           | Active character changed       |
| character.created | {id, user_id, data, timestamp}                 | New character created          |
| character.deleted | {id, user_id, timestamp}                       | Character deleted              |

## Performance Optimization Queries

### Get Active Character with Cache

```php
function rpg_get_active_character($user_id) {
    $cache_key = 'rpg_active_character_' . $user_id;
    $character_id = get_transient($cache_key);
    
    if (false === $character_id) {
        $args = [
            'post_type' => 'rpg_character',
            'author' => $user_id,
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_rpg_active',
                    'value' => true,
                ]
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
        ];
        
        $query = new WP_Query($args);
        $character_id = $query->posts[0] ?? null;
        set_transient($cache_key, $character_id, HOUR_IN_SECONDS);
    }
    
    return $character_id ? rpg_get_character($character_id) : null;
}
```

### Get User's Characters with Caching

```php
function rpg_get_user_characters($user_id) {
    $cache_key = 'rpg_user_characters_' . $user_id;
    $characters = get_transient($cache_key);
    
    if (false === $characters) {
        $args = [
            'post_type' => 'rpg_character',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'post_status' => 'publish',
        ];
        
        $query = new WP_Query($args);
        $characters = $query->posts;
        set_transient($cache_key, $characters, HOUR_IN_SECONDS);
    }
    
    return $characters;
}
```

## Data Sanitization and Validation

All data is sanitized before storage and validated for React components:

```php
// Enhanced sanitization for React compatibility
function rpg_sanitize_character_data($data) {
    $sanitized = [];
    
    // Basic fields
    $sanitized['name'] = sanitize_text_field($data['name'] ?? '');
    $sanitized['class'] = sanitize_text_field($data['class'] ?? '');
    
    // Attributes with validation
    if (isset($data['attributes']) && is_array($data['attributes'])) {
        foreach ($data['attributes'] as $key => $value) {
            if (preg_match('/^\d+d\d+(\+\d+)?$/', $value)) {
                $sanitized['attributes'][sanitize_key($key)] = $value;
            }
        }
    }
    
    // Add revision tracking
    $sanitized['revision_id'] = wp_generate_uuid4();
    $sanitized['last_modified'] = current_time('c');
    
    return $sanitized;
}
```

## Performance Considerations

For optimal performance with React frontend:

1. **Query Optimization**:
   - Use field limiting (`fields` parameter)
   - Avoid `found_rows` when not needed
   - Use proper indexes on meta queries

2. **Caching Strategy**:
   - Cache at multiple levels (object, transient, HTTP)
   - Implement cache warming for popular data
   - Use cache tags for targeted invalidation

3. **Real-time Updates**:
   - Use revision IDs to prevent conflicts
   - Implement optimistic updates in React
   - Queue bulk updates for efficiency

4. **API Optimization**:
   - Use pagination for large datasets
   - Implement field filtering in API
   - Add response compression

## Migration Strategy

As the plugin evolves:

1. Store plugin version in options
2. Check for schema updates on activation
3. Add new meta fields with defaults
4. Migrate existing data if needed
5. Update cache version to force refresh
6. Provide rollback mechanisms

## React-Specific Considerations

### State Management

The database schema supports React's state management needs:

1. **Revision Tracking**: Every update gets a unique revision ID
2. **Timestamps**: ISO format for JavaScript compatibility
3. **Optimistic Updates**: Structure supports client-side predictions
4. **Partial Updates**: Individual field updates without full reloads

### API Response Format

Standardized JSON responses for React:

```json
{
    "id": 123,
    "type": "rpg_character",
    "attributes": {
        "name": "Character Name",
        "class": "Aeronaut",
        "attributes": {
            "fortitude": "3d7+1",
            "precision": "2d7",
            "intellect": "4d7",
            "charisma": "2d7+2"
        }
    },
    "meta": {
        "revision_id": "abc123",
        "last_modified": "2025-05-17T12:00:00Z",
        "cache_version": 2
    },
    "relationships": {
        "owner": {
            "id": 1,
            "type": "user"
        }
    }
}