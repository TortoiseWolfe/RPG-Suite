<?php
/**
 * Fired during plugin activation
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 * It handles tasks like registering post types, setting up roles, and flushing rewrite rules.
 */
class RPG_Suite_Activator {

    /**
     * Activate the plugin
     *
     * This method runs when the plugin is activated through the WordPress admin.
     * It sets up any initial plugin configurations needed.
     *
     * @since 0.1.0
     */
    public static function activate() {
        // Store the need to flush rewrite rules in an option
        // This prevents issues with the post type not being registered yet
        update_option('rpg_suite_flush_rewrite_rules', true);
        
        // Add capabilities to roles
        self::add_capabilities();
    }
    
    /**
     * Flush rewrite rules if needed
     * 
     * This should be called on the init hook AFTER post types are registered
     * to ensure rewrite rules are properly generated.
     *
     * @since 0.1.0
     */
    public static function maybe_flush_rewrite_rules() {
        // Check if we need to flush rewrite rules
        if (get_option('rpg_suite_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('rpg_suite_flush_rewrite_rules');
        }
    }
    
    /**
     * Add capabilities to roles
     * 
     * This ensures proper permissions for working with characters.
     */
    private static function add_capabilities() {
        // Get the administrator role
        $admin_role = get_role('administrator');
        
        // Standard capabilities for characters using 'post' as the type
        if ($admin_role) {
            // These are already assigned for the 'post' capability_type,
            // but we'll make sure they're explicitly set for clarity
            $admin_role->add_cap('edit_posts');
            $admin_role->add_cap('edit_others_posts');
            $admin_role->add_cap('publish_posts');
            $admin_role->add_cap('read_private_posts');
            $admin_role->add_cap('delete_posts');
            $admin_role->add_cap('delete_others_posts');
        }
        
        // Set up capabilities for authors and contributors if needed
        $author_role = get_role('author');
        if ($author_role) {
            // Authors can only manage their own characters
            $author_role->add_cap('edit_posts');
            $author_role->add_cap('publish_posts');
            $author_role->add_cap('delete_posts');
        }
    }
}