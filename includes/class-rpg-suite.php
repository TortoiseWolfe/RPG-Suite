<?php
/**
 * The main plugin class.
 *
 * This is the main class that coordinates all functionality of the plugin.
 * Defines internationalization, admin hooks, and public hooks.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/includes
 */

namespace RPG\Suite\Includes;

use RPG\Suite\Core\Core;

/**
 * The main plugin class.
 *
 * This is the main class that coordinates all functionality of the plugin.
 * Defines internationalization, admin hooks, and public hooks.
 */
class RPG_Suite {

    /**
     * Array of active subsystems.
     *
     * @var array
     */
    private $active_subsystems;

    /**
     * Core subsystem instance.
     *
     * @var Core
     */
    public $core;
    
    /**
     * Character manager instance.
     *
     * @var Character_Manager
     */
    public $character_manager;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->active_subsystems = [];

        // Load the autoloader if it hasn't been loaded already
        if (!class_exists('RPG\\Suite\\Includes\\Autoloader')) {
            require_once RPG_SUITE_PLUGIN_DIR . 'includes/class-autoloader.php';
            Autoloader::init();
        }

        // Set up the plugin
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @return void
     */
    private function load_dependencies() {
        // Ensure Character Manager is loaded
        require_once RPG_SUITE_PLUGIN_DIR . 'includes/class-character-manager.php';
        
        // Initialize the Character Manager
        $this->character_manager = new Character_Manager();
        
        // Ensure Core class is loaded
        require_once RPG_SUITE_PLUGIN_DIR . 'src/Core/class-core.php';
        
        // The Core subsystem is always loaded
        $this->core = new Core();
        $this->active_subsystems['core'] = $this->core;

        // Get active subsystems from options
        $active_subsystems = get_option('rpg_suite_active_subsystems', [
            'health' => true,
            'geo' => true,
            'dice' => true,
            'inventory' => true,
            'combat' => true,
            'quest' => false,
        ]);

        // Load active subsystems
        foreach ($active_subsystems as $subsystem => $active) {
            if ($active) {
                $this->load_subsystem($subsystem);
            }
        }
    }

    /**
     * Load a specific subsystem.
     *
     * @param string $subsystem The subsystem to load.
     * @return void
     */
    private function load_subsystem($subsystem) {
        // Skip Core as it's already loaded
        if ($subsystem === 'core') {
            return;
        }

        // Try to load the subsystem class
        $class_name = '\\RPG\\Suite\\' . ucfirst($subsystem) . '\\' . ucfirst($subsystem);
        
        if (class_exists($class_name)) {
            $this->active_subsystems[$subsystem] = new $class_name();
        } else {
            // Try a direct file include as fallback
            $file_path = RPG_SUITE_PLUGIN_DIR . 'src/' . ucfirst($subsystem) . '/class-' . strtolower($subsystem) . '.php';
            if (file_exists($file_path)) {
                require_once $file_path;
                if (class_exists($class_name)) {
                    $this->active_subsystems[$subsystem] = new $class_name();
                }
            }
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality of the plugin.
     *
     * @return void
     */
    private function define_admin_hooks() {
        // Register admin hooks for each active subsystem
        foreach ($this->active_subsystems as $subsystem) {
            if (method_exists($subsystem, 'register_admin_hooks')) {
                $subsystem->register_admin_hooks();
            }
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality of the plugin.
     *
     * @return void
     */
    private function define_public_hooks() {
        // Register public hooks for each active subsystem
        foreach ($this->active_subsystems as $subsystem) {
            if (method_exists($subsystem, 'register_public_hooks')) {
                $subsystem->register_public_hooks();
            }
        }

        // Register custom post types and taxonomies
        add_action('init', [$this, 'register_custom_post_types'], 0);
        add_action('init', [$this, 'register_custom_taxonomies'], 0);

        // Load text domain for internationalization
        add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);
    }

    /**
     * Register custom post types required by the plugin.
     *
     * @return void
     */
    public function register_custom_post_types() {
        // Register rpg_character post type
        register_post_type('rpg_character', [
            'labels' => [
                'name' => __('Characters', 'rpg-suite'),
                'singular_name' => __('Character', 'rpg-suite'),
                'menu_name' => __('Characters', 'rpg-suite'),
                'all_items' => __('All Characters', 'rpg-suite'),
                'add_new' => __('Add New', 'rpg-suite'),
                'add_new_item' => __('Add New Character', 'rpg-suite'),
                'edit_item' => __('Edit Character', 'rpg-suite'),
                'new_item' => __('New Character', 'rpg-suite'),
                'view_item' => __('View Character', 'rpg-suite'),
                'search_items' => __('Search Characters', 'rpg-suite'),
                'not_found' => __('No characters found', 'rpg-suite'),
                'not_found_in_trash' => __('No characters found in Trash', 'rpg-suite'),
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-admin-users',
            'supports' => ['title', 'editor', 'thumbnail', 'author', 'custom-fields'],
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'character'],
            'show_in_menu' => 'rpg-suite', // Make this post type appear under the RPG Suite menu
        ]);

        // Allow subsystems to register their own post types
        do_action('rpg_suite_register_post_types');
    }

    /**
     * Register custom taxonomies required by the plugin.
     *
     * @return void
     */
    public function register_custom_taxonomies() {
        // Register character_type taxonomy
        register_taxonomy('character_type', ['rpg_character'], [
            'labels' => [
                'name' => __('Character Types', 'rpg-suite'),
                'singular_name' => __('Character Type', 'rpg-suite'),
                'menu_name' => __('Character Types', 'rpg-suite'),
                'all_items' => __('All Character Types', 'rpg-suite'),
                'edit_item' => __('Edit Character Type', 'rpg-suite'),
                'view_item' => __('View Character Type', 'rpg-suite'),
                'update_item' => __('Update Character Type', 'rpg-suite'),
                'add_new_item' => __('Add New Character Type', 'rpg-suite'),
                'new_item_name' => __('New Character Type Name', 'rpg-suite'),
                'search_items' => __('Search Character Types', 'rpg-suite'),
                'popular_items' => __('Popular Character Types', 'rpg-suite'),
                'not_found' => __('No character types found', 'rpg-suite'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'character-type'],
        ]);

        // Allow subsystems to register their own taxonomies
        do_action('rpg_suite_register_taxonomies');
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @return void
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'rpg-suite',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Run the plugin.
     *
     * @return void
     */
    public function run() {
        // Get the event dispatcher from Core
        $event_dispatcher = $this->core->get_event_dispatcher();
        
        // Register subsystems as event subscribers
        foreach ($this->active_subsystems as $name => $subsystem) {
            if ($name !== 'core' && $subsystem instanceof \RPG\Suite\Core\Event_Subscriber) {
                $event_dispatcher->add_subscriber($subsystem);
            }
        }
        
        // Initialize the Core subsystem
        $this->core->init();

        // Initialize each active subsystem
        foreach ($this->active_subsystems as $name => $subsystem) {
            if ($name !== 'core' && method_exists($subsystem, 'init')) {
                $subsystem->init();
            }
        }
    }

    /**
     * Get an active subsystem.
     *
     * @param string $name The name of the subsystem.
     * @return object|null The subsystem, or null if not active.
     */
    public function get_subsystem($name) {
        return $this->active_subsystems[$name] ?? null;
    }

    /**
     * Get the Core subsystem.
     *
     * @return Core The Core subsystem.
     */
    public function get_core() {
        return $this->core;
    }

    /**
     * Get all active subsystems.
     *
     * @return array The active subsystems.
     */
    public function get_active_subsystems() {
        return $this->active_subsystems;
    }
    
    /**
     * Get the Character Manager.
     *
     * @return Character_Manager The character manager.
     */
    public function get_character_manager() {
        return $this->character_manager;
    }
}