# Plugin Lifecycle Management

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

This document outlines how RPG-Suite manages its lifecycle, including activation, deactivation, and uninstallation processes.

## Installation and Activation

### Minimum Requirements

RPG-Suite requires:
- WordPress 5.7 or higher
- PHP 7.2 or higher
- BuddyPress 8.0 or higher
- MySQL 5.6 or higher or MariaDB 10.1 or higher

### Activation Procedure

When the plugin is activated, it performs these essential tasks:

1. **Requirement Check**: Verify WordPress and PHP versions
2. **BuddyPress Check**: Verify BuddyPress is active
3. **Register Post Types**: Register the character post type
4. **Register Meta Fields**: Register character attribute meta fields
5. **Flush Rewrite Rules**: Update permalink structure

### Activation Hook

The plugin uses WordPress's standard activation hook:

```
register_activation_hook(__FILE__, array('RPG_Suite_Activator', 'activate'));
```

### Post Type Registration

During activation, the character post type is registered with standard post capabilities for simplicity and reliability:

```
register_post_type('rpg_character', [
    'labels' => [
        'name' => __('Characters', 'rpg-suite'),
        'singular_name' => __('Character', 'rpg-suite'),
        // Other labels...
    ],
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,  // Enable block editor support
    'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
    'has_archive' => false,
    'capability_type' => 'post',  // Use standard post capabilities
    'map_meta_cap' => true,
]);
```

### Dependency Handling

If required dependencies are missing:

1. Display an admin notice explaining what's missing
2. Auto-deactivate the plugin
3. Provide links to install required plugins

## Deactivation

When the plugin is deactivated, it:

1. **Preserves Data**: All character data remains in the database
2. **Flushes Rewrite Rules**: Cleans up permalink structure
3. **Clears Caches**: Removes any transients or cached data

### Deactivation Hook

```
register_deactivation_hook(__FILE__, array('RPG_Suite_Deactivator', 'deactivate'));
```

## Uninstallation

When the plugin is deleted, it can optionally remove all plugin data:

1. **Check Setting**: Only delete data if the user has enabled this option
2. **Remove Characters**: Delete all character posts
3. **Remove Options**: Delete all plugin options
4. **Remove User Meta**: Delete related user metadata

### Uninstall File

The plugin uses WordPress's standard uninstall.php file:

```
// In uninstall.php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if we should remove data
$remove_data = get_option('rpg_suite_remove_data_on_uninstall', false);
if (!$remove_data) {
    return;
}

// Delete character posts
$character_posts = get_posts([
    'post_type' => 'rpg_character',
    'numberposts' => -1,
    'fields' => 'ids',
]);

foreach ($character_posts as $post_id) {
    wp_delete_post($post_id, true);
}

// Delete plugin options
delete_option('rpg_suite_version');
delete_option('rpg_suite_remove_data_on_uninstall');
// Delete other options...

// Delete user meta
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_rpg_%'");
```

## Version Management

The plugin tracks its version to manage updates:

1. **Version Storage**: Store the plugin version in options table
2. **Version Check**: Compare stored version with current version on plugin load
3. **Update Process**: Run any necessary updates when the version changes

## Implementation Approach

Our implementation focuses on:

1. **Simplicity**: Keep activation and deactivation processes simple
2. **Standard WordPress Patterns**: Use WordPress's built-in hooks and functions
3. **Data Safety**: Preserve user data by default
4. **Graceful Failures**: Handle missing dependencies without breaking the site

## Error Handling

The plugin handles errors by:

1. **Informative Messages**: Display helpful admin notices
2. **Graceful Deactivation**: Auto-deactivate when requirements aren't met
3. **Error Logging**: Log errors for troubleshooting

## Testing Methodology

To ensure proper lifecycle management:

1. **Test in Browser**: All activation processes should be tested in a browser environment
2. **Test with Other Plugins**: Verify compatibility with common plugins
3. **Test Deactivation**: Ensure clean deactivation without errors
4. **Test Reactivation**: Verify the plugin can be reactivated without issues

## Multisite Support

Basic multisite support is included:

1. **Network Activation**: Support plugin activation across all sites
2. **Per-Site Configuration**: Allow configuration on a per-site basis