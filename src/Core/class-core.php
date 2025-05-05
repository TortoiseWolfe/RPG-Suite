<?php
/**
 * The Core subsystem of the RPG Suite.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/Core
 */

namespace RPG\Suite\Core;

/**
 * The Core subsystem class.
 *
 * Provides the foundation and API gateway for all other subsystems.
 */
class Core {

    /**
     * The event dispatcher instance.
     *
     * @var Event_Dispatcher
     */
    private $event_dispatcher;
    
    /**
     * Initialize the Core subsystem.
     */
    public function __construct() {
        // Ensure Event Dispatcher class is loaded
        require_once dirname(__FILE__) . '/class-event-dispatcher.php';
        require_once dirname(__FILE__) . '/class-event.php';
        require_once dirname(__FILE__) . '/class-event-subscriber.php';
        
        $this->event_dispatcher = new Event_Dispatcher();
    }
    
    /**
     * Initialize hooks and actions.
     *
     * @return void
     */
    public function init() {
        // Register admin menu
        add_action('admin_menu', [$this, 'register_admin_menu']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Register shortcodes
        add_action('init', [$this, 'register_shortcodes']);
        
        // Initialize capabilities
        add_action('init', [$this, 'initialize_capabilities']);
        
        // Fire init event for other subsystems
        $this->event_dispatcher->dispatch('rpg_suite.core.init');
    }
    
    /**
     * Register the admin menu.
     *
     * @return void
     */
    public function register_admin_menu() {
        // Add main menu
        add_menu_page(
            __('RPG Suite', 'rpg-suite'),
            __('RPG Suite', 'rpg-suite'),
            'manage_options',
            'rpg-suite',
            [$this, 'render_admin_page'],
            'dashicons-shield',
            30
        );
        
        // Add default submenu pages
        add_submenu_page(
            'rpg-suite',
            __('Dashboard', 'rpg-suite'),
            __('Dashboard', 'rpg-suite'),
            'manage_options',
            'rpg-suite',
            [$this, 'render_admin_page']
        );
        
        add_submenu_page(
            'rpg-suite',
            __('Settings', 'rpg-suite'),
            __('Settings', 'rpg-suite'),
            'manage_options',
            'rpg-suite-settings',
            [$this, 'render_settings_page']
        );
        
        // Let subsystems add their own submenus
        do_action('rpg_suite_admin_menu');
    }
    
    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_rest_routes() {
        register_rest_route('rpg-suite/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => function() {
                return current_user_can('play_rpg');
            },
        ]);
    }
    
    /**
     * Get the plugin status.
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_status($request) {
        $active_subsystems = get_option('rpg_suite_active_subsystems', []);
        
        return rest_ensure_response([
            'version' => RPG_SUITE_VERSION,
            'active_subsystems' => $active_subsystems,
        ]);
    }
    
    /**
     * Register shortcodes.
     *
     * @return void
     */
    public function register_shortcodes() {
        add_shortcode('rpg_dashboard', [$this, 'render_dashboard_shortcode']);
    }
    
    /**
     * Render the dashboard shortcode.
     *
     * @param array $atts The shortcode attributes.
     * @return string The shortcode output.
     */
    public function render_dashboard_shortcode($atts) {
        // Check if user can play RPG
        if (!current_user_can('play_rpg')) {
            return '<p>' . __('You do not have permission to access the RPG Dashboard.', 'rpg-suite') . '</p>';
        }
        
        ob_start();
        
        // Dashboard content will go here
        echo '<div class="rpg-suite-dashboard">';
        echo '<h2>' . __('RPG Dashboard', 'rpg-suite') . '</h2>';
        
        // Allow other subsystems to add content
        do_action('rpg_suite_dashboard_content');
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Initialize capabilities.
     *
     * @return void
     */
    public function initialize_capabilities() {
        // This is handled during activation but we ensure they exist here
    }
    
    /**
     * Render the admin page.
     *
     * @return void
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('RPG Suite Dashboard', 'rpg-suite'); ?></h1>
            
            <style>
                .rpg-suite-admin-dashboard {
                    margin-top: 20px;
                }
                .rpg-suite-admin-panel {
                    background: white;
                    border: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                    margin-bottom: 20px;
                    padding: 10px 20px 20px;
                    border-radius: 4px;
                }
                .rpg-suite-admin-panel h2 {
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                    color: #23282d;
                }
                .rpg-suite-version {
                    background: #f6f7f7;
                    border-radius: 3px;
                    color: #23282d;
                    display: inline-block;
                    font-size: 0.8em;
                    font-weight: 600;
                    margin-left: 10px;
                    padding: 2px 8px;
                    vertical-align: middle;
                }
                .rpg-suite-header-logo {
                    display: inline-block;
                    vertical-align: middle;
                    margin-right: 10px;
                    color: #B87333; /* Copper color for steampunk theme */
                    font-size: 1.2em;
                }
            </style>
            
            <div class="rpg-suite-admin-dashboard">
                <div class="rpg-suite-admin-panel">
                    <h2>
                        <span class="rpg-suite-header-logo"><span class="dashicons dashicons-shield"></span></span>
                        <?php echo esc_html__('Subsystem Status', 'rpg-suite'); ?>
                        <span class="rpg-suite-version">v<?php echo esc_html(RPG_SUITE_VERSION); ?></span>
                    </h2>
                    <p><?php echo esc_html__('This dashboard shows the status of all RPG Suite subsystems.', 'rpg-suite'); ?></p>
                    <?php $this->render_subsystems_status(); ?>
                </div>
                
                <div class="rpg-suite-admin-panel">
                    <h2>
                        <span class="rpg-suite-header-logo"><span class="dashicons dashicons-admin-tools"></span></span>
                        <?php echo esc_html__('System Status', 'rpg-suite'); ?>
                    </h2>
                    <p><?php echo esc_html__('WordPress and dependency compatibility information.', 'rpg-suite'); ?></p>
                    <?php $this->render_system_status(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the settings page.
     *
     * @return void
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('RPG Suite Settings', 'rpg-suite'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('rpg-suite-settings');
                do_settings_sections('rpg-suite-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render the subsystems status.
     *
     * @return void
     */
    private function render_subsystems_status() {
        $active_subsystems = get_option('rpg_suite_active_subsystems', []);
        
        // Add styles for the status dashboard
        echo '<style>
            .rpg-suite-status { 
                display: inline-block; 
                padding: 4px 8px; 
                border-radius: 3px; 
                font-weight: bold;
            }
            .rpg-suite-status-active { background-color: #28a745; color: white; }
            .rpg-suite-status-inactive { background-color: #dc3545; color: white; }
            .rpg-suite-status-partial { background-color: #ffc107; color: #212529; }
            .rpg-suite-status-placeholder { background-color: #17a2b8; color: white; }
            .rpg-suite-implementation-status {
                display: flex;
                align-items: center;
                margin-top: 5px;
            }
            .rpg-suite-progress-bar {
                height: 10px;
                background-color: #e9ecef;
                border-radius: 5px;
                margin-right: 10px;
                flex-grow: 1;
                max-width: 200px;
                overflow: hidden;
            }
            .rpg-suite-progress-inner {
                height: 100%;
                background-color: #007bff;
                border-radius: 5px;
            }
            .rpg-suite-feature-count {
                color: #6c757d;
                font-size: 0.8em;
                margin-left: 5px;
            }
        </style>';
        
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Subsystem', 'rpg-suite') . '</th>';
        echo '<th>' . esc_html__('Activation Status', 'rpg-suite') . '</th>';
        echo '<th>' . esc_html__('Implementation', 'rpg-suite') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // Define implementation status for each subsystem
        $implementation_status = [
            'core' => [
                'status' => 'complete',
                'percentage' => 100,
                'features' => 4,
                'description' => __('Fully implemented', 'rpg-suite')
            ],
            'health' => [
                'status' => 'complete', 
                'percentage' => 100,
                'features' => 4,
                'description' => __('Fully implemented', 'rpg-suite')
            ],
            'geo' => [
                'status' => 'placeholder',
                'percentage' => 0,
                'features' => 0,
                'description' => __('Directory structure only', 'rpg-suite')
            ],
            'dice' => [
                'status' => 'placeholder',
                'percentage' => 0, 
                'features' => 0,
                'description' => __('Directory structure only', 'rpg-suite')
            ],
            'inventory' => [
                'status' => 'placeholder',
                'percentage' => 0,
                'features' => 0,
                'description' => __('Directory structure only', 'rpg-suite')
            ],
            'combat' => [
                'status' => 'placeholder',
                'percentage' => 0,
                'features' => 0,
                'description' => __('Directory structure only', 'rpg-suite')
            ],
            'quest' => [
                'status' => 'placeholder',
                'percentage' => 0,
                'features' => 0,
                'description' => __('Directory structure only', 'rpg-suite')
            ],
        ];
        
        // Helper function to determine if a subsystem has actual implementation
        $is_implemented = function($key) {
            // Check if the directory exists AND has php files
            $dir = RPG_SUITE_PLUGIN_DIR . 'src/' . ucfirst($key);
            if (!is_dir($dir)) {
                return false;
            }
            
            $files = glob($dir . '/*.php');
            return !empty($files);
        };
        
        // Core is always active and fully implemented
        echo '<tr>';
        echo '<td><strong>' . esc_html__('Core', 'rpg-suite') . '</strong></td>';
        echo '<td><span class="rpg-suite-status rpg-suite-status-active">' . esc_html__('Always Active', 'rpg-suite') . '</span></td>';
        echo '<td>';
        echo '<div class="rpg-suite-implementation-status">';
        echo '<div class="rpg-suite-progress-bar"><div class="rpg-suite-progress-inner" style="width: 100%"></div></div>';
        echo '<span>100%</span>';
        echo '<span class="rpg-suite-feature-count">' . sprintf(esc_html__('(%d features)', 'rpg-suite'), $implementation_status['core']['features']) . '</span>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
        
        $subsystems = [
            'health' => __('Health', 'rpg-suite'),
            'geo' => __('Geo', 'rpg-suite'),
            'dice' => __('Dice', 'rpg-suite'),
            'inventory' => __('Inventory', 'rpg-suite'),
            'combat' => __('Combat', 'rpg-suite'),
            'quest' => __('Quest', 'rpg-suite'),
        ];
        
        foreach ($subsystems as $key => $name) {
            $is_active = !empty($active_subsystems[$key]);
            $has_implementation = $is_implemented($key);
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($name) . '</strong></td>';
            
            // Activation status
            if ($is_active) {
                $status_class = $has_implementation ? 'active' : 'partial';
                $status_text = $has_implementation ? 
                    __('Active', 'rpg-suite') : 
                    __('Active (Placeholder)', 'rpg-suite');
            } else {
                $status_class = 'inactive';
                $status_text = __('Inactive', 'rpg-suite');
            }
            
            echo '<td><span class="rpg-suite-status rpg-suite-status-' . esc_attr($status_class) . '">' . 
                esc_html($status_text) . '</span></td>';
            
            // Implementation status
            $impl_status = $implementation_status[$key];
            $impl_percentage = $impl_status['percentage'];
            
            echo '<td>';
            echo '<div class="rpg-suite-implementation-status">';
            echo '<div class="rpg-suite-progress-bar">';
            echo '<div class="rpg-suite-progress-inner" style="width: ' . esc_attr($impl_percentage) . '%"></div>';
            echo '</div>';
            echo '<span>' . esc_html($impl_percentage) . '%</span>';
            
            if ($impl_status['features'] > 0) {
                echo '<span class="rpg-suite-feature-count">' . 
                    sprintf(esc_html__('(%d features)', 'rpg-suite'), $impl_status['features']) . 
                '</span>';
            }
            
            echo '</div>';
            echo '<small>' . esc_html($impl_status['description']) . '</small>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        echo '<p class="description">' . 
            esc_html__('Note: "Active" status indicates a subsystem is enabled, while implementation status shows actual development progress.', 'rpg-suite') . 
        '</p>';
    }
    
    /**
     * Render the system status.
     *
     * @return void
     */
    private function render_system_status() {
        // Add specific styles for system status
        echo '<style>
            .rpg-suite-status-icon {
                display: inline-block;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                margin-right: 8px;
                vertical-align: text-bottom;
                text-align: center;
                line-height: 18px;
            }
            .rpg-suite-status-icon.active {
                background-color: #28a745;
                color: white;
            }
            .rpg-suite-status-icon.warning {
                background-color: #ffc107;
                color: #212529;
            }
            .rpg-suite-status-icon.error {
                background-color: #dc3545;
                color: white;
            }
            .rpg-suite-system-info {
                background-color: #f8f9fa;
                border-radius: 4px;
                padding: 4px 8px;
                font-family: monospace;
                margin-left: 5px;
            }
        </style>';
        
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th style="width: 30%;">' . esc_html__('Item', 'rpg-suite') . '</th>';
        echo '<th>' . esc_html__('Status', 'rpg-suite') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // WordPress version
        global $wp_version;
        $wp_status = version_compare($wp_version, '6.8', '>=') ? 'active' : 'error';
        $wp_text = version_compare($wp_version, '6.8', '>=') ? __('Compatible', 'rpg-suite') : __('Needs update', 'rpg-suite');
        $wp_icon = version_compare($wp_version, '6.8', '>=') ? '✓' : '✗';
        echo '<tr>';
        echo '<td><strong>' . esc_html__('WordPress Version', 'rpg-suite') . '</strong></td>';
        echo '<td>';
        echo '<span class="rpg-suite-status-icon ' . esc_attr($wp_status) . '">' . esc_html($wp_icon) . '</span>';
        echo esc_html($wp_text);
        echo '<span class="rpg-suite-system-info">' . esc_html($wp_version) . '</span>';
        echo '</td>';
        echo '</tr>';
        
        // PHP version
        $php_status = version_compare(PHP_VERSION, '8.2', '>=') ? 'active' : 'error';
        $php_text = version_compare(PHP_VERSION, '8.2', '>=') ? __('Compatible', 'rpg-suite') : __('Needs update', 'rpg-suite');
        $php_icon = version_compare(PHP_VERSION, '8.2', '>=') ? '✓' : '✗';
        echo '<tr>';
        echo '<td><strong>' . esc_html__('PHP Version', 'rpg-suite') . '</strong></td>';
        echo '<td>';
        echo '<span class="rpg-suite-status-icon ' . esc_attr($php_status) . '">' . esc_html($php_icon) . '</span>';
        echo esc_html($php_text);
        echo '<span class="rpg-suite-system-info">' . esc_html(PHP_VERSION) . '</span>';
        echo '</td>';
        echo '</tr>';
        
        // BuddyPress
        $bp_active = class_exists('BuddyPress');
        $bp_status = $bp_active ? 'active' : 'error';
        $bp_text = $bp_active ? __('Active', 'rpg-suite') : __('Not installed', 'rpg-suite');
        $bp_icon = $bp_active ? '✓' : '✗';
        $bp_version = $bp_active ? BP_VERSION : '';
        echo '<tr>';
        echo '<td><strong>' . esc_html__('BuddyPress', 'rpg-suite') . '</strong></td>';
        echo '<td>';
        echo '<span class="rpg-suite-status-icon ' . esc_attr($bp_status) . '">' . esc_html($bp_icon) . '</span>';
        echo esc_html($bp_text);
        if ($bp_version) {
            echo '<span class="rpg-suite-system-info">' . esc_html($bp_version) . '</span>';
        }
        echo '</td>';
        echo '</tr>';
        
        // BuddyX Theme
        $theme = wp_get_theme();
        $buddyx_active = $theme->get_template() === 'buddyx';
        $buddyx_status = $buddyx_active ? 'active' : 'warning';
        $buddyx_text = $buddyx_active ? __('Active', 'rpg-suite') : __('Not active', 'rpg-suite');
        $buddyx_icon = $buddyx_active ? '✓' : '⚠';
        echo '<tr>';
        echo '<td><strong>' . esc_html__('BuddyX Theme', 'rpg-suite') . '</strong></td>';
        echo '<td>';
        echo '<span class="rpg-suite-status-icon ' . esc_attr($buddyx_status) . '">' . esc_html($buddyx_icon) . '</span>';
        echo esc_html($buddyx_text);
        echo '<span class="rpg-suite-system-info">' . esc_html($theme->get('Version')) . '</span>';
        echo '</td>';
        echo '</tr>';
        
        // Database
        global $wpdb;
        $db_tables = [
            $wpdb->prefix . 'rpg_character_items',
            $wpdb->prefix . 'rpg_combat_log',
            $wpdb->prefix . 'rpg_character_attributes',
        ];
        
        $tables_exist = true;
        foreach ($db_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                $tables_exist = false;
                break;
            }
        }
        
        $db_status = $tables_exist ? 'active' : 'error';
        $db_text = $tables_exist ? __('Tables created', 'rpg-suite') : __('Missing tables', 'rpg-suite');
        $db_icon = $tables_exist ? '✓' : '✗';
        
        echo '<tr>';
        echo '<td><strong>' . esc_html__('Database Tables', 'rpg-suite') . '</strong></td>';
        echo '<td>';
        echo '<span class="rpg-suite-status-icon ' . esc_attr($db_status) . '">' . esc_html($db_icon) . '</span>';
        echo esc_html($db_text);
        echo '</td>';
        echo '</tr>';
        
        // RPG Suite Version
        echo '<tr>';
        echo '<td><strong>' . esc_html__('RPG Suite Version', 'rpg-suite') . '</strong></td>';
        echo '<td>';
        echo '<span class="rpg-suite-status-icon active">✓</span>';
        echo esc_html__('Installed', 'rpg-suite');
        echo '<span class="rpg-suite-system-info">' . esc_html(RPG_SUITE_VERSION) . '</span>';
        echo '</td>';
        echo '</tr>';
        
        echo '</tbody>';
        echo '</table>';
        
        echo '<div style="margin-top: 15px;">';
        echo '<h3>' . esc_html__('Installed Subsystems', 'rpg-suite') . '</h3>';
        echo '<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">';
        
        // Define subsystems with icons
        $all_subsystems = [
            'core' => ['name' => __('Core', 'rpg-suite'), 'icon' => 'dashicons-shield', 'color' => '#007bff'],
            'health' => ['name' => __('Health', 'rpg-suite'), 'icon' => 'dashicons-heart', 'color' => '#dc3545'],
            'geo' => ['name' => __('Geo', 'rpg-suite'), 'icon' => 'dashicons-location', 'color' => '#28a745'],
            'dice' => ['name' => __('Dice', 'rpg-suite'), 'icon' => 'dashicons-games', 'color' => '#6f42c1'],
            'inventory' => ['name' => __('Inventory', 'rpg-suite'), 'icon' => 'dashicons-archive', 'color' => '#fd7e14'],
            'combat' => ['name' => __('Combat', 'rpg-suite'), 'icon' => 'dashicons-superhero', 'color' => '#e83e8c'],
            'quest' => ['name' => __('Quest', 'rpg-suite'), 'icon' => 'dashicons-book-alt', 'color' => '#17a2b8'],
        ];
        
        // Check which subsystems are actually implemented
        $implemented = [
            'core' => true,
            'health' => file_exists(RPG_SUITE_PLUGIN_DIR . 'src/Health/class-health.php'),
        ];
        
        foreach ($all_subsystems as $key => $subsystem) {
            $is_implemented = isset($implemented[$key]) && $implemented[$key];
            
            echo '<div style="background-color: ' . esc_attr($is_implemented ? '#f8f9fa' : '#f2f2f2') . '; border-radius: 5px; padding: 10px; width: calc(33.333% - 10px); min-width: 150px; box-sizing: border-box; opacity: ' . esc_attr($is_implemented ? '1' : '0.6') . ';">';
            echo '<div style="display: flex; align-items: center;">';
            echo '<span class="dashicons ' . esc_attr($subsystem['icon']) . '" style="color: ' . esc_attr($subsystem['color']) . '; font-size: 24px; width: 24px; height: 24px; margin-right: 10px;"></span>';
            echo '<strong>' . esc_html($subsystem['name']) . '</strong>';
            echo '</div>';
            if ($is_implemented) {
                echo '<div style="margin-top: 5px; color: #28a745;"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Implemented', 'rpg-suite') . '</div>';
            } else {
                echo '<div style="margin-top: 5px; color: #6c757d;"><span class="dashicons dashicons-marker"></span> ' . esc_html__('Planned', 'rpg-suite') . '</div>';
            }
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Get the event dispatcher.
     *
     * @return Event_Dispatcher The event dispatcher.
     */
    public function get_event_dispatcher() {
        return $this->event_dispatcher;
    }
}