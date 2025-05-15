<?php
/**
 * Permalink Debugger
 *
 * Provides detailed tools for debugging permalink issues in the plugin.
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

/**
 * Class RPG_Suite_Permalink_Debugger
 *
 * Advanced permalink debugging tools
 */
class RPG_Suite_Permalink_Debugger {

    /**
     * Initialize the debugger
     */
    public static function init() {
        // Add test endpoint
        add_action('init', array(__CLASS__, 'register_test_endpoint'));
        
        // Check for permalink reset parameter
        add_action('init', array(__CLASS__, 'handle_permalink_reset'), 5);

        // Add test tab to plugin admin
        add_action('admin_menu', array(__CLASS__, 'add_debug_menu'));
    }

    /**
     * Register a test endpoint for permalinks
     */
    public static function register_test_endpoint() {
        add_rewrite_rule(
            'rpg-test/?$',
            'index.php?rpg_test=1',
            'top'
        );
        
        add_rewrite_tag('%rpg_test%', '([^&]+)');
        
        add_filter('query_vars', function($vars) {
            $vars[] = 'rpg_test';
            return $vars;
        });
        
        add_action('template_redirect', function() {
            if (get_query_var('rpg_test') == '1') {
                self::display_permalink_test();
                exit;
            }
        });
    }

    /**
     * Handle permalink reset request
     */
    public static function handle_permalink_reset() {
        if (isset($_GET['rpg_reset_permalinks']) && current_user_can('manage_options')) {
            global $wp_rewrite;
            
            // Force register the post type to make sure it's available
            if (class_exists('RPG_Suite_Character_Post_Type')) {
                $post_type = new RPG_Suite_Character_Post_Type();
                $post_type->register_post_type();
            }
            
            // Get the post type object
            $character_type = get_post_type_object('rpg_character');
            $character_slug = isset($character_type->rewrite['slug']) ? $character_type->rewrite['slug'] : 'character';
            
            // Get sample characters
            $characters = get_posts(array(
                'post_type' => 'rpg_character',
                'posts_per_page' => 5,
                'post_status' => 'publish'
            ));
            
            // Debug output
            $message = '<h1>RPG-Suite Permalink Debugging</h1>';
            
            // Post type info
            $message .= '<h2>Character Post Type</h2>';
            $message .= '<ul>';
            $message .= '<li><strong>Name:</strong> rpg_character</li>';
            $message .= '<li><strong>Slug:</strong> ' . esc_html($character_slug) . '</li>';
            $message .= '<li><strong>Public:</strong> ' . (isset($character_type->public) && $character_type->public ? 'Yes' : 'No') . '</li>';
            $message .= '<li><strong>Has Archive:</strong> ' . (isset($character_type->has_archive) && $character_type->has_archive ? 'Yes' : 'No') . '</li>';
            $message .= '<li><strong>With Front:</strong> ' . (isset($character_type->rewrite['with_front']) && $character_type->rewrite['with_front'] ? 'Yes' : 'No') . '</li>';
            $message .= '</ul>';
            
            // Character list
            $message .= '<h2>Character List</h2>';
            if (!empty($characters)) {
                $message .= '<table border="1" cellpadding="5">';
                $message .= '<tr><th>ID</th><th>Title</th><th>Permalink</th><th>Test Link</th></tr>';
                
                foreach ($characters as $character) {
                    $permalink = get_permalink($character->ID);
                    $message .= '<tr>';
                    $message .= '<td>' . $character->ID . '</td>';
                    $message .= '<td>' . esc_html($character->post_title) . '</td>';
                    $message .= '<td>' . esc_html($permalink) . '</td>';
                    $message .= '<td><a href="' . esc_url($permalink) . '" target="_blank">Test Link</a></td>';
                    $message .= '</tr>';
                }
                
                $message .= '</table>';
            } else {
                $message .= '<p>No characters found. <a href="' . admin_url('post-new.php?post_type=rpg_character') . '">Create one</a>.</p>';
            }
            
            // Rewrite rules
            $message .= '<h2>Rewrite Rules</h2>';
            
            $rules = $wp_rewrite->wp_rewrite_rules();
            $has_character_rules = false;
            
            $message .= '<p>Showing only character-related rules:</p>';
            $message .= '<table border="1" cellpadding="5">';
            $message .= '<tr><th>Rule</th><th>Rewrite</th></tr>';
            
            if (!empty($rules)) {
                foreach ($rules as $rule => $rewrite) {
                    if (strpos($rule, 'character') !== false || strpos($rewrite, 'rpg_character') !== false) {
                        $has_character_rules = true;
                        $message .= '<tr>';
                        $message .= '<td>' . esc_html($rule) . '</td>';
                        $message .= '<td>' . esc_html($rewrite) . '</td>';
                        $message .= '</tr>';
                    }
                }
            }
            
            $message .= '</table>';
            
            if (!$has_character_rules) {
                $message .= '<p><strong>WARNING:</strong> No character rewrite rules found! This indicates a problem with post type registration or rewrite rules.</p>';
            }
            
            // Test endpoint
            $message .= '<h2>Test Endpoint</h2>';
            $test_url = home_url('rpg-test/');
            $message .= '<p><a href="' . esc_url($test_url) . '" target="_blank">Test Endpoint</a> - If this works, rewrite rules are functioning.</p>';
            
            // Environment information
            $message .= '<h2>Environment Information</h2>';
            $message .= '<ul>';
            $message .= '<li><strong>Permalink Structure:</strong> ' . get_option('permalink_structure') . '</li>';
            $message .= '<li><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</li>';
            $message .= '<li><strong>Plugin Version:</strong> ' . RPG_SUITE_VERSION . '</li>';
            $message .= '<li><strong>Using .htaccess:</strong> ' . (file_exists(ABSPATH . '.htaccess') ? 'Yes' : 'No') . '</li>';
            $message .= '</ul>';
            
            // Actions
            $message .= '<h2>Actions</h2>';
            
            // Flush rules
            flush_rewrite_rules();
            $message .= '<p><strong>Rewrite rules have been flushed.</strong></p>';
            
            $message .= '<p><a href="' . admin_url() . '">Return to Dashboard</a></p>';
            
            // Display debug info
            wp_die($message, 'RPG-Suite Permalink Debug', array('response' => 200));
        }
    }

    /**
     * Display permalink test page
     */
    public static function display_permalink_test() {
        echo '<html><head><title>RPG-Suite Permalink Test</title>';
        echo '<style>body{font-family:sans-serif;margin:40px;line-height:1.5}</style></head>';
        echo '<body>';
        echo '<h1>RPG-Suite Permalink Test Successful</h1>';
        echo '<p>If you can see this page, the custom permalinks are working correctly.</p>';
        echo '<h2>Next Steps</h2>';
        echo '<ul>';
        echo '<li><a href="' . home_url('/character/') . '">View Character Archive</a></li>';
        
        // Get a sample character
        $characters = get_posts(array(
            'post_type' => 'rpg_character',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if (!empty($characters)) {
            $permalink = get_permalink($characters[0]->ID);
            echo '<li><a href="' . esc_url($permalink) . '">View Sample Character</a></li>';
        }
        
        echo '<li><a href="' . admin_url() . '">Return to Dashboard</a></li>';
        echo '</ul>';
        echo '</body></html>';
    }

    /**
     * Add debug menu to admin
     */
    public static function add_debug_menu() {
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'tools.php',
                'RPG-Suite Permalinks',
                'RPG-Suite Permalinks',
                'manage_options',
                'rpg-suite-permalinks',
                array(__CLASS__, 'render_debug_page')
            );
        }
    }

    /**
     * Render the debug admin page
     */
    public static function render_debug_page() {
        echo '<div class="wrap">';
        echo '<h1>RPG-Suite Permalink Debug</h1>';
        
        echo '<p>This page provides tools for debugging permalink issues with the RPG-Suite plugin.</p>';
        
        echo '<h2>Actions</h2>';
        echo '<p><a href="' . add_query_arg('rpg_reset_permalinks', '1') . '" class="button button-primary">Flush and Debug Permalinks</a></p>';
        
        echo '<h2>Manual Tests</h2>';
        echo '<ol>';
        echo '<li>Visit the <a href="' . home_url('rpg-test/') . '" target="_blank">test endpoint</a> to verify that custom permalinks are working.</li>';
        echo '<li>Check the <a href="' . home_url('character/') . '" target="_blank">character archive</a> to see if the archive page works.</li>';
        
        // Get a sample character
        $characters = get_posts(array(
            'post_type' => 'rpg_character',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if (!empty($characters)) {
            $permalink = get_permalink($characters[0]->ID);
            echo '<li>Try viewing a <a href="' . esc_url($permalink) . '" target="_blank">sample character</a> directly.</li>';
        } else {
            echo '<li>Create a <a href="' . admin_url('post-new.php?post_type=rpg_character') . '">new character</a> to test individual character permalinks.</li>';
        }
        
        echo '</ol>';
        
        echo '<h2>Troubleshooting</h2>';
        echo '<ul>';
        echo '<li>Make sure your permalink structure is set to something other than "Plain" in <a href="' . admin_url('options-permalink.php') . '">Settings > Permalinks</a>.</li>';
        echo '<li>Ensure your web server has the rewrite module enabled (mod_rewrite for Apache).</li>';
        echo '<li>Check file permissions on your .htaccess file to ensure WordPress can modify it.</li>';
        echo '<li>Try deactivating and reactivating the plugin.</li>';
        echo '</ul>';
        
        echo '</div>';
    }
}

// Initialize the debugger
RPG_Suite_Permalink_Debugger::init();