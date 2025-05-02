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
        add_menu_page(
            __('RPG Suite', 'rpg-suite'),
            __('RPG Suite', 'rpg-suite'),
            'manage_options',
            'rpg-suite',
            [$this, 'render_admin_page'],
            'dashicons-shield',
            30
        );
        
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
            
            <div class="rpg-suite-admin-dashboard">
                <div class="rpg-suite-admin-panel">
                    <h2><?php echo esc_html__('Active Subsystems', 'rpg-suite'); ?></h2>
                    <?php $this->render_subsystems_status(); ?>
                </div>
                
                <div class="rpg-suite-admin-panel">
                    <h2><?php echo esc_html__('System Status', 'rpg-suite'); ?></h2>
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
        
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Subsystem', 'rpg-suite') . '</th>';
        echo '<th>' . esc_html__('Status', 'rpg-suite') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        echo '<tr>';
        echo '<td>' . esc_html__('Core', 'rpg-suite') . '</td>';
        echo '<td><span class="rpg-suite-status rpg-suite-status-active">' . esc_html__('Always Active', 'rpg-suite') . '</span></td>';
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
            echo '<tr>';
            echo '<td>' . esc_html($name) . '</td>';
            $status = !empty($active_subsystems[$key]) ? 'active' : 'inactive';
            $status_text = !empty($active_subsystems[$key]) ? __('Active', 'rpg-suite') : __('Inactive', 'rpg-suite');
            echo '<td><span class="rpg-suite-status rpg-suite-status-' . esc_attr($status) . '">' . esc_html($status_text) . '</span></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    /**
     * Render the system status.
     *
     * @return void
     */
    private function render_system_status() {
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Item', 'rpg-suite') . '</th>';
        echo '<th>' . esc_html__('Status', 'rpg-suite') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // WordPress version
        global $wp_version;
        $wp_status = version_compare($wp_version, '6.8', '>=') ? 'active' : 'error';
        $wp_text = version_compare($wp_version, '6.8', '>=') ? __('Compatible', 'rpg-suite') : __('Needs update', 'rpg-suite');
        echo '<tr>';
        echo '<td>' . esc_html__('WordPress Version', 'rpg-suite') . '</td>';
        echo '<td><span class="rpg-suite-status rpg-suite-status-' . esc_attr($wp_status) . '">' . esc_html($wp_version) . ' (' . esc_html($wp_text) . ')</span></td>';
        echo '</tr>';
        
        // PHP version
        $php_status = version_compare(PHP_VERSION, '8.2', '>=') ? 'active' : 'error';
        $php_text = version_compare(PHP_VERSION, '8.2', '>=') ? __('Compatible', 'rpg-suite') : __('Needs update', 'rpg-suite');
        echo '<tr>';
        echo '<td>' . esc_html__('PHP Version', 'rpg-suite') . '</td>';
        echo '<td><span class="rpg-suite-status rpg-suite-status-' . esc_attr($php_status) . '">' . esc_html(PHP_VERSION) . ' (' . esc_html($php_text) . ')</span></td>';
        echo '</tr>';
        
        // BuddyPress
        $bp_active = class_exists('BuddyPress');
        $bp_status = $bp_active ? 'active' : 'error';
        $bp_text = $bp_active ? __('Active', 'rpg-suite') : __('Not installed', 'rpg-suite');
        echo '<tr>';
        echo '<td>' . esc_html__('BuddyPress', 'rpg-suite') . '</td>';
        echo '<td><span class="rpg-suite-status rpg-suite-status-' . esc_attr($bp_status) . '">' . esc_html($bp_text) . '</span></td>';
        echo '</tr>';
        
        // BuddyX Theme
        $theme = wp_get_theme();
        $buddyx_active = $theme->get_template() === 'buddyx';
        $buddyx_status = $buddyx_active ? 'active' : 'warning';
        $buddyx_text = $buddyx_active ? __('Active', 'rpg-suite') : __('Not active', 'rpg-suite');
        echo '<tr>';
        echo '<td>' . esc_html__('BuddyX Theme', 'rpg-suite') . '</td>';
        echo '<td><span class="rpg-suite-status rpg-suite-status-' . esc_attr($buddyx_status) . '">' . esc_html($buddyx_text) . '</span></td>';
        echo '</tr>';
        
        // RPG Suite Version
        echo '<tr>';
        echo '<td>' . esc_html__('RPG Suite Version', 'rpg-suite') . '</td>';
        echo '<td><span class="rpg-suite-status rpg-suite-status-active">' . esc_html(RPG_SUITE_VERSION) . '</span></td>';
        echo '</tr>';
        
        echo '</tbody>';
        echo '</table>';
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