# Character REST Controller Specification

## Purpose
The Character REST Controller provides RESTful API endpoints for the React frontend to interact with character data, supporting real-time updates, efficient caching, and proper authentication.

## Requirements
1. Implement standard REST endpoints for character CRUD operations
2. Support partial updates for real-time React updates
3. Provide efficient field filtering for optimized responses
4. Implement proper authentication and capability checks
5. Support batch operations for performance
6. Add caching headers for client-side optimization
7. Follow JSON:API specification where applicable
8. Handle errors gracefully with meaningful messages

## Class Definition

The Character REST Controller class should:
1. Be named `RPG_Suite_Character_REST_Controller`
2. Extend `WP_REST_Controller`
3. Be defined in file `class-character-rest-controller.php`
4. Have dependencies on:
   - RPG_Suite_Character_Manager
   - RPG_Suite_Cache_Manager
   - RPG_Suite_Die_Code_Utility

## REST Endpoints

### Get Character
```
GET /wp-json/rpg-suite/v1/characters/{id}
```
- Retrieve single character data
- Support field filtering via `fields` parameter
- Include revision ID for cache management
- Add cache headers for client optimization

### Update Character
```
PATCH /wp-json/rpg-suite/v1/characters/{id}
```
- Support partial updates for specific fields
- Validate die codes for attributes
- Update revision ID on changes
- Return updated character data
- Trigger cache invalidation

### Create Character
```
POST /wp-json/rpg-suite/v1/characters
```
- Create new character with validation
- Enforce character limits per user
- Set initial active status
- Return created character with ID

### Delete Character
```
DELETE /wp-json/rpg-suite/v1/characters/{id}
```
- Soft delete or permanent delete option
- Handle active character reassignment
- Clear all related caches
- Return confirmation

### List User Characters
```
GET /wp-json/rpg-suite/v1/users/{id}/characters
```
- Get all characters for a user
- Support pagination
- Include active status
- Sort by name or date

### Switch Active Character
```
POST /wp-json/rpg-suite/v1/characters/switch
```
- Change active character for user
- Validate ownership
- Handle concurrency
- Return new active character

### Batch Update
```
PATCH /wp-json/rpg-suite/v1/characters/batch
```
- Update multiple fields efficiently
- Support multiple characters
- Atomic operations
- Return all updated data

## Method Implementations

### Register Routes
```php
public function register_routes() {
    $namespace = 'rpg-suite/v1';
    
    // Single character endpoints
    register_rest_route($namespace, '/characters/(?P<id>\d+)', [
        [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_item'],
            'permission_callback' => [$this, 'get_item_permissions_check'],
            'args' => $this->get_item_schema(),
        ],
        [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_item'],
            'permission_callback' => [$this, 'update_item_permissions_check'],
            'args' => $this->get_update_schema(),
        ],
        [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'delete_item'],
            'permission_callback' => [$this, 'delete_item_permissions_check'],
        ],
    ]);
    
    // Collection endpoints
    register_rest_route($namespace, '/characters', [
        [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_items'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
            'args' => $this->get_collection_params(),
        ],
        [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_item'],
            'permission_callback' => [$this, 'create_item_permissions_check'],
            'args' => $this->get_create_schema(),
        ],
    ]);
    
    // Special endpoints
    register_rest_route($namespace, '/characters/switch', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => [$this, 'switch_character'],
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'character_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => [$this, 'validate_character_id'],
            ],
        ],
    ]);
    
    // User characters
    register_rest_route($namespace, '/users/(?P<id>\d+)/characters', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => [$this, 'get_user_characters'],
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'validate_callback' => [$this, 'validate_user_id'],
            ],
        ],
    ]);
}
```

### Get Character Implementation
```php
public function get_item($request) {
    $character_id = $request['id'];
    $fields = $request->get_param('fields');
    
    // Get from cache if available
    $cache_key = 'api_character_' . $character_id;
    if ($fields) {
        $cache_key .= '_' . md5(implode(',', $fields));
    }
    
    $cached = $this->cache_manager->get($cache_key, 'api');
    if ($cached !== false) {
        return rest_ensure_response($cached);
    }
    
    // Get character data
    $character = $this->character_manager->get_character($character_id);
    if (!$character) {
        return new WP_Error('not_found', 'Character not found', ['status' => 404]);
    }
    
    // Format response
    $data = $this->prepare_item_for_response($character, $request);
    
    // Add to cache
    $this->cache_manager->set($cache_key, $data, 'api', 5 * MINUTE_IN_SECONDS);
    
    // Add cache headers
    $response = rest_ensure_response($data);
    $this->add_cache_headers($response, $character_id);
    
    return $response;
}
```

### Update Character Implementation
```php
public function update_item($request) {
    $character_id = $request['id'];
    $updates = $request->get_json_params();
    
    // Validate ownership
    if (!$this->user_owns_character($character_id)) {
        return new WP_Error('forbidden', 'You cannot edit this character', ['status' => 403]);
    }
    
    // Validate updates
    $validation = $this->validate_updates($updates);
    if (is_wp_error($validation)) {
        return $validation;
    }
    
    // Apply updates
    $updated_character = $this->character_manager->update_character($character_id, $updates);
    if (is_wp_error($updated_character)) {
        return $updated_character;
    }
    
    // Clear caches
    $this->cache_manager->invalidate_character($character_id);
    
    // Format response
    $data = $this->prepare_item_for_response($updated_character, $request);
    
    // Add revision header
    $response = rest_ensure_response($data);
    $response->header('X-Revision-ID', get_post_meta($character_id, '_rpg_revision_id', true));
    
    return $response;
}
```

### Response Formatting
```php
public function prepare_item_for_response($character, $request) {
    $fields = $request->get_param('fields');
    
    $data = [
        'id' => $character->ID,
        'type' => 'character',
        'attributes' => [
            'name' => $character->post_title,
            'description' => $character->post_content,
            'class' => get_post_meta($character->ID, '_rpg_class', true),
            'attributes' => [
                'fortitude' => get_post_meta($character->ID, '_rpg_attribute_fortitude', true),
                'precision' => get_post_meta($character->ID, '_rpg_attribute_precision', true),
                'intellect' => get_post_meta($character->ID, '_rpg_attribute_intellect', true),
                'charisma' => get_post_meta($character->ID, '_rpg_attribute_charisma', true),
            ],
            'active' => (bool) get_post_meta($character->ID, '_rpg_active', true),
        ],
        'meta' => [
            'revision_id' => get_post_meta($character->ID, '_rpg_revision_id', true),
            'last_modified' => get_post_modified_time('c', false, $character->ID),
            'created' => get_post_time('c', false, $character->ID),
        ],
        'relationships' => [
            'owner' => [
                'data' => [
                    'type' => 'user',
                    'id' => $character->post_author,
                ],
            ],
        ],
    ];
    
    // Apply field filtering
    if ($fields) {
        $data = $this->filter_fields($data, $fields);
    }
    
    // Add HATEOAS links
    $data['links'] = [
        'self' => rest_url('rpg-suite/v1/characters/' . $character->ID),
        'owner' => rest_url('wp/v2/users/' . $character->post_author),
    ];
    
    return $data;
}
```

### Permission Checks
```php
public function update_item_permissions_check($request) {
    $character_id = $request['id'];
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return new WP_Error('unauthorized', 'You must be logged in', ['status' => 401]);
    }
    
    // Check character ownership or admin
    if (!current_user_can('edit_rpg_character', $character_id)) {
        return new WP_Error('forbidden', 'You cannot edit this character', ['status' => 403]);
    }
    
    return true;
}
```

### Caching Headers
```php
private function add_cache_headers($response, $character_id) {
    $revision = get_post_meta($character_id, '_rpg_revision_id', true);
    $last_modified = get_post_modified_time('U', false, $character_id);
    
    $response->header('Cache-Control', 'private, max-age=300');
    $response->header('ETag', '"' . $revision . '"');
    $response->header('Last-Modified', gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');
    $response->header('X-Revision-ID', $revision);
    
    return $response;
}
```

## Error Handling

All endpoints should return consistent error responses:

```json
{
    "code": "validation_error",
    "message": "Invalid attribute value",
    "data": {
        "status": 400,
        "params": {
            "attributes.fortitude": "Invalid die code format"
        }
    }
}
```

## Schema Definition

### Character Schema
```php
public function get_item_schema() {
    return [
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        'title' => 'character',
        'type' => 'object',
        'properties' => [
            'id' => [
                'type' => 'integer',
                'readonly' => true,
            ],
            'name' => [
                'type' => 'string',
                'required' => true,
            ],
            'class' => [
                'type' => 'string',
                'enum' => ['Aeronaut', 'Mechwright', 'Aethermancer', 'Diplomat'],
            ],
            'attributes' => [
                'type' => 'object',
                'properties' => [
                    'fortitude' => ['type' => 'string', 'pattern' => '^\d+d7(\+\d+)?$'],
                    'precision' => ['type' => 'string', 'pattern' => '^\d+d7(\+\d+)?$'],
                    'intellect' => ['type' => 'string', 'pattern' => '^\d+d7(\+\d+)?$'],
                    'charisma' => ['type' => 'string', 'pattern' => '^\d+d7(\+\d+)?$'],
                ],
            ],
        ],
    ];
}
```

## Performance Optimizations

1. **Field Filtering**: Only return requested fields
2. **Batch Operations**: Support multiple updates in one request
3. **Conditional Requests**: Support If-None-Match headers
4. **Pagination**: Limit collection responses
5. **Compression**: Enable gzip for responses

## Security Measures

1. **Authentication**: Verify WordPress nonce
2. **Authorization**: Check user capabilities
3. **Validation**: Sanitize all inputs
4. **Rate Limiting**: Implement per-user limits
5. **CORS**: Configure allowed origins

## Testing Requirements

1. Unit tests for all endpoints
2. Integration tests with database
3. Authentication/authorization tests
4. Performance benchmarks
5. Error handling tests

## Implementation Notes

1. Follow WordPress REST API best practices
2. Use proper HTTP status codes
3. Implement consistent response format
4. Add comprehensive documentation
5. Support API versioning
6. Log important operations
7. Monitor API usage
8. Provide detailed error messages
9. Support debugging mode
10. Cache responses appropriately