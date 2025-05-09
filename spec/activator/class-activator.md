# Plugin Activator Specification

## Purpose
This specification defines the plugin activator class that handles initialization tasks when the RPG-Suite plugin is activated, including database setup, version checks, and default data creation.

## Requirements
1. Check WordPress version compatibility
2. Check PHP version compatibility
3. Check BuddyPress dependency
4. Register custom post types
5. Set default plugin options
6. Create database schema as needed
7. Handle multisite activations
8. Initialize plugin version data

## Class Definition

```php
/**
 * Plugin activation handler
 *
 * @since 1.0.0
 */
class RPG_Suite_Activator {
    /**
     * Minimum required WordPress version
     *
     * @since 1.0.0
     * @var string
     */
    const MIN_WP_VERSION = '5.7';
    
    /**
     * Minimum required PHP version
     *
     * @since 1.0.0
     * @var string
     */
    const MIN_PHP_VERSION = '7.2';
    
    /**
     * Minimum required BuddyPress version
     *
     * @since 1.0.0
     * @var string
     */
    const MIN_BP_VERSION = '8.0';
    
    /**
     * Activate the plugin
     *
     * @since 1.0.0
     * @param bool $network_wide Whether the plugin is being activated network-wide.
     * @return void
     */
    public static function activate($network_wide) {
        if ($network_wide && is_multisite()) {
            self::activate_multisite();
        } else {
            self::activate_single_site();
        }
    }
    
    /**
     * Activate the plugin on a single site
     *
     * @since 1.0.0
     * @return void
     */
    private static function activate_single_site() {
        // Check requirements
        if (!self::check_requirements()) {
            self::deactivate_plugin();
            return;
        }
        
        // Setup database
        self::setup_database();
        
        // Register post types
        self::register_post_types();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Activate the plugin network-wide
     *
     * @since 1.0.0
     * @return void
     */
    private static function activate_multisite() {
        // Check requirements once for the entire network
        if (!self::check_requirements()) {
            self::deactivate_plugin();
            return;
        }
        
        global $wpdb;
        
        // Get all sites
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            
            // Setup database for this site
            self::setup_database();
            
            // Register post types for this site
            self::register_post_types();
            
            // Set default options for this site
            self::set_default_options();
            
            restore_current_blog();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Check plugin requirements
     *
     * @since 1.0.0
     * @return bool Whether requirements are met.
     */
    private static function check_requirements() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), self::MIN_WP_VERSION, '<')) {
            add_action('admin_notices', array(__CLASS__, 'wordpress_version_notice'));
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
            add_action('admin_notices', array(__CLASS__, 'php_version_notice'));
            return false;
        }
        
        // Check BuddyPress
        if (!class_exists('BuddyPress')) {
            add_action('admin_notices', array(__CLASS__, 'buddypress_missing_notice'));
            return false;
        }
        
        if (defined('BP_VERSION') && version_compare(BP_VERSION, self::MIN_BP_VERSION, '<')) {
            add_action('admin_notices', array(__CLASS__, 'buddypress_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Setup database tables and schema
     *
     * @since 1.0.0
     * @return void
     */
    private static function setup_database() {
        // No custom tables needed initially, using post types
        // This method can be expanded if custom tables are needed later
    }
    
    /**
     * Register custom post types
     *
     * @since 1.0.0
     * @return void
     */
    private static function register_post_types() {
        // Character post type
        register_post_type('rpg_character', array(
            'labels'              => array(
                'name'               => __('Characters', 'rpg-suite'),
                'singular_name'      => __('Character', 'rpg-suite'),
            ),
            'public'              => true,
            'has_archive'         => true,
            'supports'            => array('title', 'editor', 'author', 'thumbnail'),
            'rewrite'             => array('slug' => 'character'),
            'capability_type'     => 'post',
            'register_meta_box_cb' => null,
        ));
        
        // Invention post type
        register_post_type('rpg_invention', array(
            'labels'             => array(
                'name'              => __('Inventions', 'rpg-suite'),
                'singular_name'     => __('Invention', 'rpg-suite'),
            ),
            'public'             => true,
            'has_archive'        => true,
            'supports'           => array('title', 'editor', 'author', 'thumbnail'),
            'rewrite'            => array('slug' => 'invention'),
            'capability_type'    => 'post',
            'register_meta_box_cb' => null,
        ));
    }
    
    /**
     * Set default plugin options
     *
     * @since 1.0.0
     * @return void
     */
    private static function set_default_options() {
        // Set plugin version
        if (!get_option('rpg_suite_version')) {
            add_option('rpg_suite_version', RPG_SUITE_VERSION);
        }
        
        // Set character limit
        if (!get_option('rpg_suite_character_limit')) {
            add_option('rpg_suite_character_limit', 2);
        }
        
        // Set dice animation
        if (!get_option('rpg_suite_dice_animation')) {
            add_option('rpg_suite_dice_animation', 1);
        }
        
        // Set data removal flag (default to not remove)
        if (!get_option('rpg_suite_remove_data_on_uninstall')) {
            add_option('rpg_suite_remove_data_on_uninstall', 0);
        }
    }
    
    /**
     * Deactivate the plugin due to requirements not being met
     *
     * @since 1.0.0
     * @return void
     */
    private static function deactivate_plugin() {
        deactivate_plugins(plugin_basename(RPG_SUITE_PLUGIN_FILE));
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
    
    /**
     * Display WordPress version notice
     *
     * @since 1.0.0
     * @return void
     */
    public static function wordpress_version_notice() {
        $message = sprintf(
            /* translators: %1$s: minimum required version, %2$s: current version */
            __('RPG-Suite requires WordPress version %1$s or higher. You are running version %2$s. Please upgrade WordPress to activate this plugin.', 'rpg-suite'),
            self::MIN_WP_VERSION,
            get_bloginfo('version')
        );
        echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
    }
    
    /**
     * Display PHP version notice
     *
     * @since 1.0.0
     * @return void
     */
    public static function php_version_notice() {
        $message = sprintf(
            /* translators: %1$s: minimum required version, %2$s: current version */
            __('RPG-Suite requires PHP version %1$s or higher. You are running version %2$s. Please contact your host to upgrade PHP.', 'rpg-suite'),
            self::MIN_PHP_VERSION,
            PHP_VERSION
        );
        echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
    }
    
    /**
     * Display BuddyPress missing notice
     *
     * @since 1.0.0
     * @return void
     */
    public static function buddypress_missing_notice() {
        $message = __('RPG-Suite requires BuddyPress to be installed and activated. Please install and activate BuddyPress to use this plugin.', 'rpg-suite');
        echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
    }
    
    /**
     * Display BuddyPress version notice
     *
     * @since 1.0.0
     * @return void
     */
    public static function buddypress_version_notice() {
        $message = sprintf(
            /* translators: %1$s: minimum required version, %2$s: current version */
            __('RPG-Suite requires BuddyPress version %1$s or higher. You are running version %2$s. Please upgrade BuddyPress to activate this plugin.', 'rpg-suite'),
            self::MIN_BP_VERSION,
            BP_VERSION
        );
        echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
    }
}
```

## Usage

The activator is hooked into WordPress's activation hook in the main plugin file:

```php
// In rpg-suite.php
register_activation_hook(__FILE__, array('RPG_Suite_Activator', 'activate'));
```

## Version Upgrade Logic

When updating the plugin, the activator checks the stored version against the current version and runs any necessary upgrade routines:

```php
/**
 * Run version upgrade routines if needed
 *
 * @since 1.0.0
 * @param string $current_version Current plugin version.
 * @param string $stored_version Stored plugin version.
 * @return void
 */
private static function maybe_upgrade($current_version, $stored_version) {
    if (version_compare($stored_version, $current_version, '<')) {
        // Run upgrades based on version
        if (version_compare($stored_version, '1.1.0', '<')) {
            self::upgrade_to_110();
        }
        
        // Update stored version
        update_option('rpg_suite_version', $current_version);
    }
}

/**
 * Upgrade to version 1.1.0
 *
 * @since 1.1.0
 * @return void
 */
private static function upgrade_to_110() {
    // Upgrade logic for version 1.1.0
}
```

## Implementation Notes

1. **Version Requirements**: The activator enforces minimum version requirements to prevent issues.
2. **Multisite Support**: The plugin properly handles activations in multisite environments.
3. **User Feedback**: Clear error messages are shown if requirements aren't met.
4. **Default Data**: Default settings are created during activation.
5. **Graceful Handling**: The plugin deactivates itself if requirements aren't met.
6. **Database Schema**: No custom tables are initially created, leveraging WP post types.
7. **Upgrade Path**: The activator handles version upgrades when the plugin is updated.