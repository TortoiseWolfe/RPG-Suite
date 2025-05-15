<?php
/**
 * Main plugin class
 *
 * This is the main class that handles plugin initialization and serves
 * as the central access point for all plugin components.
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

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
    public $version = '0.1.0';

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
     * Character meta handler instance
     * 
     * @var RPG_Suite_Character_Meta_Handler
     */
    public $character_meta_handler;
    
    /**
     * Character post type instance
     * 
     * @var RPG_Suite_Character_Post_Type
     */
    public $character_post_type;

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
        // Character system classes are loaded by autoloader
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
        add_action('init', array($this, 'register_character_meta'), 20);
        
        // Schedule flush rewrite rules (after post types are registered at priority 10)
        add_action('init', array('RPG_Suite_Activator', 'maybe_flush_rewrite_rules'), 999);
    }

    /**
     * Initialize character system
     */
    private function initialize_character_system() {
        // Initialize character post type
        $this->character_post_type = new RPG_Suite_Character_Post_Type();
        
        // Initialize character meta handler
        $this->character_meta_handler = new RPG_Suite_Character_Meta_Handler($this->die_code_utility);
        
        // Initialize character manager
        $this->character_manager = new RPG_Suite_Character_Manager(
            $this->character_meta_handler,
            $this->die_code_utility,
            $this->event_dispatcher
        );
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
     * Manual flush rewrite rules
     * 
     * Utility function for manually flushing rewrite rules
     * at runtime if needed for debugging.
     */
    public function manual_flush_rewrite_rules() {
        flush_rewrite_rules();
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
                // Check post type AND standard capability for proper auth
                return get_post_type($post_id) === 'rpg_character' && 
                       current_user_can('edit_post', $post_id);
            },
        ));
        
        register_post_meta('rpg_character', '_rpg_attribute_precision', array(
            'type'        => 'string',
            'description' => 'Character precision attribute',
            'single'      => true,
            'show_in_rest' => true,
            'auth_callback' => function($allowed, $meta_key, $post_id) {
                return get_post_type($post_id) === 'rpg_character' && 
                       current_user_can('edit_post', $post_id);
            },
        ));
        
        register_post_meta('rpg_character', '_rpg_attribute_intellect', array(
            'type'        => 'string',
            'description' => 'Character intellect attribute',
            'single'      => true,
            'show_in_rest' => true,
            'auth_callback' => function($allowed, $meta_key, $post_id) {
                return get_post_type($post_id) === 'rpg_character' && 
                       current_user_can('edit_post', $post_id);
            },
        ));
        
        register_post_meta('rpg_character', '_rpg_attribute_charisma', array(
            'type'        => 'string',
            'description' => 'Character charisma attribute',
            'single'      => true,
            'show_in_rest' => true,
            'auth_callback' => function($allowed, $meta_key, $post_id) {
                return get_post_type($post_id) === 'rpg_character' && 
                       current_user_can('edit_post', $post_id);
            },
        ));
        
        register_post_meta('rpg_character', '_rpg_class', array(
            'type'        => 'string',
            'description' => 'Character class/profession',
            'single'      => true,
            'show_in_rest' => true,
            'auth_callback' => function($allowed, $meta_key, $post_id) {
                return get_post_type($post_id) === 'rpg_character' && 
                       current_user_can('edit_post', $post_id);
            },
        ));
        
        register_post_meta('rpg_character', '_rpg_active', array(
            'type'        => 'boolean',
            'description' => 'Whether this is the user\'s active character',
            'single'      => true,
            'show_in_rest' => true,
            'auth_callback' => function($allowed, $meta_key, $post_id) {
                return get_post_type($post_id) === 'rpg_character' && 
                       current_user_can('edit_post', $post_id);
            },
        ));
        
        register_post_meta('rpg_character', '_rpg_invention_points', array(
            'type'        => 'integer',
            'description' => 'Points for creating inventions',
            'single'      => true,
            'show_in_rest' => true,
            'auth_callback' => function($allowed, $meta_key, $post_id) {
                return get_post_type($post_id) === 'rpg_character' && 
                       current_user_can('edit_post', $post_id);
            },
        ));
        
        register_post_meta('rpg_character', '_rpg_fate_tokens', array(
            'type'        => 'integer',
            'description' => 'Tokens for fate manipulation',
            'single'      => true,
            'show_in_rest' => true,
            'auth_callback' => function($allowed, $meta_key, $post_id) {
                return get_post_type($post_id) === 'rpg_character' && 
                       current_user_can('edit_post', $post_id);
            },
        ));
    }
    
    /**
     * Test if the plugin is working correctly
     * 
     * This method can be called to test if the plugin is initialized correctly,
     * including the autoloader, component initialization, and dependencies.
     * 
     * @return array Test results
     */
    public function test_plugin() {
        $results = array(
            'status' => 'success',
            'components' => array(),
            'messages' => array(),
        );
        
        // Test event system
        if ($this->event_dispatcher instanceof RPG_Suite_Event_Dispatcher) {
            $results['components']['event_system'] = 'success';
        } else {
            $results['components']['event_system'] = 'failed';
            $results['messages'][] = 'Event system not properly initialized';
            $results['status'] = 'failed';
        }
        
        // Test die code utility
        if ($this->die_code_utility instanceof RPG_Suite_Die_Code_Utility) {
            $results['components']['die_code_utility'] = 'success';
            
            // Test die code functions
            $test_die_code = '2d7+1';
            $parsed = $this->die_code_utility->parse_die_code($test_die_code);
            if ($parsed['dice'] === 2 && $parsed['modifier'] === 1) {
                $results['components']['die_code_functions'] = 'success';
            } else {
                $results['components']['die_code_functions'] = 'failed';
                $results['messages'][] = 'Die code parsing failed';
                $results['status'] = 'failed';
            }
        } else {
            $results['components']['die_code_utility'] = 'failed';
            $results['messages'][] = 'Die code utility not properly initialized';
            $results['status'] = 'failed';
        }
        
        // Test character post type
        if ($this->character_post_type instanceof RPG_Suite_Character_Post_Type) {
            $results['components']['character_post_type'] = 'success';
        } else {
            $results['components']['character_post_type'] = 'failed';
            $results['messages'][] = 'Character post type not properly initialized';
            $results['status'] = 'failed';
        }
        
        // Test character meta handler
        if ($this->character_meta_handler instanceof RPG_Suite_Character_Meta_Handler) {
            $results['components']['character_meta_handler'] = 'success';
        } else {
            $results['components']['character_meta_handler'] = 'failed';
            $results['messages'][] = 'Character meta handler not properly initialized';
            $results['status'] = 'failed';
        }
        
        // Test character manager
        if ($this->character_manager instanceof RPG_Suite_Character_Manager) {
            $results['components']['character_manager'] = 'success';
        } else {
            $results['components']['character_manager'] = 'failed';
            $results['messages'][] = 'Character manager not properly initialized';
            $results['status'] = 'failed';
        }
        
        // Test BuddyPress integration (only if BuddyPress is active)
        if (function_exists('buddypress')) {
            if ($this->buddypress_integration instanceof RPG_Suite_BuddyPress_Integration) {
                $results['components']['buddypress_integration'] = 'success';
            } else {
                $results['components']['buddypress_integration'] = 'failed';
                $results['messages'][] = 'BuddyPress integration not properly initialized';
                $results['status'] = 'failed';
            }
        } else {
            $results['components']['buddypress_integration'] = 'not_tested';
            $results['messages'][] = 'BuddyPress not active, integration not tested';
        }
        
        return $results;
    }
}