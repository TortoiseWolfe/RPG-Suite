<?php
/**
 * Test Data Generator
 *
 * Creates test characters and data for development and testing.
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

/**
 * Class RPG_Suite_Test_Data
 *
 * Provides methods for generating test data
 */
class RPG_Suite_Test_Data {

    /**
     * Initialize the test data generator
     */
    public static function init() {
        // Add admin page for generating test data
        add_action('admin_menu', array(__CLASS__, 'add_test_data_menu'));
        
        // Hook for creating test data from URL param
        add_action('init', array(__CLASS__, 'check_for_test_data_param'));
    }
    
    /**
     * Check for test data generation parameter
     */
    public static function check_for_test_data_param() {
        if (isset($_GET['rpg_generate_test_data']) && current_user_can('manage_options')) {
            $count = isset($_GET['count']) ? intval($_GET['count']) : 1;
            $count = min($count, 10); // Limit to 10 characters at once
            
            $characters = self::generate_test_characters($count);
            
            // Generate output message
            $message = '<h1>RPG-Suite Test Data Generated</h1>';
            
            if (!empty($characters)) {
                $message .= '<h2>Generated Characters</h2>';
                $message .= '<table border="1" cellpadding="5">';
                $message .= '<tr><th>ID</th><th>Title</th><th>Class</th><th>Permalink</th></tr>';
                
                foreach ($characters as $character) {
                    $permalink = get_permalink($character['id']);
                    $message .= '<tr>';
                    $message .= '<td>' . $character['id'] . '</td>';
                    $message .= '<td>' . esc_html($character['title']) . '</td>';
                    $message .= '<td>' . esc_html($character['class']) . '</td>';
                    $message .= '<td><a href="' . esc_url($permalink) . '" target="_blank">' . esc_html($permalink) . '</a></td>';
                    $message .= '</tr>';
                }
                
                $message .= '</table>';
                
                // Added permalink debugging
                $message .= '<h2>Rewrite Rules Debug</h2>';
                $message .= '<p>If you experience 404 errors when viewing character pages, please visit the <a href="' . add_query_arg('rpg_reset_permalinks', '1') . '">permalink debug page</a>.</p>';
                
                $message .= '<p>Or try the <a href="' . home_url('rpg-test/') . '">test endpoint</a> to verify rewrite rules.</p>';
            } else {
                $message .= '<p>Failed to generate test characters.</p>';
            }
            
            $message .= '<p><a href="' . admin_url() . '">Return to Dashboard</a></p>';
            
            // Display results
            wp_die($message, 'Test Data Generated', array('response' => 200));
        }
    }
    
    /**
     * Generate test characters
     *
     * @param int $count Number of characters to generate
     * @return array Generated character data
     */
    public static function generate_test_characters($count = 1) {
        $characters = array();
        
        // Make sure Character Manager is available
        if (!class_exists('RPG_Suite_Character_Manager')) {
            return $characters;
        }
        
        // Get required classes
        global $rpg_suite;
        
        if (!$rpg_suite || !$rpg_suite->character_manager) {
            // Direct instantiation if global not set up
            require_once plugin_dir_path(dirname(__FILE__)) . 'Character/class-character-meta-handler.php';
            require_once plugin_dir_path(dirname(__FILE__)) . 'Character/class-character-manager.php';
            
            $meta_handler = new RPG_Suite_Character_Meta_Handler(null);
            $character_manager = new RPG_Suite_Character_Manager($meta_handler, null, null);
        } else {
            $character_manager = $rpg_suite->character_manager;
        }
        
        // Character classes and their primary attributes
        $classes = array(
            'aeronaut' => array(
                'fortitude' => '3d7+1',
                'precision' => '2d7+2',
                'intellect' => '2d7',
                'charisma' => '2d7'
            ),
            'mechwright' => array(
                'fortitude' => '2d7',
                'precision' => '2d7+1',
                'intellect' => '3d7+2',
                'charisma' => '2d7'
            ),
            'aethermancer' => array(
                'fortitude' => '2d7',
                'precision' => '2d7',
                'intellect' => '3d7+2',
                'charisma' => '2d7+1'
            ),
            'diplomat' => array(
                'fortitude' => '2d7',
                'precision' => '2d7',
                'intellect' => '2d7+1',
                'charisma' => '3d7+2'
            )
        );
        
        // Character name prefixes and suffixes to generate unique names
        $name_prefixes = array('Captain', 'Admiral', 'Engineer', 'Doctor', 'Professor', 'Agent', 'Chancellor', 'Master');
        $name_parts = array('Avery', 'Clarke', 'Drake', 'Edison', 'Farraday', 'Gable', 'Hayes', 'Irwin', 'Juno', 'Knox', 'Lemming', 'Morgan');
        $name_suffixes = array('of the Skyways', 'the Inventor', 'the Brilliant', 'of High Tower', 'the Diplomat', 'the Explorer', 'of New London', 'the Navigator');
        
        // Current user ID
        $current_user_id = get_current_user_id();
        
        // Generate characters
        for ($i = 0; $i < $count; $i++) {
            // Generate a random name
            $prefix = $name_prefixes[array_rand($name_prefixes)];
            $name = $name_parts[array_rand($name_parts)];
            $suffix = $name_suffixes[array_rand($name_suffixes)];
            $full_name = "$prefix $name $suffix";
            
            // Select a random class
            $class_keys = array_keys($classes);
            $selected_class = $class_keys[array_rand($class_keys)];
            $attributes = $classes[$selected_class];
            
            // Create character
            $character_data = array(
                'name' => $full_name,
                'description' => 'This is a test character of the ' . ucfirst($selected_class) . ' class, generated for permalink testing.',
                'class' => $selected_class,
                'attributes' => $attributes,
                'user_id' => $current_user_id,
                'active' => ($i === 0), // Make the first one active
                'invention_points' => rand(0, 5),
                'fate_tokens' => rand(0, 3)
            );
            
            $character_id = $character_manager->create_character($character_data);
            
            if (!is_wp_error($character_id)) {
                $characters[] = array(
                    'id' => $character_id,
                    'title' => $full_name,
                    'class' => $selected_class,
                );
            }
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        return $characters;
    }
    
    /**
     * Add test data menu to admin
     */
    public static function add_test_data_menu() {
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'tools.php',
                'RPG-Suite Test Data',
                'RPG-Suite Test Data',
                'manage_options',
                'rpg-suite-test-data',
                array(__CLASS__, 'render_test_data_page')
            );
        }
    }
    
    /**
     * Render the test data admin page
     */
    public static function render_test_data_page() {
        echo '<div class="wrap">';
        echo '<h1>RPG-Suite Test Data Generator</h1>';
        
        echo '<p>Use this page to generate test characters for the RPG-Suite plugin.</p>';
        
        echo '<h2>Generate Test Characters</h2>';
        echo '<p>Generate test characters with random attributes and classes.</p>';
        echo '<p>';
        echo '<a href="' . add_query_arg('rpg_generate_test_data', '1') . '" class="button button-primary">Generate 1 Character</a> ';
        echo '<a href="' . add_query_arg(array('rpg_generate_test_data' => '1', 'count' => '5')) . '" class="button">Generate 5 Characters</a>';
        echo '</p>';
        
        // Character count
        $character_count = wp_count_posts('rpg_character');
        $total_characters = $character_count->publish + $character_count->draft + $character_count->pending;
        
        echo '<h2>Current Test Data</h2>';
        echo '<p>Total characters: ' . $total_characters . '</p>';
        
        // Get recent characters
        $characters = get_posts(array(
            'post_type' => 'rpg_character',
            'posts_per_page' => 10,
            'post_status' => 'publish'
        ));
        
        if (!empty($characters)) {
            echo '<table class="widefat">';
            echo '<thead><tr><th>ID</th><th>Title</th><th>Class</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($characters as $character) {
                $class = get_post_meta($character->ID, '_rpg_class', true);
                echo '<tr>';
                echo '<td>' . $character->ID . '</td>';
                echo '<td>' . esc_html($character->post_title) . '</td>';
                echo '<td>' . esc_html($class) . '</td>';
                echo '<td>';
                echo '<a href="' . get_permalink($character->ID) . '" target="_blank">View</a> | ';
                echo '<a href="' . get_edit_post_link($character->ID) . '">Edit</a>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>No characters found.</p>';
        }
        
        echo '</div>';
    }
}

// Initialize the test data generator
RPG_Suite_Test_Data::init();