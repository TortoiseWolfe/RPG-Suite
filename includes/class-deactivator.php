<?php
/**
 * Fired during plugin deactivation.
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
     * @return void
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Optionally clean up user capabilities
        // Note: We're not removing capabilities here by default
        // to prevent data loss. Uncomment if needed.
        // self::remove_capabilities();
    }
    
    /**
     * Remove capabilities from roles.
     *
     * @return void
     */
    private static function remove_capabilities() {
        // Administrator role
        $admin = get_role('administrator');
        $admin->remove_cap('play_rpg');
        $admin->remove_cap('gm_rpg');
        $admin->remove_cap('manage_rpg');
        $admin->remove_cap('edit_quests');
        
        // Editor role
        $editor = get_role('editor');
        $editor->remove_cap('play_rpg');
        $editor->remove_cap('gm_rpg');
        $editor->remove_cap('edit_quests');
        
        // Player roles
        $player_roles = ['author', 'contributor', 'subscriber'];
        foreach ($player_roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->remove_cap('play_rpg');
            }
        }
    }
}