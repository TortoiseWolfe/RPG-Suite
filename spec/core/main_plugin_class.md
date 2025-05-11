# Main Plugin Class Specification - REVISED

## Purpose
The main plugin class (`RPG_Suite`) serves as the central access point and initialization mechanism for the RPG-Suite plugin.

## Requirements
1. Create a singleton instance accessible via global variable
2. Initialize all subsystems in the correct order
3. Provide access to core components via public properties
4. Handle plugin hooks and lifecycle events
5. Maintain backward compatibility with any existing code

## Lessons Learned from Previous Implementation
1. **Public Property Access**: Ensure core components are accessible as public properties
2. **WordPress + BuddyPress Hooks**: Register hooks at the correct time in WordPress/BuddyPress lifecycle
3. **Capability Registration**: Properly register custom capabilities during activation
4. **Activation/Deactivation**: Clean up properly during deactivation, set up correctly during activation
5. **Simplified Architecture**: Focus on a clean, maintainable implementation without excessive complexity

## Class Definition

The main plugin class should:

1. Be named `RPG_Suite` and defined in file `class-rpg-suite.php`
2. Implement a singleton pattern with:
   - Private static instance property
   - Private constructor to prevent direct instantiation
   - Public static get_instance() method

3. Maintain public properties for:
   - Plugin version
   - Plugin name
   - Character manager reference (RPG_Suite_Character_Manager)
   - Event dispatcher reference (RPG_Suite_Event_Dispatcher)
   - Other core component references

4. Include initialization methods:
   - load_dependencies() - Load required files
   - initialize_core() - Set up core components
   - initialize_character_system() - Set up character management
   - initialize_integrations() - Set up plugin integrations

## Revised Implementation

```php
/**
 * Main plugin class
 */
class RPG_Suite {

    /**
     * Static instance of this class
     *
     * @var RPG_Suite
     */
    private static $instance;

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '0.3.0';

    /**
     * Plugin name
     *
     * @var string
     */
    public $plugin_name = 'rpg-suite';

    /**
     * Character manager instance
     *
     * @var RPG_Suite_Character_Manager
     */
    public $character_manager;

    /**
     * Event dispatcher instance
     *
     * @var RPG_Suite_Event_Dispatcher
     */
    public $event_dispatcher;

    /**
     * BuddyPress integration instance
     *
     * @var RPG_Suite_BuddyPress_Integration
     */
    public $buddypress_integration;

    /**
     * Die code utility instance
     *
     * @var RPG_Suite_Die_Code_Utility
     */
    public $die_code_utility;

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->initialize_core();
        $this->initialize_character_system();
        $this->initialize_integrations();
    }

    /**
     * Get singleton instance
     *
     * @return RPG_Suite
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Core dependencies are loaded via autoloader
    }

    /**
     * Initialize core systems
     */
    private function initialize_core() {
        // Initialize event dispatcher
        $this->event_dispatcher = new RPG_Suite_Event_Dispatcher();
        
        // Initialize die code utility
        $this->die_code_utility = new RPG_Suite_Die_Code_Utility();
        
        // Register hooks
        add_action('init', array($this, 'register_character_post_type'), 10);
        add_action('init', array($this, 'register_character_meta'), 20);
    }

    /**
     * Initialize character system
     */
    private function initialize_character_system() {
        // Initialize character manager
        $this->character_manager = new RPG_Suite_Character_Manager($this->event_dispatcher);
    }

    /**
     * Initialize integrations
     */
    private function initialize_integrations() {
        // Initialize BuddyPress integration if BuddyPress is active
        if (function_exists('buddypress')) {
            // Use bp_init hook with proper priority to ensure BuddyPress is fully loaded
            add_action('bp_init', array($this, 'initialize_buddypress_integration'), 20);
        }
    }

    /**
     * Initialize BuddyPress integration
     */
    public function initialize_buddypress_integration() {
        $this->buddypress_integration = new RPG_Suite_BuddyPress_Integration(
            $this->character_manager,
            $this->event_dispatcher
        );
    }

    /**
     * Register character post type
     */
    public function register_character_post_type() {
        // Use custom capability type and enable map_meta_cap
        register_post_type('rpg_character', array(
            'labels' => array(
                'name'          => __('Characters', 'rpg-suite'),
                'singular_name' => __('Character', 'rpg-suite'),
                // Other labels...
            ),
            'public'       => true,
            'has_archive'  => true,
            'menu_icon'    => 'dashicons-admin-users',
            'supports'     => array('title', 'editor', 'thumbnail', 'author', 'custom-fields'),
            'capability_type' => 'rpg_character',  // Critical: Use custom capability type
            'map_meta_cap' => true,               // Critical: Enable capability mapping
        ));
    }

    /**
     * Register character meta fields
     */
    public function register_character_meta() {
        // Register meta for character attributes with proper auth callbacks
        register_post_meta('rpg_character', '_rpg_attribute_fortitude', array(
            'type'        => 'string',
            'description' => 'Character fortitude attribute',
            'single'      => true,
            'show_in_rest' => true,
            'auth_callback' => function($allowed, $meta_key, $post_id) {
                // Check post type AND capability for proper auth
                return get_post_type($post_id) === 'rpg_character' && 
                       current_user_can('edit_rpg_character', $post_id);
            },
        ));
        
        // Register other character meta fields...
    }
}
```

## Usage Example

```php
// In main plugin file
function rpg_suite_init() {
    // Initialize the plugin
    global $rpg_suite;
    $rpg_suite = RPG_Suite::get_instance();
}
add_action('plugins_loaded', 'rpg_suite_init');

// Helper function for global access
function rpg_suite() {
    global $rpg_suite;
    return $rpg_suite;
}

// Example using the global instance to access a component
function example_usage() {
    $character = rpg_suite()->character_manager->get_character(123);
    return $character;
}
```

## Implementation Notes

1. **Component Access**: All components should be accessible as public properties
2. **Initialization Order**: Core first, Character system next, Integrations last
3. **Hook Registration**: Register hooks at the appropriate time in WordPress/BuddyPress lifecycle
4. **Custom Capabilities**: Use custom capability type and enable mapping for proper permissions
5. **Direct Property Access**: Allow direct access to components via public properties
6. **Global Variable**: Use a simple global variable pattern for ease of access
7. **Proper Auth Callbacks**: Include post type checks in auth callbacks for meta fields
8. **BuddyPress Timing**: Initialize BuddyPress integration on bp_init hook with priority 20