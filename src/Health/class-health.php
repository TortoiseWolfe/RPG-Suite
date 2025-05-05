<?php
/**
 * Health Subsystem for RPG Suite.
 *
 * Manages character health, damage, healing, and related mechanics.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/Health
 */

namespace RPG\Suite\Health;

use RPG\Suite\Core\Event_Subscriber;

/**
 * Health subsystem class.
 *
 * Provides functionality for managing character health points,
 * applying damage, healing, and handling related game mechanics.
 */
class Health implements Event_Subscriber {

    /**
     * Maximum health points setting.
     *
     * @var int
     */
    private $max_health = 100;

    /**
     * Whether the health system is enabled.
     *
     * @var bool
     */
    private $health_enabled = true;

    /**
     * Character attribute key for current health.
     *
     * @var string
     */
    private $health_key = 'current_health';

    /**
     * Character attribute key for maximum health.
     *
     * @var string
     */
    private $max_health_key = 'max_health';

    /**
     * Initialize the health subsystem.
     */
    public function __construct() {
        // Load settings
        $game_settings = get_option('rpg_suite_game_settings', []);
        if (isset($game_settings['health_enabled'])) {
            $this->health_enabled = (bool) $game_settings['health_enabled'];
        }
        if (isset($game_settings['max_health'])) {
            $this->max_health = (int) $game_settings['max_health'];
        }
    }

    /**
     * Initialize hooks and actions.
     *
     * @return void
     */
    public function init() {
        if (!$this->health_enabled) {
            return;
        }

        // Register admin hooks
        add_action('admin_init', [$this, 'register_settings']);
        
        // Register admin menu under RPG Suite
        add_action('rpg_suite_admin_menu', [$this, 'add_admin_menu']);
        
        // Register public hooks
        add_action('rpg_suite_character_created', [$this, 'initialize_character_health']);
    }

    /**
     * Register admin hooks.
     *
     * @return void
     */
    public function register_admin_hooks() {
        // Register admin menu under RPG Suite
        add_action('rpg_suite_admin_menu', [$this, 'add_admin_menu']);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register public-facing hooks.
     *
     * @return void
     */
    public function register_public_hooks() {
        // Register shortcodes
        add_shortcode('rpg_health', [$this, 'render_health_shortcode']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Hook into character creation
        add_action('rpg_suite_character_created', [$this, 'initialize_character_health']);
    }

    /**
     * Add admin menu item.
     *
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'rpg-suite',
            __('Health Settings', 'rpg-suite'),
            __('Health', 'rpg-suite'),
            'manage_options',
            'rpg-suite-health',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Register settings.
     *
     * @return void
     */
    public function register_settings() {
        register_setting('rpg-suite-health', 'rpg_suite_health_settings');
        
        add_settings_section(
            'rpg_suite_health_general',
            __('Health Settings', 'rpg-suite'),
            [$this, 'render_settings_section'],
            'rpg-suite-health'
        );
        
        add_settings_field(
            'health_enabled',
            __('Enable Health System', 'rpg-suite'),
            [$this, 'render_enabled_field'],
            'rpg-suite-health',
            'rpg_suite_health_general'
        );
        
        add_settings_field(
            'max_health',
            __('Maximum Health Points', 'rpg-suite'),
            [$this, 'render_max_health_field'],
            'rpg-suite-health',
            'rpg_suite_health_general'
        );
    }

    /**
     * Render the admin page.
     *
     * @return void
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Health Settings', 'rpg-suite'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('rpg-suite-health');
                do_settings_sections('rpg-suite-health');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render the settings section.
     *
     * @return void
     */
    public function render_settings_section() {
        echo '<p>' . __('Configure the health system settings for your RPG game.', 'rpg-suite') . '</p>';
    }
    
    /**
     * Render the enabled field.
     *
     * @return void
     */
    public function render_enabled_field() {
        $game_settings = get_option('rpg_suite_game_settings', []);
        $enabled = isset($game_settings['health_enabled']) ? $game_settings['health_enabled'] : true;
        ?>
        <label>
            <input type="checkbox" name="rpg_suite_game_settings[health_enabled]" value="1" <?php checked($enabled); ?> />
            <?php _e('Enable the health system for characters', 'rpg-suite'); ?>
        </label>
        <?php
    }
    
    /**
     * Render the max health field.
     *
     * @return void
     */
    public function render_max_health_field() {
        $game_settings = get_option('rpg_suite_game_settings', []);
        $max_health = isset($game_settings['max_health']) ? $game_settings['max_health'] : 100;
        ?>
        <input type="number" name="rpg_suite_game_settings[max_health]" value="<?php echo esc_attr($max_health); ?>" min="1" step="1" />
        <p class="description"><?php _e('The maximum number of health points a character can have', 'rpg-suite'); ?></p>
        <?php
    }
    
    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_rest_routes() {
        register_rest_route('rpg-suite/v1', '/health/(?P<character_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_character_health'],
            'permission_callback' => function($request) {
                return current_user_can('read_rpg_character');
            },
            'args' => [
                'character_id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
        
        register_rest_route('rpg-suite/v1', '/health/(?P<character_id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_character_health'],
            'permission_callback' => function($request) {
                $character_id = $request->get_param('character_id');
                return current_user_can('edit_post', $character_id);
            },
            'args' => [
                'character_id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'health' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
    }
    
    /**
     * Get character health (API endpoint).
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_character_health($request) {
        $character_id = $request->get_param('character_id');
        
        // Verify the character exists
        $character = get_post($character_id);
        if (!$character || $character->post_type !== 'rpg_character') {
            return new \WP_Error('invalid_character', __('Invalid character ID', 'rpg-suite'), ['status' => 404]);
        }
        
        // Get the character's health
        $current_health = $this->get_health($character_id);
        $max_health = $this->get_max_health($character_id);
        
        return rest_ensure_response([
            'character_id' => $character_id,
            'current_health' => $current_health,
            'max_health' => $max_health,
            'health_percentage' => $max_health > 0 ? ($current_health / $max_health) * 100 : 0,
        ]);
    }
    
    /**
     * Update character health (API endpoint).
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_character_health($request) {
        $character_id = $request->get_param('character_id');
        $new_health = $request->get_param('health');
        
        // Verify the character exists
        $character = get_post($character_id);
        if (!$character || $character->post_type !== 'rpg_character') {
            return new \WP_Error('invalid_character', __('Invalid character ID', 'rpg-suite'), ['status' => 404]);
        }
        
        // Update the character's health
        $result = $this->set_health($character_id, $new_health);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Get the updated health values
        $current_health = $this->get_health($character_id);
        $max_health = $this->get_max_health($character_id);
        
        return rest_ensure_response([
            'character_id' => $character_id,
            'current_health' => $current_health,
            'max_health' => $max_health,
            'health_percentage' => $max_health > 0 ? ($current_health / $max_health) * 100 : 0,
        ]);
    }
    
    /**
     * Initialize character health when a character is created.
     *
     * @param int $character_id The character ID.
     * @return void
     */
    public function initialize_character_health($character_id) {
        // Check if health is already set
        $health = $this->get_health($character_id);
        
        if ($health === false) {
            // Set initial health to maximum
            $this->set_health($character_id, $this->max_health);
            $this->set_max_health($character_id, $this->max_health);
        }
    }
    
    /**
     * Get character's current health.
     *
     * @param int $character_id The character ID.
     * @return int|bool The current health, or false if not found.
     */
    public function get_health($character_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rpg_character_attributes';
        $query = $wpdb->prepare(
            "SELECT attribute_value FROM $table_name 
            WHERE character_id = %d AND attribute_key = %s",
            $character_id,
            $this->health_key
        );
        
        $health = $wpdb->get_var($query);
        
        return $health !== null ? (int) $health : false;
    }
    
    /**
     * Set character's current health.
     *
     * @param int $character_id The character ID.
     * @param int $health The new health value.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function set_health($character_id, $health) {
        global $wpdb;
        
        // Get maximum health
        $max_health = $this->get_max_health($character_id);
        
        // Ensure health doesn't exceed maximum
        $health = min((int) $health, $max_health);
        
        // Ensure health doesn't go below 0
        $health = max(0, $health);
        
        $table_name = $wpdb->prefix . 'rpg_character_attributes';
        
        // Check if the attribute already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE character_id = %d AND attribute_key = %s",
                $character_id,
                $this->health_key
            )
        );
        
        if ($exists) {
            // Update existing attribute
            $result = $wpdb->update(
                $table_name,
                ['attribute_value' => $health],
                [
                    'character_id' => $character_id,
                    'attribute_key' => $this->health_key,
                ],
                ['%s'],
                ['%d', '%s']
            );
        } else {
            // Insert new attribute
            $result = $wpdb->insert(
                $table_name,
                [
                    'character_id' => $character_id,
                    'attribute_key' => $this->health_key,
                    'attribute_value' => $health,
                ],
                ['%d', '%s', '%s']
            );
        }
        
        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to update health', 'rpg-suite'));
        }
        
        // Trigger health changed event
        do_action('rpg_suite_health_changed', $character_id, $health);
        
        return true;
    }
    
    /**
     * Get character's maximum health.
     *
     * @param int $character_id The character ID.
     * @return int The maximum health.
     */
    public function get_max_health($character_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rpg_character_attributes';
        $query = $wpdb->prepare(
            "SELECT attribute_value FROM $table_name 
            WHERE character_id = %d AND attribute_key = %s",
            $character_id,
            $this->max_health_key
        );
        
        $max_health = $wpdb->get_var($query);
        
        return $max_health !== null ? (int) $max_health : $this->max_health;
    }
    
    /**
     * Set character's maximum health.
     *
     * @param int $character_id The character ID.
     * @param int $max_health The new maximum health value.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function set_max_health($character_id, $max_health) {
        global $wpdb;
        
        // Ensure max health is at least 1
        $max_health = max(1, (int) $max_health);
        
        $table_name = $wpdb->prefix . 'rpg_character_attributes';
        
        // Check if the attribute already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE character_id = %d AND attribute_key = %s",
                $character_id,
                $this->max_health_key
            )
        );
        
        if ($exists) {
            // Update existing attribute
            $result = $wpdb->update(
                $table_name,
                ['attribute_value' => $max_health],
                [
                    'character_id' => $character_id,
                    'attribute_key' => $this->max_health_key,
                ],
                ['%s'],
                ['%d', '%s']
            );
        } else {
            // Insert new attribute
            $result = $wpdb->insert(
                $table_name,
                [
                    'character_id' => $character_id,
                    'attribute_key' => $this->max_health_key,
                    'attribute_value' => $max_health,
                ],
                ['%d', '%s', '%s']
            );
        }
        
        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to update maximum health', 'rpg-suite'));
        }
        
        // Also adjust current health if it exceeds the new maximum
        $current_health = $this->get_health($character_id);
        if ($current_health > $max_health) {
            $this->set_health($character_id, $max_health);
        }
        
        return true;
    }
    
    /**
     * Apply damage to a character.
     *
     * @param int $character_id The character ID.
     * @param int $amount The amount of damage to apply.
     * @return int The new health value.
     */
    public function apply_damage($character_id, $amount) {
        $current_health = $this->get_health($character_id);
        $new_health = max(0, $current_health - abs($amount));
        
        $this->set_health($character_id, $new_health);
        
        // Trigger damage event
        do_action('rpg_suite_damage_applied', $character_id, $amount, $new_health);
        
        // Check if character is defeated
        if ($new_health <= 0) {
            do_action('rpg_suite_character_defeated', $character_id);
        }
        
        return $new_health;
    }
    
    /**
     * Heal a character.
     *
     * @param int $character_id The character ID.
     * @param int $amount The amount of healing to apply.
     * @return int The new health value.
     */
    public function apply_healing($character_id, $amount) {
        $current_health = $this->get_health($character_id);
        $max_health = $this->get_max_health($character_id);
        $new_health = min($max_health, $current_health + abs($amount));
        
        $this->set_health($character_id, $new_health);
        
        // Trigger healing event
        do_action('rpg_suite_healing_applied', $character_id, $amount, $new_health);
        
        return $new_health;
    }
    
    /**
     * Render health bar shortcode.
     *
     * @param array $atts The shortcode attributes.
     * @return string The shortcode output.
     */
    public function render_health_shortcode($atts) {
        $atts = shortcode_atts([
            'character_id' => 0,
            'show_text' => true,
            'show_percentage' => true,
            'width' => '100%',
        ], $atts, 'rpg_health');
        
        $character_id = (int) $atts['character_id'];
        
        // If no character ID was provided, try to get the current character
        if ($character_id === 0) {
            if (function_exists('rpg_suite_get_active_character')) {
                $character = rpg_suite_get_active_character();
                if ($character) {
                    $character_id = $character->ID;
                }
            }
        }
        
        if ($character_id === 0) {
            return '<p class="rpg-suite-error">' . __('No character specified for health display', 'rpg-suite') . '</p>';
        }
        
        $current_health = $this->get_health($character_id);
        $max_health = $this->get_max_health($character_id);
        
        if ($current_health === false) {
            return '<p class="rpg-suite-error">' . __('Character has no health value', 'rpg-suite') . '</p>';
        }
        
        $percentage = $max_health > 0 ? ($current_health / $max_health) * 100 : 0;
        
        // Determine health status color
        $color = '#28a745'; // Green for healthy
        if ($percentage <= 25) {
            $color = '#dc3545'; // Red for critical
        } elseif ($percentage <= 50) {
            $color = '#ffc107'; // Yellow for warning
        }
        
        // Build the health bar HTML
        $output = '<div class="rpg-suite-health-bar-container" style="width: ' . esc_attr($atts['width']) . '; max-width: 100%; background-color: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; height: 20px; overflow: hidden;">';
        $output .= '<div class="rpg-suite-health-bar" style="width: ' . esc_attr($percentage) . '%; height: 100%; background-color: ' . esc_attr($color) . ';"></div>';
        $output .= '</div>';
        
        // Add text display if enabled
        if ($atts['show_text'] || $atts['show_percentage']) {
            $output .= '<div class="rpg-suite-health-text" style="margin-top: 5px; font-size: 14px;">';
            
            if ($atts['show_text']) {
                $output .= '<span class="rpg-suite-health-value">' . esc_html($current_health) . '/' . esc_html($max_health) . '</span>';
            }
            
            if ($atts['show_text'] && $atts['show_percentage']) {
                $output .= ' - ';
            }
            
            if ($atts['show_percentage']) {
                $output .= '<span class="rpg-suite-health-percentage">' . esc_html(round($percentage)) . '%</span>';
            }
            
            $output .= '</div>';
        }
        
        return '<div class="rpg-suite-health-display">' . $output . '</div>';
    }
    
    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array The events and methods to call.
     */
    public function get_subscribed_events() {
        return [
            'rpg_suite.core.init' => 'init',
            'rpg_suite_character_created' => 'initialize_character_health',
        ];
    }
}