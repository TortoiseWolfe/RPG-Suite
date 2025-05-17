# Character Manager Specification - React Revision

## Purpose
The Character Manager provides a centralized API for all character operations in our steampunk d7 system, with enhanced support for React frontend and REST API endpoints.

## Requirements
1. Support creating, retrieving, updating, and deleting characters
2. Enforce character ownership and limits per user
3. Manage active character status with proper concurrency control
4. Handle character attributes and skills using d7 system
5. Support invention and gadget creation mechanics
6. Trigger appropriate events for character state changes
7. Provide clear error handling and validation
8. Implement multi-layer caching for performance optimization
9. Support REST API operations for React frontend
10. Handle real-time updates and cache invalidation

## Class Definition

The Character Manager class should:
1. Be named `RPG_Suite_Character_Manager`
2. Be defined in file `class-character-manager.php`
3. Have the following properties:
   - A private property for the event dispatcher instance (RPG_Suite_Event_Dispatcher)
   - A private property for the die code utility instance (RPG_Suite_Die_Code_Utility)
   - A private property for the cache manager instance (RPG_Suite_Cache_Manager)
4. Have a constructor that accepts and stores these dependencies

## Core Methods (Enhanced for React)

### Creating a Character
This method (`create_character`) should:
- Check if the user has reached their character limit
- Validate required fields including character name
- Validate character attributes according to d7 system rules
- Apply default attributes if none provided (2d7 for each attribute)
- Validate skills if provided, ensuring they relate to valid attributes
- Create a character post with proper sanitization of all inputs
- Store character metadata including attributes, skills, class, etc.
- Initialize invention points and fate tokens with default values
- Make the character active if it's the user's first character
- Generate revision ID for React cache management
- Dispatch a 'character_created' event with relevant data
- Clear relevant caches
- Return the character ID on success or WP_Error on failure

### Retrieving a Character (With Caching)
This method (`get_character`) should:
- Accept a character ID parameter and optional format parameter
- Check multi-layer cache (object cache → transient → database)
- Return the character post object if it exists and is of the correct type
- Format data for REST API response if requested
- Include revision ID and cache metadata
- Return null if the character doesn't exist or is of the wrong type

### Getting User Characters (Optimized)
This method (`get_user_characters`) should:
- Implement multi-layer caching for performance
- Check cache first and return cached results if available
- Query all characters belonging to the specified user if not cached
- Order results by title ascending
- Cache the results with appropriate expiration
- Return an array of character post objects or formatted data
- Support pagination for large character lists

### Getting Active Character (Enhanced)
This method (`get_active_character`) should:
- Implement transient and object caching
- Check cache first and validate the cached character still exists
- Query for the user's character with the '_rpg_active' meta set to true
- Cache the result with appropriate expiration
- Include revision tracking for React
- Return the active character post or null if none found

### Setting Active Character (With Locking)
This method (`set_active_character`) should:
- Use a transient-based mutex for concurrency control
- Return an error if another activation is in progress
- Verify the character belongs to the specified user
- Get the previous active character for event data
- Deactivate all of the user's characters
- Activate only the specified character
- Clear all relevant caches across layers
- Update revision IDs
- Dispatch a 'character_activated' event with relevant data
- Send cache invalidation notifications to React
- Release the mutex and handle any exceptions
- Return true on success or WP_Error on failure

### Batch Character Updates (New)
This method (`batch_update_character`) should:
- Accept character ID and array of updates
- Validate each update field
- Apply updates atomically
- Update revision ID
- Clear caches selectively
- Dispatch appropriate events
- Return updated character data

### Real-time Update Notification (New)
This method (`notify_character_update`) should:
- Accept character ID and update type
- Generate update event data
- Dispatch to event system
- Queue for WebSocket notification (future)
- Update cache metadata

## REST API Support Methods

### Format for API Response
This method (`format_for_api`) should:
- Accept character post object
- Transform to standardized JSON structure
- Include calculated fields
- Add HATEOAS links
- Return formatted array

### Validate API Request
This method (`validate_api_request`) should:
- Check request method
- Validate nonce
- Verify user capabilities
- Sanitize input data
- Return validation result

## Caching Strategy

### Multi-Layer Cache Implementation
This method (`get_from_cache`) should:
- Check object cache first (fastest)
- Fall back to transient cache
- Query database as last resort
- Update higher cache layers on miss
- Track cache performance metrics

### Cache Invalidation
This method (`invalidate_character_cache`) should:
- Accept character ID and invalidation scope
- Clear object cache entries
- Delete transient entries
- Update revision IDs
- Notify React of cache invalidation
- Clear related caches (user characters, etc.)

## Event System Integration

### Dispatch Character Events
Events should include:
- character_created
- character_updated
- character_deleted
- character_activated
- character_deactivated
- character_cache_invalidated

Each event should include:
- Character ID
- User ID
- Timestamp
- Revision ID
- Update type
- Previous/new values

## Performance Optimizations

1. **Query Optimization**:
   - Use selective field queries
   - Implement query result caching
   - Batch related queries

2. **Cache Warming**:
   - Pre-cache frequently accessed data
   - Background cache refresh
   - Predictive caching

3. **API Response Optimization**:
   - Field filtering support
   - Response compression
   - ETag support

## Security Considerations

1. **Capability Checks**:
   - Verify user can edit character
   - Check character ownership
   - Validate admin operations

2. **Input Validation**:
   - Sanitize all inputs
   - Validate data types
   - Check value ranges

3. **Rate Limiting**:
   - Implement per-user limits
   - Track API usage
   - Prevent abuse

## Usage Context

The Character Manager should be accessible through:
- Global plugin instance: $rpg_suite->get_character_manager()
- REST API endpoints for React frontend
- WordPress admin interface
- BuddyPress integration hooks

## Implementation Notes

1. All methods must include proper error handling with descriptive error messages
2. Character validation ensures data integrity for the d7 system
3. Multi-layer caching provides optimal performance
4. Concurrency control prevents race conditions during character activation
5. Events are dispatched for major actions to allow integration
6. All user inputs must be properly sanitized before storage
7. REST API responses follow JSON:API specification
8. Revision tracking enables optimistic updates in React
9. Cache invalidation is granular and efficient
10. Performance metrics are tracked for optimization
11. Security checks are performed at every layer
12. The maximum number of characters should be filterable but default to 2