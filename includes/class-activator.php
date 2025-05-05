<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/includes
 */

namespace RPG\Suite\Includes;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator {

    /**
     * Activate the plugin.
     *
     * Create database tables, register capabilities, and set default options.
     *
     * @return void
     */
    public static function activate() {
        // Ensure roles and capabilities are set
        self::setup_roles_and_capabilities();

        // Set up database tables
        self::create_database_tables();

        // Set default options if they don't exist
        self::set_default_options();

        // Flush rewrite rules to ensure custom post types are registered
        flush_rewrite_rules();
    }

    /**
     * Set up roles and capabilities.
     *
     * @return void
     */
    private static function setup_roles_and_capabilities() {
        // Define roles and capabilities
        $rpg_capabilities = [
            // Generic RPG capabilities
            'play_rpg' => true,   // Basic player capability
            'gm_rpg' => true,     // Game master capability
            
            // Character management
            'edit_rpg_character' => true,
            'read_rpg_character' => true,
            'delete_rpg_character' => true,
            'edit_rpg_characters' => true,
            'edit_others_rpg_characters' => true,
            'publish_rpg_characters' => true,
            'read_private_rpg_characters' => true,
            
            // Allow administrators to create NPCs
            'create_npc_character' => true,
        ];

        // Add capabilities to Administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($rpg_capabilities as $cap => $grant) {
                $admin_role->add_cap($cap);
            }
        }

        // Set up RPG Game Master role if it doesn't exist
        if (!get_role('rpg_game_master')) {
            add_role('rpg_game_master', __('RPG Game Master', 'rpg-suite'), [
                'read' => true,
                'play_rpg' => true,
                'gm_rpg' => true,
                'upload_files' => true,
                'edit_rpg_character' => true,
                'read_rpg_character' => true,
                'delete_rpg_character' => true,
                'edit_rpg_characters' => true,
                'edit_others_rpg_characters' => true,
                'publish_rpg_characters' => true,
                'read_private_rpg_characters' => true,
                'create_npc_character' => true,
            ]);
        }

        // Set up RPG Player role if it doesn't exist
        if (!get_role('rpg_player')) {
            add_role('rpg_player', __('RPG Player', 'rpg-suite'), [
                'read' => true,
                'play_rpg' => true,
                'upload_files' => true,
                'edit_rpg_character' => true,
                'read_rpg_character' => true,
                'delete_rpg_character' => true,
                'edit_rpg_characters' => true,
                'publish_rpg_characters' => true,
            ]);
        }

        // Add capability to subscribers if BuddyPress is active
        $subscriber_role = get_role('subscriber');
        if ($subscriber_role && class_exists('BuddyPress')) {
            $subscriber_role->add_cap('play_rpg');
            $subscriber_role->add_cap('read_rpg_character');
        }
    }

    /**
     * Create custom database tables for the plugin.
     *
     * @return void
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Character-item relationship table (for inventory)
        $table_name = $wpdb->prefix . 'rpg_character_items';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            character_id bigint(20) NOT NULL,
            item_id bigint(20) NOT NULL,
            quantity int(11) NOT NULL DEFAULT '1',
            equipped tinyint(1) NOT NULL DEFAULT '0',
            slot varchar(50) DEFAULT NULL,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY character_id (character_id),
            KEY item_id (item_id)
        ) $charset_collate;";
        
        // Combat log table
        $table_name_log = $wpdb->prefix . 'rpg_combat_log';
        
        $sql .= "CREATE TABLE $table_name_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            encounter_id bigint(20) NOT NULL,
            character_id bigint(20) DEFAULT NULL,
            target_id bigint(20) DEFAULT NULL,
            action_type varchar(50) NOT NULL,
            action_data longtext,
            roll_value int(11) DEFAULT NULL,
            result longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY encounter_id (encounter_id),
            KEY character_id (character_id),
            KEY target_id (target_id)
        ) $charset_collate;";
        
        // Character attributes table (for health, stats, etc.)
        $table_name_attrs = $wpdb->prefix . 'rpg_character_attributes';
        
        $sql .= "CREATE TABLE $table_name_attrs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            character_id bigint(20) NOT NULL,
            attribute_key varchar(50) NOT NULL,
            attribute_value longtext,
            max_value longtext DEFAULT NULL,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY character_attribute (character_id, attribute_key),
            KEY character_id (character_id),
            KEY attribute_key (attribute_key)
        ) $charset_collate;";
        
        // Use WordPress dbDelta to create tables
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Set default options for the plugin.
     *
     * @return void
     */
    private static function set_default_options() {
        // Set default active subsystems if the option doesn't exist
        if (!get_option('rpg_suite_active_subsystems')) {
            add_option('rpg_suite_active_subsystems', [
                'health' => true,
                'geo' => true,
                'dice' => true,
                'inventory' => true,
                'combat' => true,
                'quest' => false,
            ]);
        }
        
        // Set default character limit option
        if (!get_option('rpg_suite_character_limit')) {
            add_option('rpg_suite_character_limit', 2);
        }
        
        // Set default game settings
        if (!get_option('rpg_suite_game_settings')) {
            add_option('rpg_suite_game_settings', [
                'health_enabled' => true,
                'max_health' => 100,
                'geo_enabled' => true,
                'geo_privacy' => 'friends', // Options: 'public', 'friends', 'gm_only'
                'dice_enabled' => true,
                'default_dice' => 'd20',
                'inventory_enabled' => true,
                'max_inventory_slots' => 20,
                'combat_enabled' => true,
                'combat_turn_timeout' => 1440, // In minutes (24 hours default)
                'quest_enabled' => false,
            ]);
        }
        
        // Ensure installation version is stored
        update_option('rpg_suite_version', RPG_SUITE_VERSION);
        update_option('rpg_suite_db_version', '1.0.0');
    }
}