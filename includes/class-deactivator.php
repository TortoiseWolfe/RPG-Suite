<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/includes
 */

namespace RPG\Suite\Includes;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Flush rewrite rules and perform any cleanup needed.
     * Note that we don't remove data on deactivation for data preservation.
     *
     * @return void
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any transients
        self::clear_transients();
        
        // Disable any active recurring events
        self::disable_scheduled_events();
    }
    
    /**
     * Clear plugin-specific transients.
     *
     * @return void
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Delete all transients with the rpg_suite prefix
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_rpg_suite_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_rpg_suite_%'");
    }
    
    /**
     * Disable any scheduled events.
     *
     * @return void
     */
    private static function disable_scheduled_events() {
        // Clear any scheduled cron jobs
        wp_clear_scheduled_hook('rpg_suite_hourly_event');
        wp_clear_scheduled_hook('rpg_suite_daily_event');
        wp_clear_scheduled_hook('rpg_suite_weekly_event');
        
        // Allow subsystems to clear their own events
        do_action('rpg_suite_deactivation_clear_events');
    }
    
    /**
     * Remove capabilities from roles.
     * 
     * This is intentionally not called during deactivation to prevent
     * data loss if the plugin is temporarily deactivated.
     *
     * @return void
     */
    public static function remove_capabilities() {
        // Get all roles
        $roles = [
            get_role('administrator'),
            get_role('rpg_game_master'),
            get_role('rpg_player'),
            get_role('subscriber'),
        ];
        
        // Define capabilities to remove
        $rpg_capabilities = [
            'play_rpg',
            'gm_rpg',
            'edit_rpg_character',
            'read_rpg_character',
            'delete_rpg_character',
            'edit_rpg_characters',
            'edit_others_rpg_characters',
            'publish_rpg_characters',
            'read_private_rpg_characters',
            'create_npc_character',
        ];
        
        // Remove capabilities from each role
        foreach ($roles as $role) {
            if ($role) {
                foreach ($rpg_capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
        
        // Remove custom roles
        remove_role('rpg_game_master');
        remove_role('rpg_player');
    }
    
    /**
     * Remove database tables.
     * 
     * This is intentionally not called during deactivation to prevent
     * data loss if the plugin is temporarily deactivated.
     *
     * @return void
     */
    public static function remove_database_tables() {
        global $wpdb;
        
        // List of tables to remove
        $tables = [
            $wpdb->prefix . 'rpg_character_items',
            $wpdb->prefix . 'rpg_combat_log',
            $wpdb->prefix . 'rpg_character_attributes',
        ];
        
        // Drop each table
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Remove plugin options.
     * 
     * This is intentionally not called during deactivation to prevent
     * data loss if the plugin is temporarily deactivated.
     *
     * @return void
     */
    public static function remove_options() {
        // List of options to remove
        $options = [
            'rpg_suite_active_subsystems',
            'rpg_suite_character_limit',
            'rpg_suite_game_settings',
            'rpg_suite_version',
            'rpg_suite_db_version',
        ];
        
        // Delete each option
        foreach ($options as $option) {
            delete_option($option);
        }
    }
    
    /**
     * Complete uninstall routine.
     * 
     * This should only be called during plugin uninstall, not deactivation.
     *
     * @return void
     */
    public static function uninstall() {
        // Remove capabilities
        self::remove_capabilities();
        
        // Remove database tables
        self::remove_database_tables();
        
        // Remove options
        self::remove_options();
        
        // Allow subsystems to clean up
        do_action('rpg_suite_uninstall');
    }
}