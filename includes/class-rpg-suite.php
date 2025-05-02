<?php
/**
 * The core plugin class.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/includes
 */

namespace RPG\Suite\Includes;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class RPG_Suite {

    /**
     * The subsystems of the RPG Suite.
     *
     * @var array
     */
    protected $subsystems = [];

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->register_subsystems();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @return void
     */
    private function load_dependencies() {
        // Include core files
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @return void
     */
    private function set_locale() {
        add_action('plugins_loaded', function() {
            load_plugin_textdomain(
                'rpg-suite',
                false,
                dirname(RPG_SUITE_PLUGIN_BASENAME) . '/languages/'
            );
        });
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @return void
     */
    private function define_admin_hooks() {
        // Admin hooks
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @return void
     */
    private function define_public_hooks() {
        // Public hooks
    }

    /**
     * Register all subsystems.
     *
     * @return void
     */
    private function register_subsystems() {
        // Core is always active
        $this->subsystems['core'] = new \RPG\Suite\Core\Core();
        
        // Register optional subsystems
        $active_subsystems = get_option('rpg_suite_active_subsystems', [
            'health' => true,
            'geo' => true,
            'dice' => true,
            'inventory' => true,
            'combat' => true,
            'quest' => false, // Opt-in by default
        ]);
        
        // Initialize active subsystems
        if (!empty($active_subsystems['health'])) {
            $this->subsystems['health'] = new \RPG\Suite\Health\Health();
        }
        
        if (!empty($active_subsystems['geo'])) {
            $this->subsystems['geo'] = new \RPG\Suite\Geo\Geo();
        }
        
        if (!empty($active_subsystems['dice'])) {
            $this->subsystems['dice'] = new \RPG\Suite\Dice\Dice();
        }
        
        if (!empty($active_subsystems['inventory'])) {
            $this->subsystems['inventory'] = new \RPG\Suite\Inventory\Inventory();
        }
        
        if (!empty($active_subsystems['combat'])) {
            $this->subsystems['combat'] = new \RPG\Suite\Combat\Combat();
        }
        
        if (!empty($active_subsystems['quest'])) {
            $this->subsystems['quest'] = new \RPG\Suite\Quest\Quest();
        }
    }

    /**
     * Run the plugin.
     *
     * @return void
     */
    public function run() {
        // Run the plugin's core functionality
        foreach ($this->subsystems as $subsystem) {
            if (method_exists($subsystem, 'init')) {
                $subsystem->init();
            }
        }
    }
}