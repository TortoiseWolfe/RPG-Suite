# Character Meta Handler Specification

## Purpose
The Character Meta Handler is responsible for managing character metadata, including saving, retrieving, and validating character attributes, skills, and other properties specific to the d7 system.

## Requirements
1. Save character metadata in a consistent format
2. Retrieve character metadata with proper defaults
3. Validate character data according to d7 system rules
4. Handle format conversion between database and application formats
5. Provide sanitization for all character data
6. Support caching for efficient data access

## Class Definition

The Character Meta Handler class should:
1. Be named `RPG_Suite_Character_Meta_Handler`
2. Be defined in file `class-character-meta-handler.php`
3. Have a private property for the die code utility instance (RPG_Suite_Die_Code_Utility)
4. Have a constructor that accepts and stores this dependency

## Core Methods

### Saving Character Meta
This method (`save_character_meta`) should:
- Verify the post ID is a valid rpg_character post type
- Validate the provided data using the validate_character_data method
- Sanitize and save attributes if provided
- Sanitize and save skills if provided
- Save class information if provided
- Save active status if provided
- Save invention points if provided (using absint for sanitization)
- Save fate tokens if provided (using absint for sanitization)
- Save derived stats if provided
- Clear the character's cache after saving
- Return true on success or WP_Error on failure

### Getting Character Meta
This method (`get_character_meta`) should:
- Check the cache first if $use_cache is true
- Use 'rpg_character_meta_[post_id]' as the cache key
- Verify the post ID is a valid rpg_character post type
- Retrieve all meta fields (attributes, skills, class, active status, etc.)
- Apply default values for any missing data
- Calculate derived stats if not already stored
- Compile the data into a structured array including:
  - Basic character info (ID, name, description, owner)
  - Attributes with proper formatting
  - Skills with proper structure
  - Class designation
  - Active status as boolean
  - Invention points and fate tokens as integers
  - Derived stats
- Cache the result with a 12-hour expiration if caching is enabled
- Return the complete character data array

### Getting Default Attributes
This method (`get_default_attributes`) should:
- Provide base attributes for all classes (fortitude, precision, intellect, charisma = 2d7)
- Adjust attributes based on the provided character class:
  - Aeronaut: Higher precision (3d7) and fortitude (2d7+1)
  - Mechwright: Higher intellect (3d7) and precision (2d7+1)
  - Aethermancer: Higher intellect (3d7+1)
  - Diplomat: Higher charisma (3d7) and intellect (2d7+1)
- Apply the 'rpg_suite_default_attributes' filter to allow customization
- Return the attributes array

### Getting Default Skills
This method (`get_default_skills`) should:
- Provide class-specific default skills when applicable
- Return an empty array or minimal defaults if no class is specified
- Apply appropriate filters for customization

### Calculating Derived Stats
This method (`calculate_derived_stats`) should:
- Parse attribute die codes using the Die Code Utility
- Calculate Vitality based on Fortitude (dice × 5 + modifier)
- Calculate Movement based on Precision (5 + dice)
- Calculate Initiative based on Intellect and Precision
- Calculate Will based on Intellect and Charisma
- Apply the 'rpg_suite_derived_stats' filter
- Return the calculated stats array

### Validating Character Data
This method (`validate_character_data`) should:
- Validate attributes if provided:
  - Check that attribute names are valid (fortitude, precision, intellect, charisma)
  - Verify die code format using the die code utility
- Validate skills if provided:
  - Check each skill has required 'attribute' and 'value' properties
  - Verify the referenced attribute exists
  - Validate the die code format for skill values
- Validate class if provided:
  - Ensure the class is one of the valid options (aeronaut, mechwright, aethermancer, diplomat)
- Return true on success or WP_Error with detailed message on failure

### Data Sanitization
The class should include the following private sanitization methods:
- sanitize_attributes: Ensures attributes have valid die codes
- sanitize_skills: Sanitizes skill names and validates their structure and values
- sanitize_derived_stats: Validates and sanitizes derived stat values

### Cache Management
This method (`clear_cache`) should:
- Accept a character post ID parameter
- Delete the character's meta data from the WordPress cache
- Use the standard cache key format 'rpg_character_meta_[post_id]'

## Usage Context

The Character Meta Handler should be:
- Accessible through the global plugin instance via $rpg_suite->get_character_meta_handler()
- Used primarily by the Character Manager for data persistence
- Used by display components to retrieve formatted character data
- Configured with appropriate capabilities to ensure secure data access

## Integration with Other Components

The Character Meta Handler integrates with:
1. **Character Manager** - Provides data layer for manager operations
2. **Die Code Utility** - Validates and manipulates d7 die codes
3. **WordPress Cache API** - Caches character data for performance
4. **WordPress Meta API** - Stores and retrieves post meta data

## Implementation Notes

1. All character data must be properly validated before saving
2. Default values should be provided for required fields
3. Caching improves performance for frequently accessed characters
4. Derived stats should be calculated from attributes automatically
5. The class should include hook points for customization and extension
6. Strict sanitization ensures data integrity
7. Error handling should follow WordPress patterns
8. Meta keys should use consistent naming convention (_rpg_*)
9. Meta values should be stored in a format that's easy to query
10. All class names follow the RPG_Suite_ prefix convention