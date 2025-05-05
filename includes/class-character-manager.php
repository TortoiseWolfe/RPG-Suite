<?php
/**
 * Character Management for RPG Suite.
 *
 * This class handles the relationship between WordPress users (players) 
 * and characters, supporting multiple characters per player.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/includes
 */

namespace RPG\Suite\Includes;

/**
 * Character Manager class.
 *
 * Manages the relationship between users and their characters.
 */
class Character_Manager {

    /**
     * Initialize the character manager.
     */
    public function __construct() {
        // Register hooks for character management
        add_action('init', [$this, 'register_character_metadata']);
        add_action('save_post_rpg_character', [$this, 'set_character_user_relationship'], 10, 3);
        add_action('delete_post', [$this, 'cleanup_character_relationship']);
        
        // Character limits and validation
        add_filter('wp_insert_post_data', [$this, 'validate_character_limit'], 10, 2);
        
        // Character switching
        add_action('template_redirect', [$this, 'handle_character_switching']);
        
        // Admin UI customizations
        add_action('add_meta_boxes_rpg_character', [$this, 'add_character_meta_boxes']);
        add_action('save_post_rpg_character', [$this, 'save_character_meta_boxes'], 10, 2);
        
        // REST API extensions
        add_action('rest_api_init', [$this, 'register_rest_fields']);
    }
    
    /**
     * Register character metadata.
     *
     * @return void
     */
    public function register_character_metadata() {
        // Register metadata for character ownership
        register_meta('post', 'character_owner', [
            'object_subtype' => 'rpg_character',
            'type' => 'integer',
            'description' => 'The user ID of the character owner',
            'single' => true,
            'show_in_rest' => true,
        ]);
        
        // Register metadata for character type (PC/NPC)
        register_meta('post', 'character_is_npc', [
            'object_subtype' => 'rpg_character',
            'type' => 'boolean',
            'description' => 'Whether the character is an NPC',
            'single' => true,
            'default' => false,
            'show_in_rest' => true,
        ]);
        
        // Register metadata for active status
        register_meta('post', 'character_is_active', [
            'object_subtype' => 'rpg_character',
            'type' => 'boolean',
            'description' => 'Whether this is the active character for the user',
            'single' => true,
            'default' => false,
            'show_in_rest' => true,
        ]);
    }
    
    /**
     * Set character-user relationship on save.
     *
     * @param int     $post_id The post ID.
     * @param WP_Post $post    The post object.
     * @param bool    $update  Whether this is an existing post being updated.
     * @return void
     */
    public function set_character_user_relationship($post_id, $post, $update) {
        // Skip if doing autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Skip if this is a revision
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Skip if current user can't edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Set character owner if not already set
        $owner_id = get_post_meta($post_id, 'character_owner', true);
        if (empty($owner_id)) {
            // Default to current user, unless they're an admin creating an NPC
            $is_npc = isset($_POST['character_is_npc']) ? (bool) $_POST['character_is_npc'] : false;
            
            if ($is_npc && current_user_can('create_npc_character')) {
                // NPC has no owner
                delete_post_meta($post_id, 'character_owner');
            } else {
                // Set current user as owner
                update_post_meta($post_id, 'character_owner', get_current_user_id());
            }
        }
        
        // Save NPC status
        if (isset($_POST['character_is_npc'])) {
            update_post_meta($post_id, 'character_is_npc', (bool) $_POST['character_is_npc']);
        }
        
        // Handle making this character active
        if (isset($_POST['character_is_active']) && $_POST['character_is_active']) {
            $this->set_active_character($post_id);
        }
    }
    
    /**
     * Clean up character relationships when a character is deleted.
     *
     * @param int $post_id The post ID being deleted.
     * @return void
     */
    public function cleanup_character_relationship($post_id) {
        if (get_post_type($post_id) !== 'rpg_character') {
            return;
        }
        
        // Get the owner before the character is deleted
        $owner_id = get_post_meta($post_id, 'character_owner', true);
        
        // If this was the active character, set another one as active
        if ($owner_id && get_post_meta($post_id, 'character_is_active', true)) {
            // Find another character for this user
            $args = [
                'post_type' => 'rpg_character',
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => 'character_owner',
                        'value' => $owner_id,
                    ],
                    [
                        'key' => 'character_is_npc',
                        'value' => '0',
                    ],
                ],
                'post__not_in' => [$post_id],
            ];
            
            $characters = get_posts($args);
            
            if (!empty($characters)) {
                update_post_meta($characters[0]->ID, 'character_is_active', true);
            }
        }
    }
    
    /**
     * Validate character limit when creating a new character.
     *
     * @param array $data    The post data.
     * @param array $postarr The original post array.
     * @return array The filtered post data.
     */
    public function validate_character_limit($data, $postarr) {
        // Only process for rpg_character post type
        if ($data['post_type'] !== 'rpg_character') {
            return $data;
        }
        
        // Skip for updates
        if (!empty($postarr['ID'])) {
            return $data;
        }
        
        // Admins and GMs can create characters freely
        if (current_user_can('gm_rpg')) {
            return $data;
        }
        
        // Check if user has reached character limit
        $user_id = get_current_user_id();
        $character_limit = get_option('rpg_suite_character_limit', 2);
        
        $args = [
            'post_type' => 'rpg_character',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'character_owner',
                    'value' => $user_id,
                ],
                [
                    'key' => 'character_is_npc',
                    'value' => '0',
                ],
            ],
        ];
        
        $user_characters = get_posts($args);
        
        if (count($user_characters) >= $character_limit) {
            // Prevent character creation by changing post_type to a non-existent type
            // A cleaner approach would be to add an error message, but this works for now
            $data['post_type'] = 'character_limit_exceeded';
            
            // Add error notice
            add_action('admin_notices', function() {
                $character_limit = get_option('rpg_suite_character_limit', 2);
                echo '<div class="error"><p>' . sprintf(__('You cannot create more than %d characters.', 'rpg-suite'), $character_limit) . '</p></div>';
            });
        }
        
        return $data;
    }
    
    /**
     * Handle character switching.
     *
     * @return void
     */
    public function handle_character_switching() {
        // Check if we have a character switch request
        if (isset($_GET['rpg_switch_character']) && wp_verify_nonce($_GET['_wpnonce'], 'rpg_switch_character')) {
            $character_id = intval($_GET['rpg_switch_character']);
            
            // Verify the user owns this character
            $owner_id = get_post_meta($character_id, 'character_owner', true);
            
            if ($owner_id == get_current_user_id()) {
                $this->set_active_character($character_id);
            }
            
            // Redirect back to referring page or home
            wp_safe_redirect(wp_get_referer() ?: home_url());
            exit;
        }
    }
    
    /**
     * Set a character as the active character for its owner.
     *
     * @param int $character_id The character post ID.
     * @return void
     */
    public function set_active_character($character_id) {
        // Get the owner
        $owner_id = get_post_meta($character_id, 'character_owner', true);
        
        if (!$owner_id) {
            return;
        }
        
        // Clear active status on all other characters for this user
        $args = [
            'post_type' => 'rpg_character',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'character_owner',
                    'value' => $owner_id,
                ],
                [
                    'key' => 'character_is_active',
                    'value' => '1',
                ],
            ],
        ];
        
        $active_characters = get_posts($args);
        
        foreach ($active_characters as $active_character) {
            if ($active_character->ID != $character_id) {
                update_post_meta($active_character->ID, 'character_is_active', false);
            }
        }
        
        // Set this character as active
        update_post_meta($character_id, 'character_is_active', true);
    }
    
    /**
     * Add meta boxes to the character edit screen.
     *
     * @param WP_Post $post The post object.
     * @return void
     */
    public function add_character_meta_boxes($post) {
        add_meta_box(
            'rpg_character_owner',
            __('Character Ownership', 'rpg-suite'),
            [$this, 'render_character_owner_metabox'],
            'rpg_character',
            'side',
            'high'
        );
    }
    
    /**
     * Render the character owner meta box.
     *
     * @param WP_Post $post The post object.
     * @return void
     */
    public function render_character_owner_metabox($post) {
        wp_nonce_field('rpg_character_ownership', 'rpg_character_ownership_nonce');
        
        $owner_id = get_post_meta($post->ID, 'character_owner', true);
        $is_npc = get_post_meta($post->ID, 'character_is_npc', true);
        $is_active = get_post_meta($post->ID, 'character_is_active', true);
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="character_is_npc" value="1" <?php checked($is_npc); ?> />
                <?php _e('This character is an NPC', 'rpg-suite'); ?>
            </label>
        </p>
        
        <?php if (!$is_npc && current_user_can('gm_rpg')): ?>
        <p>
            <label for="character_owner"><?php _e('Character Owner:', 'rpg-suite'); ?></label>
            <?php
            wp_dropdown_users([
                'name' => 'character_owner',
                'selected' => $owner_id ?: get_current_user_id(),
                'show_option_none' => __('None (NPC)', 'rpg-suite'),
            ]);
            ?>
        </p>
        <?php endif; ?>
        
        <?php if (!$is_npc): ?>
        <p>
            <label>
                <input type="checkbox" name="character_is_active" value="1" <?php checked($is_active); ?> />
                <?php _e('Set as active character', 'rpg-suite'); ?>
            </label>
        </p>
        <?php endif;
    }
    
    /**
     * Save the character meta box data.
     *
     * @param int     $post_id The post ID.
     * @param WP_Post $post    The post object.
     * @return void
     */
    public function save_character_meta_boxes($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['rpg_character_ownership_nonce']) || !wp_verify_nonce($_POST['rpg_character_ownership_nonce'], 'rpg_character_ownership')) {
            return;
        }
        
        // Skip if current user can't edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save NPC status
        $is_npc = isset($_POST['character_is_npc']) ? (bool) $_POST['character_is_npc'] : false;
        update_post_meta($post_id, 'character_is_npc', $is_npc);
        
        // Save owner if changed and user has permission
        if (current_user_can('gm_rpg') && isset($_POST['character_owner'])) {
            $owner_id = intval($_POST['character_owner']);
            if ($owner_id > 0) {
                update_post_meta($post_id, 'character_owner', $owner_id);
            } else {
                delete_post_meta($post_id, 'character_owner');
            }
        }
        
        // Handle making this character active
        if (isset($_POST['character_is_active']) && $_POST['character_is_active']) {
            $this->set_active_character($post_id);
        }
    }
    
    /**
     * Register REST API fields for characters.
     *
     * @return void
     */
    public function register_rest_fields() {
        register_rest_field('rpg_character', 'owner', [
            'get_callback' => function($post) {
                $owner_id = get_post_meta($post['id'], 'character_owner', true);
                return $owner_id ? intval($owner_id) : null;
            },
            'update_callback' => function($value, $post) {
                if (current_user_can('gm_rpg') || get_current_user_id() == get_post_meta($post->ID, 'character_owner', true)) {
                    update_post_meta($post->ID, 'character_owner', intval($value));
                }
            },
            'schema' => [
                'description' => __('Character owner user ID', 'rpg-suite'),
                'type' => 'integer',
            ],
        ]);
        
        register_rest_field('rpg_character', 'is_npc', [
            'get_callback' => function($post) {
                return (bool) get_post_meta($post['id'], 'character_is_npc', true);
            },
            'update_callback' => function($value, $post) {
                if (current_user_can('gm_rpg') || get_current_user_id() == get_post_meta($post->ID, 'character_owner', true)) {
                    update_post_meta($post->ID, 'character_is_npc', (bool) $value);
                }
            },
            'schema' => [
                'description' => __('Whether the character is an NPC', 'rpg-suite'),
                'type' => 'boolean',
            ],
        ]);
        
        register_rest_field('rpg_character', 'is_active', [
            'get_callback' => function($post) {
                return (bool) get_post_meta($post['id'], 'character_is_active', true);
            },
            'update_callback' => function($value, $post) {
                if (get_current_user_id() == get_post_meta($post->ID, 'character_owner', true)) {
                    if ($value) {
                        $this->set_active_character($post->ID);
                    } else {
                        update_post_meta($post->ID, 'character_is_active', false);
                    }
                }
            },
            'schema' => [
                'description' => __('Whether this is the active character for the user', 'rpg-suite'),
                'type' => 'boolean',
            ],
        ]);
    }
    
    /**
     * Get the currently active character for a user.
     *
     * @param int|null $user_id The user ID, or null for current user.
     * @return WP_Post|null The active character post, or null if none is active.
     */
    public function get_active_character($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return null;
        }
        
        $args = [
            'post_type' => 'rpg_character',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'character_owner',
                    'value' => $user_id,
                ],
                [
                    'key' => 'character_is_active',
                    'value' => '1',
                ],
            ],
        ];
        
        $characters = get_posts($args);
        
        return !empty($characters) ? $characters[0] : null;
    }
    
    /**
     * Get all characters for a user.
     *
     * @param int|null $user_id The user ID, or null for current user.
     * @return array The character posts.
     */
    public function get_user_characters($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return [];
        }
        
        $args = [
            'post_type' => 'rpg_character',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'character_owner',
                    'value' => $user_id,
                ],
                [
                    'key' => 'character_is_npc',
                    'value' => '0',
                ],
            ],
        ];
        
        return get_posts($args);
    }
    
    /**
     * Check if a user can create more characters.
     *
     * @param int|null $user_id The user ID, or null for current user.
     * @return bool Whether the user can create more characters.
     */
    public function can_create_character($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return false;
        }
        
        // Admins and GMs can always create characters
        if (user_can($user_id, 'gm_rpg')) {
            return true;
        }
        
        $character_limit = get_option('rpg_suite_character_limit', 2);
        $character_count = count($this->get_user_characters($user_id));
        
        return $character_count < $character_limit;
    }
}