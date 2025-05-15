<?php
/**
 * Character Manager
 *
 * Provides a centralized API for all character operations.
 *
 * @package    RPG_Suite
 * @subpackage Character
 * @since      0.1.0
 */

/**
 * Character Manager Class
 *
 * Handles character creation, retrieval, updating, and deletion operations.
 * Provides methods for managing multiple characters per user with active status tracking.
 */
class RPG_Suite_Character_Manager {

    /**
     * Character meta handler instance
     *
     * @var RPG_Suite_Character_Meta_Handler
     */
    private $meta_handler;

    /**
     * Die code utility instance (for future use)
     *
     * @var RPG_Suite_Die_Code_Utility
     */
    private $die_code_utility;

    /**
     * Event dispatcher instance (for future use)
     *
     * @var RPG_Suite_Event_Dispatcher
     */
    private $event_dispatcher;

    /**
     * Maximum characters per user
     *
     * @var int
     */
    private $max_characters_per_user;

    /**
     * Constructor
     *
     * @param RPG_Suite_Character_Meta_Handler $meta_handler    Meta handler instance
     * @param RPG_Suite_Die_Code_Utility       $die_code_utility Die code utility instance (optional)
     * @param RPG_Suite_Event_Dispatcher       $event_dispatcher Event dispatcher instance (optional)
     */
    public function __construct(
        $meta_handler,
        $die_code_utility = null,
        $event_dispatcher = null
    ) {
        $this->meta_handler = $meta_handler;
        $this->die_code_utility = $die_code_utility;
        $this->event_dispatcher = $event_dispatcher;
        
        // Set default character limit (can be filtered)
        $this->max_characters_per_user = apply_filters('rpg_suite_max_characters_per_user', 2);
    }

    /**
     * Create a new character
     *
     * @param array $args Character creation arguments
     * @return int|WP_Error Character ID on success, WP_Error on failure
     */
    public function create_character($args) {
        // Get user ID from args or current user
        $user_id = isset($args['user_id']) ? absint($args['user_id']) : get_current_user_id();
        
        // Check if user exists
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', __('Invalid user ID.', 'rpg-suite'));
        }
        
        // Check character limit
        if (!$this->can_create_character($user_id)) {
            return new WP_Error(
                'character_limit_reached',
                sprintf(
                    __('You have reached the maximum number of characters (%d).', 'rpg-suite'),
                    $this->max_characters_per_user
                )
            );
        }
        
        // Ensure character name is set
        if (empty($args['name'])) {
            return new WP_Error('missing_name', __('Character name is required.', 'rpg-suite'));
        }
        
        // Set character class if provided
        $class = isset($args['class']) ? sanitize_text_field($args['class']) : '';
        
        // Set attributes from args or defaults
        $attributes = isset($args['attributes']) ? $args['attributes'] : $this->meta_handler->get_default_attributes($class);
        
        // Validate attributes
        $validation = $this->meta_handler->validate_character_data(array('attributes' => $attributes, 'class' => $class));
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Create character post
        $post_data = array(
            'post_title'   => sanitize_text_field($args['name']),
            'post_content' => isset($args['description']) ? wp_kses_post($args['description']) : '',
            'post_status'  => 'publish',
            'post_type'    => 'rpg_character',
            'post_author'  => $user_id,
        );
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Check if this is the user's first character
        $is_first_character = count($this->get_user_characters($user_id)) <= 1;
        
        // Prepare meta data
        $meta_data = array(
            'attributes'        => $attributes,
            'class'             => $class,
            'active'            => $is_first_character, // Make active if it's the first character
            'invention_points'  => isset($args['invention_points']) ? absint($args['invention_points']) : 0,
            'fate_tokens'       => isset($args['fate_tokens']) ? absint($args['fate_tokens']) : 0,
        );
        
        // Add skills if provided
        if (isset($args['skills'])) {
            $meta_data['skills'] = $args['skills'];
        } else {
            $meta_data['skills'] = $this->meta_handler->get_default_skills($class);
        }
        
        // Save character meta
        $meta_result = $this->meta_handler->save_character_meta($post_id, $meta_data);
        
        if (is_wp_error($meta_result)) {
            // If meta saving failed, delete the post
            wp_delete_post($post_id, true);
            return $meta_result;
        }
        
        // Clear user characters cache
        $this->clear_character_cache(null, $user_id);
        
        // Dispatch event if event_dispatcher is available
        if ($this->event_dispatcher !== null) {
            // Create event data
            $event_data = array(
                'character_id' => $post_id,
                'user_id'      => $user_id,
                'character'    => $this->get_character($post_id),
            );
            
            // Only dispatch if event_dispatcher has the dispatch method
            if (is_object($this->event_dispatcher) && method_exists($this->event_dispatcher, 'dispatch')) {
                $this->event_dispatcher->dispatch('character_created', $event_data);
            }
        }
        
        return $post_id;
    }

    /**
     * Check if user can create a character
     *
     * @param int $user_id User ID
     * @return bool Whether the user can create a character
     */
    public function can_create_character($user_id) {
        // Always allow administrators to create characters
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Count existing characters
        $user_characters = $this->get_user_characters($user_id);
        
        // Check against limit
        return count($user_characters) < $this->max_characters_per_user;
    }

    /**
     * Get a character by ID
     *
     * @param int $character_id Character ID
     * @return WP_Post|null Character post object or null if not found
     */
    public function get_character($character_id) {
        $post = get_post($character_id);
        
        // Check if post exists and is a character
        if ($post && $post->post_type === 'rpg_character') {
            return $post;
        }
        
        return null;
    }

    /**
     * Get character data including metadata
     *
     * @param int  $character_id Character ID
     * @param bool $use_cache    Whether to use cached data
     * @return array|WP_Error Character data on success, WP_Error on failure
     */
    public function get_character_data($character_id, $use_cache = true) {
        return $this->meta_handler->get_character_meta($character_id, $use_cache);
    }

    /**
     * Get all characters belonging to a user
     *
     * @param int  $user_id   User ID
     * @param bool $use_cache Whether to use cached results
     * @return array Array of character post objects
     */
    public function get_user_characters($user_id, $use_cache = true) {
        // Check cache first
        $cache_key = 'rpg_user_characters_' . $user_id;
        if ($use_cache) {
            $cached_ids = wp_cache_get($cache_key);
            if ($cached_ids !== false) {
                $characters = array();
                foreach ($cached_ids as $id) {
                    $character = $this->get_character($id);
                    if ($character) {
                        $characters[] = $character;
                    }
                }
                return $characters;
            }
        }
        
        // Query for user's characters
        $args = array(
            'post_type'      => 'rpg_character',
            'author'         => $user_id,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );
        
        $characters = get_posts($args);
        
        // Cache the character IDs
        if ($use_cache) {
            $character_ids = wp_list_pluck($characters, 'ID');
            wp_cache_set($cache_key, $character_ids, '', 12 * HOUR_IN_SECONDS);
        }
        
        return $characters;
    }

    /**
     * Get the active character for a user
     *
     * @param int  $user_id   User ID
     * @param bool $use_cache Whether to use cached results
     * @return WP_Post|null Active character post object or null if none
     */
    public function get_active_character($user_id, $use_cache = true) {
        // Check cache first
        $cache_key = 'rpg_active_character_' . $user_id;
        if ($use_cache) {
            $cached_id = wp_cache_get($cache_key);
            if ($cached_id !== false) {
                // Verify the character still exists
                $character = $this->get_character($cached_id);
                if ($character) {
                    return $character;
                }
            }
        }
        
        // Query for active character
        $args = array(
            'post_type'      => 'rpg_character',
            'author'         => $user_id,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'   => '_rpg_active',
                    'value' => '1',
                ),
            ),
        );
        
        $characters = get_posts($args);
        
        if (!empty($characters)) {
            $active_character = $characters[0];
            
            // Cache the active character ID
            if ($use_cache) {
                wp_cache_set($cache_key, $active_character->ID, '', 12 * HOUR_IN_SECONDS);
            }
            
            return $active_character;
        }
        
        return null;
    }

    /**
     * Set a character as the active character for a user
     *
     * @param int $character_id Character ID
     * @param int $user_id      User ID (optional, defaults to character owner)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function set_active_character($character_id, $user_id = null) {
        // Get the character
        $character = $this->get_character($character_id);
        if (!$character) {
            return new WP_Error('character_not_found', __('Character not found.', 'rpg-suite'));
        }
        
        // Determine user ID
        if ($user_id === null) {
            $user_id = $character->post_author;
        }
        
        // Verify ownership
        if ($character->post_author != $user_id) {
            return new WP_Error('not_owner', __('You do not own this character.', 'rpg-suite'));
        }
        
        // Setup concurrency control with transient
        $mutex_key = 'rpg_character_activation_' . $user_id;
        $mutex_value = get_transient($mutex_key);
        
        if ($mutex_value !== false) {
            return new WP_Error('activation_in_progress', __('Another character activation is in progress. Please try again.', 'rpg-suite'));
        }
        
        // Set mutex for 30 seconds
        set_transient($mutex_key, time(), 30);
        
        try {
            // Get previous active character for event data
            $previous_active = $this->get_active_character($user_id);
            
            // Set character as active
            $meta_result = $this->meta_handler->save_character_meta($character_id, array('active' => true));
            
            if (is_wp_error($meta_result)) {
                return $meta_result;
            }
            
            // Clear caches
            $this->clear_character_cache($character_id, $user_id);
            
            // Dispatch event if event_dispatcher is available
            if ($this->event_dispatcher !== null) {
                // Create event data
                $event_data = array(
                    'character_id'         => $character_id,
                    'user_id'              => $user_id,
                    'previous_character_id' => $previous_active ? $previous_active->ID : null,
                );
                
                // Only dispatch if event_dispatcher has the dispatch method
                if (is_object($this->event_dispatcher) && method_exists($this->event_dispatcher, 'dispatch')) {
                    $this->event_dispatcher->dispatch('character_activated', $event_data);
                }
            }
            
            return true;
        } catch (Exception $e) {
            return new WP_Error('activation_error', $e->getMessage());
        } finally {
            // Release mutex
            delete_transient($mutex_key);
        }
    }

    /**
     * Update character data
     *
     * @param int   $character_id Character ID
     * @param array $data         Character data to update
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_character($character_id, $data) {
        // Get the character
        $character = $this->get_character($character_id);
        if (!$character) {
            return new WP_Error('character_not_found', __('Character not found.', 'rpg-suite'));
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $character_id)) {
            return new WP_Error('permission_denied', __('You do not have permission to edit this character.', 'rpg-suite'));
        }
        
        $post_data = array();
        $meta_data = array();
        
        // Prepare post data
        if (isset($data['name'])) {
            $post_data['post_title'] = sanitize_text_field($data['name']);
        }
        
        if (isset($data['description'])) {
            $post_data['post_content'] = wp_kses_post($data['description']);
        }
        
        // Update post if there's data to update
        if (!empty($post_data)) {
            $post_data['ID'] = $character_id;
            $post_result = wp_update_post($post_data, true);
            
            if (is_wp_error($post_result)) {
                return $post_result;
            }
        }
        
        // Prepare meta data
        if (isset($data['attributes'])) {
            $meta_data['attributes'] = $data['attributes'];
        }
        
        if (isset($data['class'])) {
            $meta_data['class'] = $data['class'];
        }
        
        if (isset($data['active'])) {
            $meta_data['active'] = (bool) $data['active'];
        }
        
        if (isset($data['invention_points'])) {
            $meta_data['invention_points'] = absint($data['invention_points']);
        }
        
        if (isset($data['fate_tokens'])) {
            $meta_data['fate_tokens'] = absint($data['fate_tokens']);
        }
        
        if (isset($data['skills'])) {
            $meta_data['skills'] = $data['skills'];
        }
        
        // Update meta if there's data to update
        if (!empty($meta_data)) {
            $meta_result = $this->meta_handler->save_character_meta($character_id, $meta_data);
            
            if (is_wp_error($meta_result)) {
                return $meta_result;
            }
        }
        
        // Clear caches
        $this->clear_character_cache($character_id, $character->post_author);
        
        // Dispatch event if event_dispatcher is available
        if ($this->event_dispatcher !== null) {
            // Create event data
            $event_data = array(
                'character_id' => $character_id,
                'user_id'      => $character->post_author,
                'updates'      => array_merge(
                    empty($post_data) ? array() : array('post' => $post_data),
                    empty($meta_data) ? array() : array('meta' => $meta_data)
                ),
            );
            
            // Only dispatch if event_dispatcher has the dispatch method
            if (is_object($this->event_dispatcher) && method_exists($this->event_dispatcher, 'dispatch')) {
                $this->event_dispatcher->dispatch('character_updated', $event_data);
            }
        }
        
        return true;
    }

    /**
     * Delete a character
     *
     * @param int $character_id Character ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function delete_character($character_id) {
        // Get the character
        $character = $this->get_character($character_id);
        if (!$character) {
            return new WP_Error('character_not_found', __('Character not found.', 'rpg-suite'));
        }
        
        // Check permissions
        if (!current_user_can('delete_post', $character_id)) {
            return new WP_Error('permission_denied', __('You do not have permission to delete this character.', 'rpg-suite'));
        }
        
        // Store user ID for cache clearing
        $user_id = $character->post_author;
        
        // Check if this is the active character
        $is_active = (bool) get_post_meta($character_id, '_rpg_active', true);
        
        // Delete the character
        $result = wp_delete_post($character_id, true);
        
        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete character.', 'rpg-suite'));
        }
        
        // Clear caches
        $this->clear_character_cache($character_id, $user_id);
        
        // If this was the active character, set another one as active if available
        if ($is_active) {
            $user_characters = $this->get_user_characters($user_id, false);
            
            if (!empty($user_characters)) {
                $this->set_active_character($user_characters[0]->ID, $user_id);
            }
        }
        
        // Dispatch event if event_dispatcher is available
        if ($this->event_dispatcher !== null) {
            // Create event data
            $event_data = array(
                'character_id' => $character_id,
                'user_id'      => $user_id,
                'was_active'   => $is_active,
            );
            
            // Only dispatch if event_dispatcher has the dispatch method
            if (is_object($this->event_dispatcher) && method_exists($this->event_dispatcher, 'dispatch')) {
                $this->event_dispatcher->dispatch('character_deleted', $event_data);
            }
        }
        
        return true;
    }

    /**
     * Clear character cache
     *
     * @param int $character_id Character ID (optional)
     * @param int $user_id      User ID (optional)
     */
    public function clear_character_cache($character_id = null, $user_id = null) {
        // Clear character meta cache
        if ($character_id) {
            $this->meta_handler->clear_cache($character_id);
        }
        
        // Clear user characters cache
        if ($user_id) {
            wp_cache_delete('rpg_user_characters_' . $user_id);
            wp_cache_delete('rpg_active_character_' . $user_id);
        } elseif ($character_id) {
            // If user ID not provided but character ID is, get the user ID from the character
            $character = $this->get_character($character_id);
            if ($character) {
                wp_cache_delete('rpg_user_characters_' . $character->post_author);
                wp_cache_delete('rpg_active_character_' . $character->post_author);
            }
        }
    }
}