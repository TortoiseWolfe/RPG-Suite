# Main Plugin Class Specification

## Purpose
The main plugin class (`RPG_Suite`) serves as the central access point and initialization mechanism for the RPG-Suite plugin.

## Requirements
1. Create a singleton instance accessible via global variable
2. Initialize all subsystems in the correct order
3. Provide access to core components via public properties
4. Handle plugin hooks and lifecycle events
5. Maintain backward compatibility with any existing code

## Class Definition

```php
/**
 * Main plugin class for RPG-Suite
 */
class RPG_Suite {
    /**
     * @var RPG_Suite Singleton instance
     */
    private static $instance = null;
    
    /**
     * @var string Plugin version
     */
    public $version = '1.0.0';
    
    /**
     * @var string Plugin name
     */
    public $plugin_name = 'rpg-suite';
    
    /**
     * @var Character_Manager Character management system
     */
    public $character_manager;
    
    /**
     * @var Event_Dispatcher Event system
     */
    public $event_dispatcher;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->load_dependencies();
        $this->initialize_core();
        $this->initialize_character_system();
        $this->initialize_integrations();
    }
    
    /**
     * Get or create the singleton instance
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
     * Load required dependencies
     * 
     * @return void
     */
    private function load_dependencies() {
        // Implementation logic
    }
    
    /**
     * Initialize core components
     * 
     * @return void
     */
    private function initialize_core() {
        // Implementation logic
    }
    
    /**
     * Initialize character management system
     * 
     * @return void
     */
    private function initialize_character_system() {
        // Implementation logic
    }
    
    /**
     * Initialize integrations with other plugins
     * 
     * @return void
     */
    private function initialize_integrations() {
        // Implementation logic
    }
}
```

## Usage Example

```php
// In plugin main file, after autoloader
global $rpg_suite;
$rpg_suite = RPG_Suite::get_instance();

// Accessing components from anywhere
global $rpg_suite;
$character = $rpg_suite->character_manager->get_active_character($user_id);
```

## Implementation Notes
1. The constructor should be private to enforce singleton pattern
2. Component initialization order matters:
   - Core components first (event system, etc.)
   - Character system next
   - Integrations last (as they may depend on other systems)
3. Public properties should be set during initialization
4. Hook registration should happen in the initialization methods
5. The global variable should be registered early
6. Component instances should be stored as public properties for easy access