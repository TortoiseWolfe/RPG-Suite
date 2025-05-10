# Activator Class Specification

## Purpose
The Activator class handles plugin activation tasks, including database table creation, capability setup, and initialization of default options.

## Requirements
1. Register custom post types and taxonomies
2. Create required database tables
3. Set up roles and capabilities
4. Initialize default plugin options
5. Flush rewrite rules for custom post types
6. Handle multi-site activation if needed

## Class Requirements

The Activator class should:

1. Be named `RPG_Suite_Activator`
2. Be defined in file `class-activator.php`
3. Include a main activation method that handles single site or multi-site
4. Support silent activation for migrations during updates
5. Register custom roles and capabilities
6. Create default plugin settings

## Method Descriptions

### Main Activation Method

The activate() method should:
- Accept optional boolean for silent activation
- Check if activation is network-wide
- For single site: call the single site activation method
- For multi-site: iterate through sites and call single site activation for each
- Trigger activation actions

### Single Site Activation

The activate_single_site() method should:
- Register character and invention post types
- Register taxonomies
- Create database tables if needed
- Initialize default roles and capabilities
- Create default plugin options
- Flush rewrite rules when not silent
- Log activation with timestamp and version

### Capability Registration

The register_capabilities() method should:
- Add post type specific capabilities to roles
- Map capabilities to appropriate WordPress roles
- Set up character editing permissions
- Set up invention editing permissions
- Add admin-only capabilities
- Update role capabilities

### Database Setup

If needed, the create_tables() method should:
- Get wpdb global instance
- Get table names with proper prefixes
- Set up character metadata table if not using post meta
- Set up optional tables for performance
- Use dbDelta for proper table creation
- Set charset and collation
- Handle errors during creation

### Default Options

The create_default_options() method should:
- Set default character limit per user
- Set default dice animations option
- Set default style options
- Set default plugin behavior options
- Only set options if they don't already exist

## Integration with Plugin Main Class

The Activator should be:
- Instantiated during plugin activation
- Called from a static activate hook callback in the main plugin file
- Registered with register_activation_hook()

## Multisite Support

For multisite support, the activator should:
- Check if is_multisite() and if activation is network wide
- Use the wpmu_new_blog hook for new site activations
- Support per-site activation settings

## Implementation Notes

1. **Capability Mapping**: Use map_meta_cap for custom post types
2. **Error Handling**: Log all activation errors
3. **Multisite Support**: Handle network wide activation 
4. **Idempotency**: Ensure multiple activations don't cause issues
5. **Default Data**: Consider creating sample content on activation
6. **Backward Compatibility**: Check previous version during reactivation
7. **Performance**: Minimize impact on large sites
8. **Class Naming**: Follows the RPG_Suite_ prefix convention for consistency
9. **WordPress Specific**: Use WordPress functions and hooks properly