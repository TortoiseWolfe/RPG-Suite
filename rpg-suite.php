<?php
/**
 * Plugin Name: RPG-Suite
 * Plugin URI: https://tortoiseWolfe.com/rpg-suite
 * Description: Complete RPG system for WordPress with character management and BuddyPress integration
 * Version: 0.1.1
 * Author: TortoiseWolfe
 * License: GPL-2.0-or-later
 * Text Domain: rpg-suite
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('RPG_SUITE_VERSION', '0.1.1');
define('RPG_SUITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RPG_SUITE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Load plugin dependencies
 */
require_once RPG_SUITE_PLUGIN_DIR . 'includes/Core/class-health-manager.php';

// Initialize the health manager as a global
global $rpg_suite_health_manager;
$rpg_suite_health_manager = new RPG_Suite_Health_Manager();

/**
 * Initialize the plugin
 */
function rpg_suite_init() {
    // Register the character post type
    rpg_suite_register_post_type();
    
    // Register REST API endpoints
    add_action('rest_api_init', 'rpg_suite_register_rest_routes');
}
add_action('init', 'rpg_suite_init');

/**
 * Debug logging function
 */
function rpg_suite_log($message, $tag = 'INFO') {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $log_message = "$timestamp [$tag] $message" . PHP_EOL;
    error_log($log_message, 3, $log_file);
}

/**
 * Clear character-related caches
 */
function rpg_suite_clear_character_caches($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'rpg_character') {
        return;
    }
    
    $user_id = $post->post_author;
    
    // Clear object cache
    wp_cache_delete('rpg_active_character_' . $user_id, 'rpg_suite');
    wp_cache_delete('rpg_user_characters_' . $user_id, 'rpg_suite');
    
    // Clear transients
    delete_transient('rpg_active_character_' . $user_id);
    delete_transient('rpg_user_characters_' . $user_id);
    
    // If Redis Object Cache plugin is active, flush specific keys
    if (function_exists('wp_cache_flush_group')) {
        wp_cache_flush_group('rpg_suite');
    }
    
    // Log cache clearing
    rpg_suite_log("Cleared caches for character ID: $post_id, User ID: $user_id", 'CACHE');
}

/**
 * Register the character post type
 */
function rpg_suite_register_post_type() {
    $labels = array(
        'name'               => __('Characters', 'rpg-suite'),
        'singular_name'      => __('Character', 'rpg-suite'),
        'add_new'            => __('Add New', 'rpg-suite'),
        'add_new_item'       => __('Add New Character', 'rpg-suite'),
        'edit_item'          => __('Edit Character', 'rpg-suite'),
        'new_item'           => __('New Character', 'rpg-suite'),
        'view_item'          => __('View Character', 'rpg-suite'),
        'search_items'       => __('Search Characters', 'rpg-suite'),
        'not_found'          => __('No characters found', 'rpg-suite'),
        'not_found_in_trash' => __('No characters found in trash', 'rpg-suite'),
        'menu_name'          => __('Characters', 'rpg-suite'),
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'character'),
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-groups',
        'supports'            => array('title', 'editor', 'thumbnail', 'author'),
        'show_in_rest'        => true,
        'rest_base'           => 'characters',
    );

    register_post_type('rpg_character', $args);
}

/**
 * Register meta boxes for character attributes
 */
function rpg_suite_add_meta_boxes() {
    add_meta_box(
        'rpg_character_attributes',
        __('Character Attributes', 'rpg-suite'),
        'rpg_suite_character_attributes_callback',
        'rpg_character',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'rpg_suite_add_meta_boxes');

/**
 * Character attributes meta box callback
 */
function rpg_suite_character_attributes_callback($post) {
    global $rpg_suite_health_manager;
    wp_nonce_field('rpg_suite_save_character', 'rpg_suite_character_nonce');
    
    $class = get_post_meta($post->ID, '_rpg_class', true);
    $active = get_post_meta($post->ID, '_rpg_active', true);
    $fortitude = get_post_meta($post->ID, '_rpg_fortitude', true);
    $precision = get_post_meta($post->ID, '_rpg_precision', true);
    $intellect = get_post_meta($post->ID, '_rpg_intellect', true);
    $charisma = get_post_meta($post->ID, '_rpg_charisma', true);
    
    // Get health data
    $current_hp = $rpg_suite_health_manager->get_current_health($post->ID);
    $health_percentage = $rpg_suite_health_manager->get_health_percentage($post->ID);
    $health_status = $rpg_suite_health_manager->get_health_status($post->ID);
    ?>
    <p>
        <label for="rpg_class"><?php _e('Class:', 'rpg-suite'); ?></label><br>
        <input type="text" id="rpg_class" name="rpg_class" value="<?php echo esc_attr($class); ?>" class="widefat" />
    </p>
    <p>
        <label for="rpg_active">
            <input type="checkbox" id="rpg_active" name="rpg_active" value="1" <?php checked($active, '1'); ?> />
            <?php _e('Active Character', 'rpg-suite'); ?>
        </label>
    </p>
    <h4><?php _e('Attributes', 'rpg-suite'); ?></h4>
    <p>
        <label for="rpg_fortitude"><?php _e('Fortitude:', 'rpg-suite'); ?></label><br>
        <input type="number" id="rpg_fortitude" name="rpg_fortitude" value="<?php echo esc_attr($fortitude); ?>" min="1" max="5" class="small-text" />
    </p>
    <p>
        <label for="rpg_precision"><?php _e('Precision:', 'rpg-suite'); ?></label><br>
        <input type="number" id="rpg_precision" name="rpg_precision" value="<?php echo esc_attr($precision); ?>" min="1" max="5" class="small-text" />
    </p>
    <p>
        <label for="rpg_intellect"><?php _e('Intellect:', 'rpg-suite'); ?></label><br>
        <input type="number" id="rpg_intellect" name="rpg_intellect" value="<?php echo esc_attr($intellect); ?>" min="1" max="5" class="small-text" />
    </p>
    <p>
        <label for="rpg_charisma"><?php _e('Charisma:', 'rpg-suite'); ?></label><br>
        <input type="number" id="rpg_charisma" name="rpg_charisma" value="<?php echo esc_attr($charisma); ?>" min="1" max="5" class="small-text" />
    </p>
    <h4><?php _e('Health', 'rpg-suite'); ?></h4>
    <p>
        <label for="rpg_current_hp"><?php _e('Current HP:', 'rpg-suite'); ?></label><br>
        <input type="number" id="rpg_current_hp" name="rpg_current_hp" value="<?php echo esc_attr($current_hp); ?>" min="0" max="100" class="small-text" />
        <span> / 100 (<?php echo esc_html($health_percentage); ?>%)</span>
    </p>
    <p>
        <strong><?php _e('Status:', 'rpg-suite'); ?></strong> <?php echo esc_html($health_status); ?>
    </p>
    <?php
}

/**
 * Save character meta data
 */
function rpg_suite_save_character_meta($post_id) {
    global $rpg_suite_health_manager;
    
    // Initialize health for new characters (works for WP-CLI and regular creation)
    $rpg_suite_health_manager->init_character_health($post_id);
    
    if (!isset($_POST['rpg_suite_character_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['rpg_suite_character_nonce'], 'rpg_suite_save_character')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save character data
    if (isset($_POST['rpg_class'])) {
        update_post_meta($post_id, '_rpg_class', sanitize_text_field($_POST['rpg_class']));
    }
    
    // Handle active character
    $is_active = isset($_POST['rpg_active']) ? '1' : '';
    if ($is_active) {
        // Deactivate other characters for this user
        rpg_suite_deactivate_user_characters($post_id);
    }
    update_post_meta($post_id, '_rpg_active', $is_active);
    
    // Save attributes
    foreach (array('fortitude', 'precision', 'intellect', 'charisma') as $attr) {
        if (isset($_POST['rpg_' . $attr])) {
            $value = intval($_POST['rpg_' . $attr]);
            $value = max(1, min(5, $value)); // Ensure value is between 1 and 5
            update_post_meta($post_id, '_rpg_' . $attr, $value);
        }
    }
    
    // Save health
    if (isset($_POST['rpg_current_hp'])) {
        global $rpg_suite_health_manager;
        $rpg_suite_health_manager->set_current_health($post_id, intval($_POST['rpg_current_hp']));
    }
    
    // Clear caches after saving
    rpg_suite_clear_character_caches($post_id);
}
add_action('save_post_rpg_character', 'rpg_suite_save_character_meta');

/**
 * Deactivate all characters for a user except the current one
 */
function rpg_suite_deactivate_user_characters($current_post_id) {
    $current_post = get_post($current_post_id);
    if (!$current_post) {
        return;
    }
    
    $user_id = $current_post->post_author;
    
    $args = array(
        'post_type' => 'rpg_character',
        'author' => $user_id,
        'posts_per_page' => -1,
        'post__not_in' => array($current_post_id),
    );
    
    $characters = get_posts($args);
    foreach ($characters as $character) {
        update_post_meta($character->ID, '_rpg_active', '');
    }
}

/**
 * Get active character for a user
 */
function rpg_suite_get_active_character($user_id) {
    // Try cache first
    $cache_key = 'rpg_active_character_' . $user_id;
    $cached = wp_cache_get($cache_key, 'rpg_suite');
    
    if ($cached !== false) {
        return $cached;
    }
    
    $args = array(
        'post_type' => 'rpg_character',
        'author' => $user_id,
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_rpg_active',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    
    $characters = get_posts($args);
    $character = !empty($characters) ? $characters[0] : null;
    
    // Cache the result
    wp_cache_set($cache_key, $character, 'rpg_suite', 300); // 5 minutes
    
    return $character;
}

/**
 * Register REST API routes
 */
function rpg_suite_register_rest_routes() {
    // Get user's characters
    register_rest_route('rpg-suite/v1', '/users/(?P<id>\d+)/characters', array(
        'methods' => 'GET',
        'callback' => 'rpg_suite_rest_get_user_characters',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
    
    // Switch active character
    register_rest_route('rpg-suite/v1', '/characters/switch', array(
        'methods' => 'POST',
        'callback' => 'rpg_suite_rest_switch_character',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
        'args' => array(
            'character_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));
}

/**
 * REST callback: Get user's characters
 */
function rpg_suite_rest_get_user_characters($request) {
    $user_id = $request['id'];
    
    $args = array(
        'post_type' => 'rpg_character',
        'author' => $user_id,
        'posts_per_page' => -1,
    );
    
    $characters = get_posts($args);
    $result = array();
    
    foreach ($characters as $character) {
        global $rpg_suite_health_manager;
        
        $result[] = array(
            'id' => $character->ID,
            'title' => $character->post_title,
            'active' => get_post_meta($character->ID, '_rpg_active', true) === '1',
            'class' => get_post_meta($character->ID, '_rpg_class', true),
            'attributes' => array(
                'fortitude' => intval(get_post_meta($character->ID, '_rpg_fortitude', true)),
                'precision' => intval(get_post_meta($character->ID, '_rpg_precision', true)),
                'intellect' => intval(get_post_meta($character->ID, '_rpg_intellect', true)),
                'charisma' => intval(get_post_meta($character->ID, '_rpg_charisma', true)),
            ),
            'health' => array(
                'current' => $rpg_suite_health_manager->get_current_health($character->ID),
                'max' => RPG_Suite_Health_Manager::MAX_HP,
                'percentage' => $rpg_suite_health_manager->get_health_percentage($character->ID),
                'status' => $rpg_suite_health_manager->get_health_status($character->ID),
            ),
        );
    }
    
    return new WP_REST_Response($result, 200);
}

/**
 * REST callback: Switch active character
 */
function rpg_suite_rest_switch_character($request) {
    $character_id = $request['character_id'];
    $character = get_post($character_id);
    
    if (!$character || $character->post_type !== 'rpg_character') {
        return new WP_Error('invalid_character', 'Invalid character ID', array('status' => 400));
    }
    
    if ($character->post_author != get_current_user_id()) {
        return new WP_Error('unauthorized', 'You do not own this character', array('status' => 403));
    }
    
    rpg_suite_log("Switching active character to ID: $character_id", 'API');
    
    // Deactivate other characters
    rpg_suite_deactivate_user_characters($character_id);
    
    // Activate this character
    update_post_meta($character_id, '_rpg_active', '1');
    
    // Clear caches
    rpg_suite_clear_character_caches($character_id);
    
    rpg_suite_log("Character switch complete for ID: $character_id", 'API');
    
    return new WP_REST_Response(array('success' => true), 200);
}

/**
 * BuddyPress integration - Display character on profile
 */
function rpg_suite_buddypress_profile_display() {
    if (!function_exists('bp_displayed_user_id')) {
        return;
    }
    
    $user_id = bp_displayed_user_id();
    rpg_suite_log("BuddyPress display called for user ID: $user_id", 'DEBUG');
    
    // Get active character
    $character = rpg_suite_get_active_character($user_id);
    
    if ($character) {
        rpg_suite_log("Active character found: {$character->post_title} (ID: {$character->ID})", 'DEBUG');
        
        // PHP fallback display
        global $rpg_suite_health_manager;
        
        $class = get_post_meta($character->ID, '_rpg_class', true);
        $fortitude = get_post_meta($character->ID, '_rpg_fortitude', true);
        $precision = get_post_meta($character->ID, '_rpg_precision', true);
        $intellect = get_post_meta($character->ID, '_rpg_intellect', true);
        $charisma = get_post_meta($character->ID, '_rpg_charisma', true);
        
        // Get health data
        $current_hp = $rpg_suite_health_manager->get_current_health($character->ID);
        $health_percentage = $rpg_suite_health_manager->get_health_percentage($character->ID);
        $health_status = $rpg_suite_health_manager->get_health_status($character->ID);
        ?>
        <!-- PHP Fallback Display -->
        <div class="rpg-character-display rpg-php-fallback">
            <h3><?php echo esc_html($character->post_title); ?></h3>
            <?php if ($class): ?>
                <p><strong><?php _e('Class:', 'rpg-suite'); ?></strong> <?php echo esc_html($class); ?></p>
            <?php endif; ?>
            <div class="rpg-attributes">
                <p><strong><?php _e('Fortitude:', 'rpg-suite'); ?></strong> <?php echo esc_html($fortitude); ?></p>
                <p><strong><?php _e('Precision:', 'rpg-suite'); ?></strong> <?php echo esc_html($precision); ?></p>
                <p><strong><?php _e('Intellect:', 'rpg-suite'); ?></strong> <?php echo esc_html($intellect); ?></p>
                <p><strong><?php _e('Charisma:', 'rpg-suite'); ?></strong> <?php echo esc_html($charisma); ?></p>
            </div>
            <div class="rpg-health">
                <p><strong><?php _e('Health:', 'rpg-suite'); ?></strong> <?php echo esc_html($current_hp); ?>/<?php echo esc_html(RPG_Suite_Health_Manager::MAX_HP); ?> (<?php echo esc_html($health_percentage); ?>%)</p>
                <p><strong><?php _e('Status:', 'rpg-suite'); ?></strong> <?php echo esc_html($health_status); ?></p>
            </div>
        </div>
        <?php
    } else {
        rpg_suite_log("No active character found for user ID: $user_id", 'DEBUG');
    }
    
    // React mount points (will replace PHP display when loaded)
    ?>
    <div id="rpg-suite-character" data-user-id="<?php echo esc_attr($user_id); ?>" style="display:none;">
        <!-- React will mount here and show this div when ready -->
    </div>
    <div id="rpg-suite-character-switcher">
        <!-- React character switcher will mount here -->
    </div>
    <?php
}
add_action('bp_before_member_header_meta', 'rpg_suite_buddypress_profile_display');

/**
 * Enqueue scripts and styles
 */
function rpg_suite_enqueue_scripts() {
    // Only load on BuddyPress profiles
    if (!function_exists('is_buddypress') || !is_buddypress()) {
        rpg_suite_log("Not enqueueing scripts - not a BuddyPress page", 'DEBUG');
        return;
    }
    
    rpg_suite_log("Enqueueing scripts on BuddyPress page", 'DEBUG');
    
    // Ensure WordPress React packages are loaded
    wp_enqueue_script('wp-element');    // WordPress's React
    wp_enqueue_script('wp-api-fetch');  // WordPress's fetch wrapper
    wp_enqueue_script('wp-data');       // WordPress's data layer
    
    // Enqueue React app if it exists
    $react_file = RPG_SUITE_PLUGIN_DIR . 'react-app/build/index.js';
    if (file_exists($react_file)) {
        $asset_file = include(RPG_SUITE_PLUGIN_DIR . 'react-app/build/index.asset.php');
        
        // Add our dependencies to WordPress's
        $dependencies = array_merge(
            $asset_file['dependencies'],
            array('wp-element', 'wp-api-fetch', 'wp-data')
        );
        
        wp_enqueue_script(
            'rpg-suite-react',
            RPG_SUITE_PLUGIN_URL . 'react-app/build/index.js',
            $dependencies,
            $asset_file['version'],
            true
        );
        
        // Localize script data
        wp_localize_script('rpg-suite-react', 'rpgSuiteData', array(
            'api' => array(
                'root' => rest_url('rpg-suite/v1'),
                'wpRoot' => rest_url('wp/v2'),
                'nonce' => wp_create_nonce('wp_rest'),
            ),
            'currentUser' => get_current_user_id(),
        ));
        
        // Enqueue any generated styles
        if (file_exists(RPG_SUITE_PLUGIN_DIR . 'react-app/build/style-index.css')) {
            wp_enqueue_style(
                'rpg-suite-react-style',
                RPG_SUITE_PLUGIN_URL . 'react-app/build/style-index.css',
                array(),
                $asset_file['version']
            );
        }
    }
    
    // Enqueue basic styles
    wp_enqueue_style(
        'rpg-suite-styles',
        RPG_SUITE_PLUGIN_URL . 'assets/styles.css',
        array(),
        RPG_SUITE_VERSION
    );
}
add_action('wp_enqueue_scripts', 'rpg_suite_enqueue_scripts');

/**
 * Activation hook
 */
function rpg_suite_activate() {
    rpg_suite_register_post_type();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'rpg_suite_activate');

/**
 * Deactivation hook
 */
function rpg_suite_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'rpg_suite_deactivate');