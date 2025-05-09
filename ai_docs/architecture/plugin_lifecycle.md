# Plugin Lifecycle Management

This document details how RPG-Suite manages its lifecycle from installation through uninstallation, including activation, deactivation, and upgrade procedures.

## Installation and Activation

### Minimum Requirements

RPG-Suite requires:
- WordPress 5.7 or higher
- PHP 7.2 or higher
- BuddyPress 8.0 or higher
- MySQL 5.6 or higher or MariaDB 10.1 or higher

### Activation Procedure

When the plugin is activated, the following occurs:

```php
register_activation_hook(__FILE__, array('RPG_Suite_Activator', 'activate'));
```

The activation class performs these tasks:

1. **Version Check**: Verifies WordPress and PHP versions
2. **BuddyPress Check**: Verifies BuddyPress is active
3. **Database Setup**:
   - Registers custom post types
   - Creates any custom database tables
   - Sets default options
4. **Flush Rewrite Rules**: Updates permalink structure for custom post types
5. **Create Default Data**: Sets up default character classes, templates, etc.
6. **Version Storage**: Stores current plugin version for future updates

### Dependency Handling

If dependencies are missing:

1. Display admin notice explaining missing requirements
2. Auto-deactivate the plugin
3. Provide helpful links to install required plugins

```php
public static function check_dependencies() {
    $requirements_met = true;
    
    // WordPress version check
    if (version_compare(get_bloginfo('version'), '5.7', '<')) {
        add_action('admin_notices', array('RPG_Suite_Activator', 'wordpress_version_notice'));
        $requirements_met = false;
    }
    
    // PHP version check
    if (version_compare(PHP_VERSION, '7.2', '<')) {
        add_action('admin_notices', array('RPG_Suite_Activator', 'php_version_notice'));
        $requirements_met = false;
    }
    
    // BuddyPress dependency check
    if (!class_exists('BuddyPress')) {
        add_action('admin_notices', array('RPG_Suite_Activator', 'buddypress_missing_notice'));
        $requirements_met = false;
    }
    
    return $requirements_met;
}
```

## Deactivation

When the plugin is deactivated:

```php
register_deactivation_hook(__FILE__, array('RPG_Suite_Deactivator', 'deactivate'));
```

The deactivation class performs these tasks:

1. **Flush Rewrite Rules**: Clean up permalink structure
2. **Clear Caches**: Remove any transients or cached data
3. **Cleanup Temporary Data**: Remove any temporary files or data
4. **Preserve User Data**: Character data remains in the database
5. **Remove Scheduled Events**: Clear any scheduled WP Cron events

```php
public static function deactivate() {
    // Clear any scheduled cron jobs
    wp_clear_scheduled_hook('rpg_suite_daily_maintenance');
    
    // Clear transients
    delete_transient('rpg_suite_character_cache');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
```

## Uninstallation

When the plugin is deleted, uninstall.php is executed:

```php
// In uninstall.php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load the uninstaller class
require_once plugin_dir_path(__FILE__) . 'includes/class-rpg-suite-uninstaller.php';

// Run uninstallation
RPG_Suite_Uninstaller::uninstall();
```

The uninstaller performs:

1. **Check Uninstall Option**: Only delete data if 'remove_data_on_uninstall' option is enabled
2. **Remove Post Types**: Delete all 'rpg_character' and 'rpg_invention' post types
3. **Remove Options**: Delete all options with 'rpg_suite_' prefix
4. **Remove User Meta**: Delete user meta with 'rpg_' prefix
5. **Remove Capabilities**: Remove any custom capabilities added by the plugin
6. **Remove Custom Database Tables**: If any were created

```php
public static function uninstall() {
    // Check if we should remove data
    $remove_data = get_option('rpg_suite_remove_data_on_uninstall', false);
    if (!$remove_data) {
        return;
    }
    
    // Delete all character posts
    $character_posts = get_posts(array(
        'post_type' => 'rpg_character',
        'numberposts' => -1,
        'fields' => 'ids',
    ));
    
    foreach ($character_posts as $post_id) {
        wp_delete_post($post_id, true);
    }
    
    // Delete all invention posts
    $invention_posts = get_posts(array(
        'post_type' => 'rpg_invention',
        'numberposts' => -1,
        'fields' => 'ids',
    ));
    
    foreach ($invention_posts as $post_id) {
        wp_delete_post($post_id, true);
    }
    
    // Delete options
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rpg_suite_%'");
    
    // Delete user meta
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_rpg_%'");
}
```

## Version Upgrades

RPG-Suite includes a version-based upgrade system:

1. **Version Tracking**: Store the current plugin version in the database
2. **Upgrade Detection**: Compare stored version with current version on plugin load
3. **Upgrade Routines**: Run appropriate upgrade routines for each version increment
4. **Database Schema Updates**: Handle database changes gracefully

```php
public function check_version() {
    $installed_version = get_option('rpg_suite_version', '0.0.0');
    $current_version = RPG_SUITE_VERSION;
    
    if (version_compare($installed_version, $current_version, '<')) {
        $upgrader = new RPG_Suite_Upgrader();
        $upgrader->upgrade($installed_version, $current_version);
        
        update_option('rpg_suite_version', $current_version);
    }
}
```

### Upgrade Example

```php
public function upgrade($from_version, $to_version) {
    // Run all applicable upgrade routines
    if (version_compare($from_version, '1.1.0', '<')) {
        $this->upgrade_to_1_1_0();
    }
    
    if (version_compare($from_version, '1.2.0', '<')) {
        $this->upgrade_to_1_2_0();
    }
    
    // Always run database upgrade
    $this->upgrade_database();
}

private function upgrade_to_1_1_0() {
    // Add new meta fields to existing characters
    $characters = get_posts(array(
        'post_type' => 'rpg_character',
        'numberposts' => -1,
    ));
    
    foreach ($characters as $character) {
        $attributes = get_post_meta($character->ID, '_rpg_attributes', true);
        if (!isset($attributes['new_attribute'])) {
            $attributes['new_attribute'] = '2d7';
            update_post_meta($character->ID, '_rpg_attributes', $attributes);
        }
    }
}
```

## Error Handling and Recovery

### Graceful Error Handling

RPG-Suite includes error handling for common issues:

1. **Database Errors**: Catch and log database errors
2. **Missing Dependencies**: Detect and notify about missing plugins
3. **Version Conflicts**: Handle WordPress version incompatibilities

### Rollback Mechanism

For critical upgrades, a rollback system is implemented:

1. **Backup Data**: Before upgrade, backup critical data
2. **Transaction-like Processing**: Perform upgrades in stages
3. **Validation**: Verify successful upgrade before committing
4. **Rollback**: If upgrade fails, restore from backup

```php
private function safe_upgrade($callback) {
    // Backup critical data
    $backup = $this->backup_critical_data();
    
    try {
        // Perform upgrade steps
        $result = call_user_func($callback);
        
        // Validate the upgrade
        if (!$this->validate_upgrade()) {
            throw new Exception('Upgrade validation failed');
        }
        
        return true;
    } catch (Exception $e) {
        // Log the error
        error_log('RPG-Suite upgrade failed: ' . $e->getMessage());
        
        // Rollback
        $this->restore_from_backup($backup);
        
        return false;
    }
}
```

## Multisite Compatibility

RPG-Suite supports WordPress multisite installations:

1. **Network Activation**: Handle network-wide activation properly
2. **Per-Site Installation**: Create database tables for each site
3. **Network Settings**: Provide network admin settings when appropriate

```php
public static function activate_multisite($network_wide) {
    if ($network_wide) {
        // Get all blog ids
        global $wpdb;
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            self::single_activate();
            restore_current_blog();
        }
    } else {
        self::single_activate();
    }
}
```