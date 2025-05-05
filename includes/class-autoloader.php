<?php
/**
 * RPG Suite Autoloader
 *
 * PSR-4 compliant autoloader for the RPG Suite plugin.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/includes
 */

namespace RPG\Suite\Includes;

/**
 * Autoloader class to handle loading of RPG Suite classes.
 *
 * This autoloader follows PSR-4 standards for the RPG Suite plugin.
 * It maps the namespace prefixes to directory structures.
 */
class Autoloader {

    /**
     * The base namespace prefix for the plugin.
     *
     * @var string
     */
    private $base_namespace = 'RPG\\Suite\\';

    /**
     * The base directory for the plugin.
     *
     * @var string
     */
    private $base_dir;

    /**
     * Initialize the autoloader.
     */
    public function __construct() {
        $this->base_dir = RPG_SUITE_PLUGIN_DIR;
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Autoload callback for PSR-4 class loading.
     *
     * @param string $class The fully-qualified class name.
     * @return void
     */
    public function autoload($class) {
        // If the class doesn't use our namespace prefix, skip it
        if (strpos($class, $this->base_namespace) !== 0) {
            return;
        }

        // Get the relative class name (without the namespace prefix)
        $relative_class = substr($class, strlen($this->base_namespace));

        // Map specific namespaces to directories
        if (strpos($relative_class, 'Includes\\') === 0) {
            // Handle Includes namespace
            $file = $this->base_dir . 'includes/' . $this->convert_class_to_file(substr($relative_class, strlen('Includes\\')));
        } else {
            // Handle subsystem namespaces (Core, Health, Geo, etc.)
            $file = $this->base_dir . 'src/' . $this->convert_class_to_file($relative_class);
        }

        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }

    /**
     * Convert a namespaced class name to a file path.
     *
     * @param string $class The class name (without the base namespace prefix).
     * @return string The file path.
     */
    private function convert_class_to_file($class) {
        // Replace namespace separator with directory separator
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        // Handle class naming convention with 'class-' prefix
        $parts = explode(DIRECTORY_SEPARATOR, $file);
        $class_name = array_pop($parts);
        
        // Check if it's already prefixed with 'class-'
        if (strpos($class_name, 'class-') !== 0) {
            $class_name = 'class-' . strtolower(str_replace('_', '-', $class_name));
        }
        
        $file = implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . $class_name . '.php';

        return $file;
    }

    /**
     * Initialize the autoloader.
     *
     * @return Autoloader The autoloader instance.
     */
    public static function init() {
        static $instance = null;
        
        if ($instance === null) {
            $instance = new self();
        }
        
        return $instance;
    }
}