# Character Manager Specification

## Purpose
The Character Manager provides a centralized API for all operations related to character creation, retrieval, updating, and deletion in our steampunk d7 system.

## Requirements
1. Support creating, retrieving, updating, and deleting characters
2. Enforce character ownership and limits per user
3. Manage active character status with proper concurrency control
4. Handle character attributes and skills using d7 system
5. Support invention and gadget creation mechanics
6. Trigger appropriate events for character state changes
7. Provide clear error handling and validation
8. Implement caching for performance optimization

## Class Definition

The Character Manager class should:
1. Be named `RPG_Suite_Character_Manager`
2. Be defined in file `class-character-manager.php`
3. Have the following properties:
   - A private property for the event dispatcher instance (RPG_Suite_Event_Dispatcher)
   - A private property for the die code utility instance (RPG_Suite_Die_Code_Utility)
4. Have a constructor that accepts and stores these dependencies

## Core Methods

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
- Dispatch a 'character_created' event with relevant data
- Return the character ID on success or WP_Error on failure

### Retrieving a Character
This method (`get_character`) should:
- Accept a character ID parameter
- Return the character post object if it exists and is of the correct type
- Return null if the character doesn't exist or is of the wrong type

### Getting User Characters
This method (`get_user_characters`) should:
- Implement caching for performance using 'rpg_user_characters_[user_id]' cache key
- Check cache first and return cached results if available
- Query all characters belonging to the specified user if not cached
- Order results by title ascending
- Cache the character IDs with a 12-hour expiration
- Return an array of character post objects

### Getting Active Character
This method (`get_active_character`) should:
- Implement transient caching using 'rpg_active_character_[user_id]' key
- Check cache first and validate the cached character still exists
- Query for the user's character with the '_rpg_active' meta set to true
- Cache the result with a 12-hour expiration
- Return the active character post or null if none found

### Setting Active Character
This method (`set_active_character`) should:
- Use a transient-based mutex for concurrency control
- Return an error if another activation is in progress
- Verify the character belongs to the specified user
- Get the previous active character for event data
- Deactivate all of the user's characters
- Activate only the specified character
- Clear relevant caches
- Dispatch a 'character_activated' event with relevant data
- Release the mutex and handle any exceptions
- Return true on success or WP_Error on failure

### Validating Attributes
This method (`validate_attributes`) should:
- Check that attribute names are valid (fortitude, precision, intellect, charisma)
- Validate die code format using the die code utility
- Enforce minimum of 1 die and maximum of 10 dice per attribute
- Return true on success or WP_Error with detailed message on failure

### Validating Skills
This method (`validate_skills`) should:
- Verify each skill has required 'attribute' and 'value' properties
- Check that referenced attributes exist
- Validate die code format for skill values
- Ensure skills don't exceed their base attribute by more than 2 dice
- Return true on success or WP_Error with detailed message on failure

### Cache Management
This method (`clear_character_cache`) should:
- Accept a character ID and optional user ID
- Determine the user ID from the character if not provided
- Clear active character cache using transient deletion
- Clear user characters cache

## Usage Context

The Character Manager should be accessible through the global plugin instance:
- Global access via $rpg_suite->get_character_manager()
- Used for all character-related operations throughout the plugin
- Event dispatching allows other components to hook into character actions

## Implementation Notes

1. All methods must include proper error handling with descriptive error messages
2. Character validation ensures data integrity for the d7 system
3. Caching is implemented for frequently accessed data
4. Concurrency control prevents race conditions during character activation
5. Events are dispatched for major actions to allow integration with other components
6. All user inputs must be properly sanitized before storage
7. Die code formatting and validation is delegated to the Die Code Utility
8. Character limit enforcement ensures users stay within their allowed limits
9. Proper security checks must be implemented for all operations
10. Meta data keys should use consistent naming conventions (_rpg_attribute, _rpg_skills, etc.)
11. The maximum number of characters should be filterable but default to 2