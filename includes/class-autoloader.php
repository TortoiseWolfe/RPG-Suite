<?php
/**
 * Autoloader for RPG Suite plugin.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/includes
 */

namespace RPG\Suite\Includes;

/**
 * Autoloader class for RPG Suite.
 */
class Autoloader {

    /**
     * Register the autoloader.
     *
     * @return void
     */
    public static function register() {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload function for class files.
     *
     * @param string $class_name The fully-qualified class name.
     * @return void
     */
    public static function autoload($class_name) {
        // Check if the class is in our namespace
        if (false === strpos($class_name, 'RPG\\Suite')) {
            return;
        }

        // Get the relative class name
        $relative_class = str_replace('RPG\\Suite\\', '', $class_name);
        $relative_class = str_replace('\\', '/', $relative_class);

        // Get the path to the file
        $path_parts = explode('/', $relative_class);
        $class_file = array_pop($path_parts);
        $class_file = 'class-' . strtolower(str_replace('_', '-', $class_file)) . '.php';
        
        // Convert namespace path to directory structure
        $namespace_path = implode('/', array_map(function($part) {
            return strtolower($part);
        }, $path_parts));

        // Build the file path
        if (empty($namespace_path)) {
            $file = RPG_SUITE_PLUGIN_DIR . 'src/' . $class_file;
        } else {
            $file = RPG_SUITE_PLUGIN_DIR . 'src/' . $namespace_path . '/' . $class_file;
        }

        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Register the autoloader
Autoloader::register();