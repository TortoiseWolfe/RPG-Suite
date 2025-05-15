<?php
/**
 * Permalink Check
 *
 * Checks and prompts for proper WordPress permalink settings.
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

/**
 * Class RPG_Suite_Permalink_Check
 *
 * Checks and prompts for proper WordPress permalink settings
 */
class RPG_Suite_Permalink_Check {

    /**
     * Initialize the permalink check
     */
    public static function init() {
        // Add admin notice if permalinks are not pretty
        add_action('admin_notices', array(__CLASS__, 'check_permalink_structure'));
        
        // Register activation hook to set pretty permalinks
        register_activation_hook('rpg-suite/rpg-suite.php', array(__CLASS__, 'set_pretty_permalinks'));
    }
    
    /**
     * Check permalink structure and display admin notice if needed
     */
    public static function check_permalink_structure() {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if we're on the permalinks page
        $screen = get_current_screen();
        if (isset($screen->id) && $screen->id === 'options-permalink') {
            return;
        }
        
        // Check current permalink structure
        $permalink_structure = get_option('permalink_structure');
        
        if (empty($permalink_structure)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>RPG-Suite Plugin Notice:</strong> 
                    Your WordPress site is using the default plain permalink structure. This may cause 404 errors when viewing character pages.
                </p>
                <p>
                    Please <a href="<?php echo admin_url('options-permalink.php'); ?>">update your permalink settings</a> 
                    to use a pretty permalink structure (recommended: <code>/%postname%/</code>).
                </p>
                <p>
                    <a href="<?php echo admin_url('options-permalink.php'); ?>" class="button button-primary">
                        Update Permalink Settings
                    </a>
                    <a href="<?php echo add_query_arg('rpg_reset_permalinks', '1'); ?>" class="button">
                        Debug Character Permalinks
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Set pretty permalinks on plugin activation
     */
    public static function set_pretty_permalinks() {
        // Only run if current user has permission
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check current permalink structure
        $permalink_structure = get_option('permalink_structure');
        
        // If using plain permalinks, suggest to update
        if (empty($permalink_structure)) {
            // We can't force change permalink structure as it's a site-wide setting
            // Instead, we'll set a transient to show a notice
            set_transient('rpg_suite_permalink_notice', true, HOUR_IN_SECONDS * 24);
            
            // Also, create a flag for the Permalink Debugger to display this info
            update_option('rpg_suite_permalink_warning', true);
        }
    }
}

// Initialize the permalink check
RPG_Suite_Permalink_Check::init();