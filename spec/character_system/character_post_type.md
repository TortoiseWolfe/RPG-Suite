# Character Post Type Specification

## Purpose
This specification defines the custom post type for characters in the RPG-Suite plugin, including its registration, meta fields, and admin integration.

## Requirements
1. Register a custom post type for characters
2. Define and register custom meta fields for character data
3. Add meta boxes for character editing
4. Set up proper labels and capabilities
5. Configure archive and single views
6. Handle post type registration during plugin activation

## Class Definition

The Character Post Type class should:
1. Be named `RPG_Suite_Character_Post_Type`
2. Be defined in file `class-character-post-type.php`
3. Define a constant for the post type name: 'rpg_character'
4. Have a dependency on the Die Code Utility (RPG_Suite_Die_Code_Utility)
5. Initialize the post type with WordPress hooks:
   - Register post type on 'init'
   - Register meta fields on 'init'
   - Add meta boxes
   - Handle saving meta box data
   - Customize admin columns
   - Customize post update messages

The class should implement these methods:
- register_post_type(): Registers the custom post type
- register_meta(): Registers meta fields for character data
- add_meta_boxes(): Adds meta boxes for character editing
- save_meta_boxes(): Saves data from meta boxes
- add_custom_columns(): Adds custom columns to admin list
- custom_column_content(): Displays content in custom columns
- updated_messages(): Customizes post update messages

## Method Implementations

### Registering Post Type

The register_post_type() method should:

1. Define comprehensive labels for the character post type
2. Set up the post type with these key requirements:
   - Public visibility for frontend display
   - Enable REST API support for block editor
   - Custom rewrite rules for user-friendly URLs
   - **CRITICAL: Standard WordPress capabilities for proper permission handling**
   - **CRITICAL: Capability mapping for role-based access control**
   - **CRITICAL: Consistent capability approach throughout all plugin components**
   - Support for standard WordPress content features
   - Proper admin menu integration

Standard WordPress capabilities are essential to prevent editing permission errors. Custom capabilities should be avoided unless absolutely necessary and thoroughly tested.

### Registering Meta Fields

The register_meta() method should register several meta fields for characters:

1. _rpg_attributes (object): Character attributes for the d7 system
2. _rpg_skills (object): Character skills
3. _rpg_class (string): Character's class/profession
4. _rpg_active (boolean): Whether this is the user's active character
5. _rpg_invention_points (integer): Points for creating inventions
6. _rpg_fate_tokens (integer): Tokens for fate manipulation

**CRITICAL: All meta fields must use proper auth_callbacks that check for specific post type and capability**

**Authentication and Authorization Requirements:**

* Verify post type before applying capability checks
* Use standard WordPress capabilities for permission validation
* Maintain consistent capability approach across all plugin components
* Consider user roles and permissions in authorization logic
* Validate context appropriately for each meta field operation
* Avoid custom capability names in favor of standard WordPress capabilities

This correction prevents permission issues when editing character meta data.

## Integration with WordPress and BuddyPress

The character post type integrates with WordPress and BuddyPress through:

1. **REST API Support** - Enables modern block editor usage and API access
2. **Custom Capability Handling** - Uses WordPress permission system
3. **Meta Field Registration** - Makes character data available to REST API
4. **Custom Archive Templates** - Character listings in frontend
5. **BuddyPress Profile Integration** - Display character data in profiles

## Security Considerations

1. **Capability Checks** - All operations validate user permissions
2. **Data Sanitization** - All input/output properly sanitized
3. **Nonce Verification** - Form submissions verified with nonces
4. **Field Authorization** - Meta fields have auth_callback checks

## Performance Optimization

1. **Efficient Queries** - Custom post type uses proper indexing
2. **Minimal Admin Load** - Admin assets only loaded when needed
3. **Targeted Meta Registration** - Only necessary fields exposed to REST

## Implementation Notes

1. The post type registration happens during plugin initialization
2. The admin UI leverages WordPress core UI patterns
3. The d7 system is integrated into meta boxes for attribute editing
4. The post type is designed to work with the character manager
5. All class names follow the RPG_Suite_ prefix convention