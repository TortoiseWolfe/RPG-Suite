# Cache Manager Specification

## Purpose
The Cache Manager provides a centralized caching solution for RPG-Suite, implementing a multi-layer caching strategy to optimize performance for both PHP backend and React frontend operations.

## Requirements
1. Implement multi-layer caching (object cache → transient → database)
2. Support cache invalidation across all layers
3. Provide cache warming capabilities
4. Track cache performance metrics
5. Support revision-based cache busting for React
6. Handle concurrent access safely
7. Provide granular cache control
8. Support cache tagging for group invalidation

## Class Definition

The Cache Manager class should:
1. Be named `RPG_Suite_Cache_Manager`
2. Be defined in file `class-cache-manager.php`
3. Have the following properties:
   - A private property for cache groups
   - A private property for cache metrics
   - A private property for default TTL values
4. Implement singleton pattern for consistent cache state

## Core Methods

### Get from Cache
This method (`get`) should:
- Accept cache key and group parameters
- Check object cache first (Redis if available)
- Fall back to WordPress transients
- Track cache hit/miss metrics
- Handle cache stampede protection
- Return cached value or false

### Set Cache Value
This method (`set`) should:
- Accept key, value, group, and TTL parameters
- Store in all cache layers
- Add revision metadata
- Update cache metrics
- Handle large value chunking
- Return success boolean

### Delete from Cache
This method (`delete`) should:
- Accept key and group parameters
- Remove from all cache layers
- Update related cache entries
- Track deletion metrics
- Return success boolean

### Invalidate Cache Group
This method (`invalidate_group`) should:
- Accept group name parameter
- Clear all entries in the group
- Update revision counters
- Notify React of invalidation
- Log invalidation event

### Cache Warming
This method (`warm_cache`) should:
- Accept entity type and ID
- Pre-load frequently accessed data
- Update cache metrics
- Run asynchronously if possible
- Return warming status

## Character-Specific Methods

### Get Character Cache
This method (`get_character`) should:
- Accept character ID
- Check multiple cache keys
- Include revision validation
- Handle partial cache hits
- Return character data or false

### Set Character Cache
This method (`set_character`) should:
- Accept character ID and data
- Generate revision ID
- Update multiple cache keys
- Set appropriate TTL
- Trigger cache events

### Invalidate Character Cache
This method (`invalidate_character`) should:
- Accept character ID
- Clear all related caches
- Update user character list cache
- Notify React frontend
- Log invalidation reason

## REST API Support

### Cache Headers
This method (`add_cache_headers`) should:
- Add appropriate HTTP cache headers
- Include ETag support
- Set cache control directives
- Add revision headers
- Support conditional requests

### Response Caching
This method (`cache_api_response`) should:
- Accept endpoint and response data
- Generate cache key from request
- Store with appropriate TTL
- Include request context
- Return cache success

## Performance Monitoring

### Track Metrics
This method (`track_metric`) should:
- Accept metric type and value
- Update in-memory counters
- Periodically persist metrics
- Support custom dimensions
- Return metric ID

### Get Cache Stats
This method (`get_stats`) should:
- Calculate hit/miss ratios
- Show cache sizes
- Display performance metrics
- Group by cache type
- Return formatted report

## React Integration

### Invalidation Events
This method (`notify_frontend`) should:
- Accept entity type and ID
- Generate invalidation event
- Dispatch via JavaScript
- Include revision data
- Support batch notifications

### Revision Management
This method (`get_revision`) should:
- Accept entity type and ID
- Generate unique revision ID
- Store revision mapping
- Handle revision conflicts
- Return revision string

## Implementation Strategies

### Object Cache Backend
```php
private function get_from_object_cache($key, $group) {
    if (!$this->is_object_cache_available()) {
        return false;
    }
    
    $value = wp_cache_get($key, $this->get_cache_group($group));
    
    if ($value !== false) {
        $this->track_metric('object_cache_hit', 1);
    } else {
        $this->track_metric('object_cache_miss', 1);
    }
    
    return $value;
}
```

### Transient Cache Backend
```php
private function get_from_transient($key, $group) {
    $transient_key = $this->build_transient_key($key, $group);
    $value = get_transient($transient_key);
    
    if ($value !== false) {
        $this->track_metric('transient_hit', 1);
    } else {
        $this->track_metric('transient_miss', 1);
    }
    
    return $value;
}
```

### Cache Stampede Protection
```php
private function prevent_stampede($key, $callback) {
    $lock_key = $key . '_lock';
    $lock_acquired = $this->acquire_lock($lock_key);
    
    if (!$lock_acquired) {
        // Wait for other process
        return $this->wait_for_value($key);
    }
    
    try {
        $value = call_user_func($callback);
        $this->set($key, $value);
        return $value;
    } finally {
        $this->release_lock($lock_key);
    }
}
```

## Cache Configuration

### TTL Values
```php
private $ttl_config = [
    'character' => HOUR_IN_SECONDS,
    'user_characters' => HOUR_IN_SECONDS,
    'active_character' => HOUR_IN_SECONDS,
    'api_response' => 5 * MINUTE_IN_SECONDS,
    'dice_roll' => 0, // Don't cache
];
```

### Cache Groups
```php
private $cache_groups = [
    'characters' => 'rpg_suite_characters',
    'users' => 'rpg_suite_users',
    'api' => 'rpg_suite_api',
    'system' => 'rpg_suite_system',
];
```

## Security Considerations

1. **Input Validation**: Sanitize all cache keys
2. **Access Control**: Verify permissions before caching
3. **Data Privacy**: Don't cache sensitive data
4. **Key Generation**: Use secure key generation
5. **TTL Limits**: Enforce maximum TTL values

## Performance Guidelines

1. **Layer Priority**: Check fastest cache first
2. **Batch Operations**: Support bulk get/set
3. **Async Warming**: Use background jobs
4. **Selective Invalidation**: Only clear affected entries
5. **Compression**: Compress large values

## Usage Examples

### Basic Usage
```php
$cache_manager = $rpg_suite->get_cache_manager();

// Get from cache
$character = $cache_manager->get('character_123', 'characters');

// Set in cache
$cache_manager->set('character_123', $character_data, 'characters');

// Invalidate
$cache_manager->invalidate_character(123);
```

### React Integration
```php
// Notify React of cache invalidation
$cache_manager->notify_frontend('character', 123, [
    'revision' => $new_revision,
    'timestamp' => time(),
]);
```

## Testing Requirements

1. Unit tests for all cache operations
2. Integration tests with Redis
3. Performance benchmarks
4. Concurrency tests
5. Cache invalidation tests

## Implementation Notes

1. Use dependency injection for cache backends
2. Implement interface for swappable backends
3. Support WordPress coding standards
4. Add filters for extensibility
5. Document cache key formats
6. Handle edge cases gracefully
7. Log important cache events
8. Monitor cache health
9. Provide admin UI for cache management
10. Support WP-CLI commands