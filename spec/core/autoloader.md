# Autoloader Specification

## Purpose
The autoloader provides PSR-4 compatible class loading for the RPG-Suite plugin.

## Requirements
1. Follow PSR-4 standard for autoloading
2. Map RPG_Suite namespace to includes directory
3. Support nested namespaces
4. Be compatible with WordPress environment
5. Register via spl_autoload_register()

## Class Definition

```php
/**
 * PSR-4 autoloader for RPG-Suite plugin
 */
class Autoloader {
    /**
     * @var string Base namespace for the plugin
     */
    private $namespace;
    
    /**
     * @var string Base directory for plugin files
     */
    private $base_dir;
    
    /**
     * Constructor
     * 
     * @param string $namespace Base namespace
     * @param string $base_dir Base directory path
     */
    public function __construct($namespace, $base_dir) {
        $this->namespace = $namespace;
        $this->base_dir = $base_dir;
    }
    
    /**
     * Register the autoloader
     * 
     * @return void
     */
    public function register() {
        spl_autoload_register([$this, 'load_class']);
    }
    
    /**
     * Load a class based on its name
     * 
     * @param string $class Full class name including namespace
     * @return void
     */
    public function load_class($class) {
        // Implementation logic
    }
}
```

## Usage Example

```php
// In plugin main file
require_once plugin_dir_path(__FILE__) . 'includes/class-autoloader.php';
$autoloader = new RPG_Suite\Autoloader('RPG_Suite', plugin_dir_path(__FILE__) . 'includes/');
$autoloader->register();
```

## Implementation Notes
1. The autoloader should strip the base namespace prefix
2. Convert namespace separators to directory separators
3. Append '.php' extension
4. Check if file exists before requiring it
5. Class filenames should follow "class-{name}.php" pattern 
6. Interface filenames should follow "interface-{name}.php" pattern