<?php
/**
 * BuddyPress Integration
 *
 * Handles integration with BuddyPress for profile display and character management.
 *
 * @package    RPG_Suite
 * @subpackage BuddyPress
 * @since      0.1.0
 */

/**
 * BuddyPress Integration Class
 *
 * Integrates with BuddyPress to display character information on user profiles
 * and provide character management functionality.
 */
class RPG_Suite_BuddyPress_Integration {

    /**
     * Character manager instance
     *
     * @var RPG_Suite_Character_Manager
     */
    private $character_manager;

    /**
     * Event dispatcher instance (for future use)
     *
     * @var RPG_Suite_Event_Dispatcher
     */
    private $event_dispatcher;

    /**
     * Whether BuddyX theme is active
     *
     * @var bool
     */
    private $is_buddyx_theme;

    /**
     * Constructor
     *
     * @param RPG_Suite_Character_Manager $character_manager Character manager instance
     * @param RPG_Suite_Event_Dispatcher  $event_dispatcher  Event dispatcher instance (optional)
     */
    public function __construct($character_manager, $event_dispatcher = null) {
        $this->character_manager = $character_manager;
        $this->event_dispatcher = $event_dispatcher;
        
        // Check if BuddyX theme is active
        $theme = wp_get_theme();
        $this->is_buddyx_theme = ('BuddyX' === $theme->name || 'BuddyX' === $theme->parent_theme);
        
        // Initialize hooks
        $this->initialize_hooks();
    }

    /**
     * Initialize BuddyPress hooks
     */
    public function initialize_hooks() {
        // Primary display hook - most compatible across themes
        add_action('bp_after_member_header', array($this, 'display_active_character'), 20);
        
        // BuddyX theme specific hook if needed
        if ($this->is_buddyx_theme) {
            add_action('buddyx_member_header', array($this, 'display_active_character'), 20);
        }
        
        // Character switch button
        add_action('bp_member_header_actions', array($this, 'add_character_switch_button'), 20);
        
        // Register AJAX handler for character switching
        add_action('wp_ajax_rpg_suite_switch_character', array($this, 'handle_character_switch_ajax'));
        
        // Enqueue necessary styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 20);
    }

    /**
     * Display active character in profile
     */
    public function display_active_character() {
        // Only display on BP profile pages
        if (!function_exists('bp_is_user') || !bp_is_user()) {
            return;
        }
        
        $user_id = bp_displayed_user_id();
        $active_character = $this->character_manager->get_active_character($user_id);
        
        if (!$active_character) {
            return;
        }
        
        // Get character data
        $character_data = $this->character_manager->get_character_data($active_character->ID);
        
        if (is_wp_error($character_data)) {
            return;
        }
        
        // HTML structure with appropriate classes
        ?>
        <div class="rpg-suite-character-display">
            <h4><?php echo esc_html__('Active Character', 'rpg-suite'); ?></h4>
            <div class="rpg-suite-character-name">
                <?php echo esc_html($active_character->post_title); ?>
                <?php if (!empty($character_data['class'])): ?>
                    <span class="rpg-suite-character-class">(<?php echo esc_html(ucfirst($character_data['class'])); ?>)</span>
                <?php endif; ?>
            </div>
            
            <div class="rpg-suite-attributes">
                <?php foreach ($character_data['attributes'] as $name => $value): ?>
                    <?php if ($value): ?>
                        <div class="rpg-suite-attribute">
                            <span class="rpg-suite-attribute-name"><?php echo esc_html(ucfirst($name)); ?>:</span>
                            <span class="rpg-suite-attribute-value"><?php echo esc_html($value); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <?php if (get_current_user_id() === $user_id || current_user_can('edit_post', $active_character->ID)): ?>
                <div class="rpg-suite-character-actions">
                    <?php
                    // Use direct admin URL to avoid conflicts
                    $edit_url = admin_url('post.php?post=' . $active_character->ID . '&action=edit');
                    ?>
                    <a href="<?php echo esc_url($edit_url); ?>" class="rpg-suite-edit-character button">
                        <?php echo esc_html__('Edit Character', 'rpg-suite'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Add character switch button to member header actions
     */
    public function add_character_switch_button() {
        // Only display on BP profile pages
        if (!function_exists('bp_is_user') || !bp_is_user()) {
            return;
        }
        
        $user_id = bp_displayed_user_id();
        
        // Only show for profile owner
        if (get_current_user_id() !== $user_id) {
            return;
        }
        
        $characters = $this->character_manager->get_user_characters($user_id);
        
        // Only show if user has multiple characters
        if (count($characters) <= 1) {
            return;
        }
        
        // Create nonce for security
        $nonce = wp_create_nonce('rpg_suite_switch_character');
        
        ?>
        <div class="generic-button rpg-suite-character-switcher">
            <a href="#" class="rpg-suite-character-switch-button">
                <?php echo esc_html__('Switch Character', 'rpg-suite'); ?>
            </a>
            
            <div class="rpg-suite-character-switcher-dropdown">
                <h4><?php echo esc_html__('Select Character', 'rpg-suite'); ?></h4>
                <ul class="rpg-suite-character-list">
                    <?php foreach ($characters as $character): ?>
                        <?php
                        $is_active = (bool) get_post_meta($character->ID, '_rpg_active', true);
                        $class = $is_active ? 'rpg-suite-character-item active' : 'rpg-suite-character-item';
                        ?>
                        <li class="<?php echo esc_attr($class); ?>">
                            <a href="#" class="rpg-suite-switch-to-character" 
                               data-character-id="<?php echo esc_attr($character->ID); ?>"
                               data-nonce="<?php echo esc_attr($nonce); ?>">
                                <span class="rpg-suite-character-name"><?php echo esc_html($character->post_title); ?></span>
                                <?php if ($is_active): ?>
                                    <span class="rpg-suite-active-indicator">(<?php echo esc_html__('Active', 'rpg-suite'); ?>)</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Handle character switch AJAX request
     */
    public function handle_character_switch_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rpg_suite_switch_character')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'rpg-suite')));
        }
        
        // Check for character ID
        if (!isset($_POST['character_id']) || !absint($_POST['character_id'])) {
            wp_send_json_error(array('message' => __('Invalid character ID.', 'rpg-suite')));
        }
        
        $character_id = absint($_POST['character_id']);
        $user_id = get_current_user_id();
        
        // Set the character as active
        $result = $this->character_manager->set_active_character($character_id, $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Character switched successfully.', 'rpg-suite')));
    }

    /**
     * Enqueue styles and scripts
     */
    public function enqueue_assets() {
        // Only enqueue on BP profile pages
        if (!function_exists('bp_is_user') || !bp_is_user()) {
            return;
        }
        
        // Register and enqueue styles
        wp_register_style(
            'rpg-suite-buddypress',
            RPG_SUITE_PLUGIN_URL . 'assets/css/buddypress.css',
            array(),
            RPG_SUITE_VERSION
        );
        
        wp_enqueue_style('rpg-suite-buddypress');
        
        // Register and enqueue scripts
        wp_register_script(
            'rpg-suite-buddypress',
            RPG_SUITE_PLUGIN_URL . 'assets/js/buddypress.js',
            array('jquery'),
            RPG_SUITE_VERSION,
            true
        );
        
        wp_localize_script('rpg-suite-buddypress', 'rpg_suite_bp', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
        
        wp_enqueue_script('rpg-suite-buddypress');
    }
}