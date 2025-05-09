# Plugin Deactivator Specification

## Purpose
This specification defines the plugin deactivator class that handles cleanup tasks when the RPG-Suite plugin is deactivated, ensuring proper cleanup while preserving user data.

## Requirements
1. Flush rewrite rules
2. Clear any scheduled events
3. Clean up transients and cache data
4. Preserve all user data and settings
5. Handle multisite deactivations properly
6. Provide hooks for extension deactivation

## Class Definition

```php
/**
 * Plugin deactivation handler
 *
 * @since 1.0.0
 */
class RPG_Suite_Deactivator {
    /**
     * Deactivate the plugin
     *
     * @since 1.0.0
     * @param bool $network_wide Whether the plugin is being deactivated network-wide.
     * @return void
     */
    public static function deactivate($network_wide) {
        if ($network_wide && is_multisite()) {
            self::deactivate_multisite();
        } else {
            self::deactivate_single_site();
        }
    }
    
    /**
     * Deactivate the plugin on a single site
     *
     * @since 1.0.0
     * @return void
     */
    private static function deactivate_single_site() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Clear caches and transients
        self::clear_caches();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        /**
         * Action fired when plugin is deactivated on a single site
         *
         * @since 1.0.0
         */
        do_action('rpg_suite_deactivated');
    }
    
    /**
     * Deactivate the plugin network-wide
     *
     * @since 1.0.0
     * @return void
     */
    private static function deactivate_multisite() {
        global $wpdb;
        
        // Get all sites
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            
            // Clear scheduled events for this site
            self::clear_scheduled_events();
            
            // Clear caches and transients for this site
            self::clear_caches();
            
            /**
             * Action fired when plugin is deactivated on a site in multisite
             *
             * @since 1.0.0
             * @param int $blog_id The ID of the blog being deactivated on.
             */
            do_action('rpg_suite_deactivated_ms', $blog_id);
            
            restore_current_blog();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        /**
         * Action fired when plugin is deactivated network-wide
         *
         * @since 1.0.0
         */
        do_action('rpg_suite_deactivated_network');
    }
    
    /**
     * Clear scheduled events
     *
     * @since 1.0.0
     * @return void
     */
    private static function clear_scheduled_events() {
        // Clear any cron jobs
        wp_clear_scheduled_hook('rpg_suite_daily_maintenance');
        wp_clear_scheduled_hook('rpg_suite_hourly_character_sync');
    }
    
    /**
     * Clear caches and transients
     *
     * @since 1.0.0
     * @return void
     */
    private static function clear_caches() {
        global $wpdb;
        
        // Delete all transients used by the plugin
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_rpg_%',
                '_transient_timeout_rpg_%'
            )
        );
        
        // Clear object cache groups if using an external object cache
        if (wp_using_ext_object_cache()) {
            wp_cache_flush_group('rpg_suite');
        }
    }
}
```

## Usage

The deactivator is hooked into WordPress's deactivation hook in the main plugin file:

```php
// In rpg-suite.php
register_deactivation_hook(__FILE__, array('RPG_Suite_Deactivator', 'deactivate'));
```

## Important Considerations

### Data Preservation
The deactivator is designed to preserve all user data, including:
- Character posts and meta
- Invention posts and meta
- Plugin settings and options

### Cleanup Tasks
The deactivator performs these cleanup tasks:
1. Removes scheduled cron jobs
2. Clears transient cache data
3. Flushes rewrite rules
4. Provides hooks for extensions to clean up

### Multisite Support
For multisite installations, the deactivator:
1. Loops through all sites in the network
2. Performs site-specific cleanup on each
3. Provides multisite-specific hooks
4. Handles network-wide deactivation properly

## Extension Points

The deactivator provides several action hooks for extensions:

```php
// Single site deactivation
do_action('rpg_suite_deactivated');

// Multisite per-site deactivation
do_action('rpg_suite_deactivated_ms', $blog_id);

// Multisite network-wide deactivation
do_action('rpg_suite_deactivated_network');
```

## Implementation Notes

1. **Data Safety**: The deactivator prioritizes user data preservation
2. **Cache Cleanup**: All transients and caches are properly cleared
3. **Scheduled Events**: All cron jobs are removed to prevent orphaned tasks
4. **Multisite Awareness**: Properly handles both single and multisite installs
5. **Extension Support**: Provides hooks for addon plugins
6. **Performance**: Optimized for efficient database operations
7. **Reusability**: Methods are structured for potential reuse