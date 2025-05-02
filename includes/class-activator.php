<?php
/**
 * Fired during plugin activation.
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
     * @return void
     */
    public static function activate() {
        // Check WordPress and PHP version requirements
        if (version_compare(PHP_VERSION, '8.2', '<')) {
            deactivate_plugins(RPG_SUITE_PLUGIN_BASENAME);
            wp_die('RPG Suite requires PHP 8.2 or higher.');
        }

        global $wp_version;
        if (version_compare($wp_version, '6.8', '<')) {
            deactivate_plugins(RPG_SUITE_PLUGIN_BASENAME);
            wp_die('RPG Suite requires WordPress 6.8 or higher.');
        }

        // Check for BuddyPress
        if (!class_exists('BuddyPress')) {
            deactivate_plugins(RPG_SUITE_PLUGIN_BASENAME);
            wp_die('RPG Suite requires BuddyPress to be installed and activated.');
        }

        // Set default options
        if (!get_option('rpg_suite_active_subsystems')) {
            add_option('rpg_suite_active_subsystems', [
                'health' => true,
                'geo' => true,
                'dice' => true,
                'inventory' => true,
                'combat' => true,
                'quest' => false, // Opt-in by default
            ]);
        }

        // Create necessary database tables
        self::create_database_tables();

        // Create required pages
        self::create_pages();

        // Add capabilities
        self::add_capabilities();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create necessary database tables.
     *
     * @return void
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Combat logs table
        $table_name = $wpdb->prefix . 'rpg_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            log_type varchar(50) NOT NULL,
            log_data longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY log_type (log_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Inventory table
        $inventory_table = $wpdb->prefix . 'rpg_inventory';
        $sql .= "CREATE TABLE IF NOT EXISTS $inventory_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            character_id bigint(20) NOT NULL,
            item_id bigint(20) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            equipped tinyint(1) NOT NULL DEFAULT 0,
            slot varchar(50) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY character_id (character_id),
            KEY item_id (item_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create required pages.
     *
     * @return void
     */
    private static function create_pages() {
        // Create RPG Dashboard page if it doesn't exist
        $dashboard = get_page_by_path('rpg-dashboard');
        if (!$dashboard) {
            wp_insert_post([
                'post_title' => 'RPG Dashboard',
                'post_content' => '[rpg_dashboard]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);
        }
    }

    /**
     * Add capabilities to roles.
     *
     * @return void
     */
    private static function add_capabilities() {
        // Administrator role
        $admin = get_role('administrator');
        $admin->add_cap('play_rpg');
        $admin->add_cap('gm_rpg');
        $admin->add_cap('manage_rpg');
        $admin->add_cap('edit_quests');
        
        // Editor role (Game Master)
        $editor = get_role('editor');
        $editor->add_cap('play_rpg');
        $editor->add_cap('gm_rpg');
        $editor->add_cap('edit_quests');
        
        // Author/Contributor/Subscriber roles (Players)
        $player_roles = ['author', 'contributor', 'subscriber'];
        foreach ($player_roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('play_rpg');
            }
        }
    }
}