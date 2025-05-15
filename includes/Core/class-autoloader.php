<?php
/**
 * Autoloader for RPG-Suite Plugin
 *
 * Handles autoloading of plugin classes while preserving underscores in class names.
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

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
        
        // Set the base directory if not provided - FIXED to use plugin root directory
        if (empty($base_dir)) {
            // Instead of using dirname(__FILE__) which gives 'includes/Core',
            // go up two levels to get the plugin root
            $this->base_dir = plugin_dir_path(dirname(dirname(__FILE__)));
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
        // Skip explicitly loaded core class
        if ($class === 'RPG_Suite') {
            return false;
        }
        
        // Check if the class uses our prefix
        if (strpos($class, $this->prefix) !== 0) {
            return false;
        }
        
        // Get the relative class name (remove the prefix)
        $relative_class = substr($class, strlen($this->prefix));
        
        // Convert underscores to directory separators for subdirectory structure
        $path = str_replace('_', '/', $relative_class);
        
        // Build the file path inside includes directory
        $file_path = $this->base_dir . 'includes/' . $path;
        
        // Break the file path into components
        $path_parts = explode('/', $file_path);
        
        // Format class file name according to convention
        $class_file_name = array_pop($path_parts);
        // Convert to kebab-case (lowercase with hyphens)
        $class_file_name = 'class-' . strtolower(str_replace('_', '-', $class_file_name)) . '.php';
        array_push($path_parts, $class_file_name);
        
        // Reconstruct the file path
        $file_path = implode('/', $path_parts);
        
        // Add debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RPG_Suite Autoloader: Attempting to load ' . $class . ' from ' . $file_path);
        }
        
        // If the file exists, require it and return true
        if (file_exists($file_path)) {
            require_once $file_path;
            return true;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RPG_Suite Autoloader: File not found for ' . $class . ' at ' . $file_path);
        }
        
        return false;
    }
}