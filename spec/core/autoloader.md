# Autoloader Specification - REVISED

## Purpose
This specification defines the autoloader class for the RPG-Suite plugin, which handles class loading based on namespaces and provides PSR-4 compatibility.

## Requirements
1. Implement PSR-4 compatible autoloading
2. Handle class names that contain underscores properly
3. Provide efficient class loading based on namespaces
4. Support plugin directory structure
5. Register with SPL autoloader stack

## Critical Issues Addressed
1. **Underscore Handling**: Previous implementation incorrectly converted underscores to directory separators, causing class loading failures. This is now fixed by ONLY converting namespace separators and preserving underscores in class names.
2. **Namespace Resolution**: Previous implementation had issues with namespace resolution. The revised implementation properly handles namespaces.

## Class Definition

The Autoloader class should:
1. Be named `RPG_Suite_Autoloader`
2. Be defined in file `class-autoloader.php`
3. Have a namespace prefix property set to 'RPG_Suite_'
4. Have a base directory property for plugin classes
5. Initialize the base directory in the constructor
6. Register itself with SPL autoload registry
7. Have a load_class method that:
   - Checks if the class starts with our prefix
   - Gets the relative class name by removing the prefix
   - Converts namespace-like separators to directory separators (for nested directories)
   - Preserves underscores in class names (critical fix)
   - Requires the file if it exists

## Correct Implementation (Replace Previous Version)

```php
/**
 * Class RPG_Suite_Autoloader
 *
 * Handles autoloading of plugin classes while preserving underscores in class names.
 */
class RPG_Suite_Autoloader {
    /**
     * The prefix for plugin classes
     *
     * @var string
     */
    private $prefix;
    
    /**
     * Base directory for plugin classes
     *
     * @var string
     */
    private $base_dir;
    
    /**
     * Constructor
     *
     * @param string $prefix   The namespace prefix for plugin classes
     * @param string $base_dir The base directory for plugin classes
     */
    public function __construct($prefix = 'RPG_Suite_', $base_dir = '') {
        $this->prefix = $prefix;
        
        // Set the base directory if not provided
        if (empty($base_dir)) {
            $this->base_dir = plugin_dir_path(dirname(__FILE__)) . 'includes/';
        } else {
            $this->base_dir = $base_dir;
        }
        
        // Register the autoloader
        spl_autoload_register(array($this, 'load_class'));
    }
    
    /**
     * Load a class file
     *
     * @param string $class The fully-qualified class name to load
     * @return bool Whether the class was loaded or not
     */
    public function load_class($class) {
        // Check if the class uses our prefix
        if (strpos($class, $this->prefix) !== 0) {
            return false;
        }
        
        // Get the relative class name (remove the prefix)
        $relative_class = substr($class, strlen($this->prefix));
        
        // Convert namespace separators to directory separators
        // CRITICAL: Do NOT replace underscores, only namespace separators
        $file = $this->base_dir . str_replace('\\', '/', $relative_class);
        
        // Break the file path into components
        $path_parts = explode('/', $file);
        
        // Format class file name according to convention
        $class_file_name = array_pop($path_parts);
        array_push($path_parts, 'class-' . strtolower($class_file_name) . '.php');
        
        // Reconstruct the file path
        $file_path = implode('/', $path_parts);
        
        // If the file exists, require it and return true
        if (file_exists($file_path)) {
            require_once $file_path;
            return true;
        }
        
        return false;
    }
}
```

## Usage Example

In the main plugin file, require the autoloader, initialize it, and register it. This will ensure that:
- Autoloader loads before any other plugin classes
- Class names with underscores will load correctly
- Examples of correctly resolved paths:
  - RPG_Suite_Core_RPG_Suite -> includes/Core/class-rpg_suite.php
  - RPG_Suite_Core_Die_Code_Utility -> includes/Core/class-die_code_utility.php

## Implementation Notes

1. **Class Naming Convention**: All plugin classes should use the `RPG_Suite_` prefix followed by underscore-separated names
2. **Critical Fix**: The autoloader preserves underscores in class names, only handling the namespace structure by replacing backslashes
3. **File Naming**: All class files should use the format `class-{lowercased_classname}.php`
4. **Base Directory**: The autoloader assumes classes are in the `includes` directory by default
5. **Class Prefix**: The autoloader only handles classes with the `RPG_Suite_` prefix

## Testing Recommendations

1. Test autoloading of classes with underscores in their names
   - Example: RPG_Suite_Core_Die_Code_Utility -> includes/Core/class-die_code_utility.php
2. Test autoloading of classes in nested directories
   - Example: RPG_Suite_BuddyPress_Integration -> includes/BuddyPress/class-integration.php
3. Test edge cases around similar class names
4. Check performance with multiple class loading operations