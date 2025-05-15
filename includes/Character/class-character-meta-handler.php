<?php
/**
 * Character Meta Handler
 *
 * Handles saving, retrieving, and validating character meta data.
 *
 * @package    RPG_Suite
 * @subpackage Character
 * @since      0.1.0
 */

/**
 * Character Meta Handler Class
 *
 * Manages character metadata, including saving, retrieving, and validating
 * character attributes, skills, and other properties specific to the d7 system.
 */
class RPG_Suite_Character_Meta_Handler {

    /**
     * Die code utility instance
     *
     * @var RPG_Suite_Die_Code_Utility
     */
    private $die_code_utility;

    /**
     * Constructor
     *
     * @param RPG_Suite_Die_Code_Utility $die_code_utility Die code utility instance
     */
    public function __construct($die_code_utility = null) {
        // Die code utility will be implemented later
        // For now, we'll create a basic version without the dependency
        $this->die_code_utility = $die_code_utility;
    }

    /**
     * Save character meta data
     *
     * @param int   $post_id Character post ID
     * @param array $data    Meta data to save
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function save_character_meta($post_id, $data) {
        // Validate post type
        if (get_post_type($post_id) !== 'rpg_character') {
            return new WP_Error('invalid_post_type', __('Invalid post type. Character meta can only be saved to rpg_character posts.', 'rpg-suite'));
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error('permission_denied', __('You do not have permission to edit this character.', 'rpg-suite'));
        }
        
        // Validate data
        $validation = $this->validate_character_data($data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Save attributes
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $attribute => $value) {
                update_post_meta($post_id, '_rpg_attribute_' . $attribute, sanitize_text_field($value));
            }
        }
        
        // Save class
        if (isset($data['class'])) {
            update_post_meta($post_id, '_rpg_class', sanitize_text_field($data['class']));
        }
        
        // Save active status
        if (isset($data['active'])) {
            $active = (bool) $data['active'];
            update_post_meta($post_id, '_rpg_active', $active);
            
            // If this character is becoming active, deactivate other characters
            if ($active) {
                $this->deactivate_other_characters($post_id, get_post_field('post_author', $post_id));
            }
        }
        
        // Save invention points
        if (isset($data['invention_points'])) {
            update_post_meta($post_id, '_rpg_invention_points', absint($data['invention_points']));
        }
        
        // Save fate tokens
        if (isset($data['fate_tokens'])) {
            update_post_meta($post_id, '_rpg_fate_tokens', absint($data['fate_tokens']));
        }
        
        // Save skills (will be implemented in future version)
        if (isset($data['skills'])) {
            // For now, we'll store them as a serialized array
            update_post_meta($post_id, '_rpg_skills', $this->sanitize_skills($data['skills']));
        }
        
        // Calculate and save derived stats
        if (isset($data['attributes'])) {
            $derived_stats = $this->calculate_derived_stats($data['attributes']);
            update_post_meta($post_id, '_rpg_derived_stats', $derived_stats);
        }
        
        // Clear the character's cache
        $this->clear_cache($post_id);
        
        return true;
    }

    /**
     * Get character meta data
     *
     * @param int  $post_id   Character post ID
     * @param bool $use_cache Whether to use cached data
     * @return array|WP_Error Character data on success, WP_Error on failure
     */
    public function get_character_meta($post_id, $use_cache = true) {
        // Check cache first
        $cache_key = 'rpg_character_meta_' . $post_id;
        if ($use_cache) {
            $cached_data = wp_cache_get($cache_key);
            if ($cached_data !== false) {
                return $cached_data;
            }
        }
        
        // Validate post type
        if (get_post_type($post_id) !== 'rpg_character') {
            return new WP_Error('invalid_post_type', __('Invalid post type. This post is not a character.', 'rpg-suite'));
        }
        
        // Get post data
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', __('Character not found.', 'rpg-suite'));
        }
        
        // Build character data array
        $character = array(
            'id'          => $post->ID,
            'name'        => $post->post_title,
            'description' => $post->post_content,
            'owner'       => (int) $post->post_author,
            'attributes'  => array(
                'fortitude' => get_post_meta($post->ID, '_rpg_attribute_fortitude', true) ?: '2d7',
                'precision' => get_post_meta($post->ID, '_rpg_attribute_precision', true) ?: '2d7',
                'intellect' => get_post_meta($post->ID, '_rpg_attribute_intellect', true) ?: '2d7',
                'charisma'  => get_post_meta($post->ID, '_rpg_attribute_charisma', true) ?: '2d7',
            ),
            'class'            => get_post_meta($post->ID, '_rpg_class', true) ?: '',
            'active'           => (bool) get_post_meta($post->ID, '_rpg_active', true),
            'invention_points' => (int) get_post_meta($post->ID, '_rpg_invention_points', true) ?: 0,
            'fate_tokens'      => (int) get_post_meta($post->ID, '_rpg_fate_tokens', true) ?: 0,
            'skills'           => get_post_meta($post->ID, '_rpg_skills', true) ?: array(),
        );
        
        // Get derived stats or calculate them if not stored
        $derived_stats = get_post_meta($post->ID, '_rpg_derived_stats', true);
        if (empty($derived_stats)) {
            $derived_stats = $this->calculate_derived_stats($character['attributes']);
            update_post_meta($post->ID, '_rpg_derived_stats', $derived_stats);
        }
        
        $character['derived_stats'] = $derived_stats;
        
        // Cache the result (12 hours)
        if ($use_cache) {
            wp_cache_set($cache_key, $character, '', 12 * HOUR_IN_SECONDS);
        }
        
        return $character;
    }

    /**
     * Get default attributes for a character class
     *
     * @param string $class Character class
     * @return array Default attributes
     */
    public function get_default_attributes($class = '') {
        // Base attributes for all classes
        $attributes = array(
            'fortitude' => '2d7',
            'precision' => '2d7',
            'intellect' => '2d7',
            'charisma'  => '2d7',
        );
        
        // Adjust based on class
        switch ($class) {
            case 'aeronaut':
                $attributes['precision'] = '3d7';
                $attributes['fortitude'] = '2d7+1';
                break;
                
            case 'mechwright':
                $attributes['intellect'] = '3d7';
                $attributes['precision'] = '2d7+1';
                break;
                
            case 'aethermancer':
                $attributes['intellect'] = '3d7+1';
                break;
                
            case 'diplomat':
                $attributes['charisma'] = '3d7';
                $attributes['intellect'] = '2d7+1';
                break;
        }
        
        // Allow customization via filter
        return apply_filters('rpg_suite_default_attributes', $attributes, $class);
    }

    /**
     * Get default skills for a character class
     *
     * @param string $class Character class
     * @return array Default skills
     */
    public function get_default_skills($class = '') {
        // Base empty array
        $skills = array();
        
        // Add class-specific skills
        switch ($class) {
            case 'aeronaut':
                $skills = array(
                    array('name' => 'Piloting', 'attribute' => 'precision', 'value' => '2d7+1'),
                    array('name' => 'Navigation', 'attribute' => 'intellect', 'value' => '2d7'),
                );
                break;
                
            case 'mechwright':
                $skills = array(
                    array('name' => 'Engineering', 'attribute' => 'intellect', 'value' => '2d7+1'),
                    array('name' => 'Mechanics', 'attribute' => 'precision', 'value' => '2d7'),
                );
                break;
                
            case 'aethermancer':
                $skills = array(
                    array('name' => 'Aetheric Theory', 'attribute' => 'intellect', 'value' => '2d7+1'),
                    array('name' => 'Meditation', 'attribute' => 'fortitude', 'value' => '2d7'),
                );
                break;
                
            case 'diplomat':
                $skills = array(
                    array('name' => 'Negotiation', 'attribute' => 'charisma', 'value' => '2d7+1'),
                    array('name' => 'Cultural Knowledge', 'attribute' => 'intellect', 'value' => '2d7'),
                );
                break;
        }
        
        // Allow customization via filter
        return apply_filters('rpg_suite_default_skills', $skills, $class);
    }

    /**
     * Calculate derived stats from attributes
     *
     * @param array $attributes Character attributes
     * @return array Derived stats
     */
    public function calculate_derived_stats($attributes) {
        // For now, we'll use a simple calculation since we don't have the die code utility yet
        
        // Extract dice count from attributes (e.g. "2d7+1" -> 2)
        $fortitude_dice = (int) $attributes['fortitude'];
        $precision_dice = (int) $attributes['precision'];
        $intellect_dice = (int) $attributes['intellect'];
        $charisma_dice  = (int) $attributes['charisma'];
        
        // If attribute is in proper format, extract the dice count
        if (preg_match('/^(\d+)d7/', $attributes['fortitude'], $matches)) {
            $fortitude_dice = (int) $matches[1];
        }
        if (preg_match('/^(\d+)d7/', $attributes['precision'], $matches)) {
            $precision_dice = (int) $matches[1];
        }
        if (preg_match('/^(\d+)d7/', $attributes['intellect'], $matches)) {
            $intellect_dice = (int) $matches[1];
        }
        if (preg_match('/^(\d+)d7/', $attributes['charisma'], $matches)) {
            $charisma_dice = (int) $matches[1];
        }
        
        // Extract modifiers (e.g. "2d7+1" -> 1)
        $fortitude_mod = 0;
        $precision_mod = 0;
        $intellect_mod = 0;
        $charisma_mod  = 0;
        
        if (preg_match('/\+(\d+)$/', $attributes['fortitude'], $matches)) {
            $fortitude_mod = (int) $matches[1];
        }
        if (preg_match('/\+(\d+)$/', $attributes['precision'], $matches)) {
            $precision_mod = (int) $matches[1];
        }
        if (preg_match('/\+(\d+)$/', $attributes['intellect'], $matches)) {
            $intellect_mod = (int) $matches[1];
        }
        if (preg_match('/\+(\d+)$/', $attributes['charisma'], $matches)) {
            $charisma_mod = (int) $matches[1];
        }
        
        // Calculate derived stats
        $derived_stats = array(
            'vitality'   => ($fortitude_dice * 5) + $fortitude_mod,
            'movement'   => 5 + $precision_dice,
            'initiative' => $precision_dice + $intellect_dice,
            'will'       => $intellect_dice + $charisma_dice,
        );
        
        // Allow customization via filter
        return apply_filters('rpg_suite_derived_stats', $derived_stats, $attributes);
    }

    /**
     * Validate character data
     *
     * @param array $data Character data
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function validate_character_data($data) {
        // Validate attributes
        if (isset($data['attributes'])) {
            $valid_attributes = array('fortitude', 'precision', 'intellect', 'charisma');
            
            foreach ($data['attributes'] as $attribute => $value) {
                // Check valid attribute name
                if (!in_array($attribute, $valid_attributes)) {
                    return new WP_Error(
                        'invalid_attribute',
                        sprintf(__('Invalid attribute: %s', 'rpg-suite'), $attribute)
                    );
                }
                
                // Validate die code format
                if (!$this->validate_die_code($value)) {
                    return new WP_Error(
                        'invalid_die_code',
                        sprintf(__('Invalid die code format for %s: %s', 'rpg-suite'), $attribute, $value)
                    );
                }
            }
        }
        
        // Validate class
        if (isset($data['class'])) {
            $valid_classes = array('aeronaut', 'mechwright', 'aethermancer', 'diplomat', '');
            
            if (!in_array($data['class'], $valid_classes)) {
                return new WP_Error(
                    'invalid_class',
                    sprintf(__('Invalid character class: %s', 'rpg-suite'), $data['class'])
                );
            }
        }
        
        // Validate skills
        if (isset($data['skills'])) {
            foreach ($data['skills'] as $skill) {
                // Check if skill has required properties
                if (!isset($skill['attribute']) || !isset($skill['value'])) {
                    return new WP_Error(
                        'invalid_skill',
                        __('Skills must have both attribute and value properties', 'rpg-suite')
                    );
                }
                
                // Check if skill attribute is valid
                if (!in_array($skill['attribute'], array('fortitude', 'precision', 'intellect', 'charisma'))) {
                    return new WP_Error(
                        'invalid_skill_attribute',
                        sprintf(__('Invalid skill attribute: %s', 'rpg-suite'), $skill['attribute'])
                    );
                }
                
                // Validate die code format
                if (!$this->validate_die_code($skill['value'])) {
                    return new WP_Error(
                        'invalid_skill_die_code',
                        sprintf(__('Invalid skill die code format: %s', 'rpg-suite'), $skill['value'])
                    );
                }
            }
        }
        
        return true;
    }

    /**
     * Validate die code format
     *
     * @param string $die_code Die code to validate
     * @return bool Whether the die code is valid
     */
    private function validate_die_code($die_code) {
        // Basic validation (will be improved with die code utility)
        return preg_match('/^\d+d7(\+\d+)?$/', $die_code);
    }

    /**
     * Sanitize skills data
     *
     * @param array $skills Skills data
     * @return array Sanitized skills data
     */
    private function sanitize_skills($skills) {
        $sanitized = array();
        
        if (is_array($skills)) {
            foreach ($skills as $skill) {
                if (isset($skill['name']) && isset($skill['attribute']) && isset($skill['value'])) {
                    $sanitized[] = array(
                        'name'      => sanitize_text_field($skill['name']),
                        'attribute' => sanitize_key($skill['attribute']),
                        'value'     => sanitize_text_field($skill['value']),
                    );
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * Deactivate other characters for a user
     *
     * @param int $current_post_id Current character post ID
     * @param int $author_id       Author user ID
     */
    private function deactivate_other_characters($current_post_id, $author_id) {
        $args = array(
            'post_type'      => 'rpg_character',
            'author'         => $author_id,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'post__not_in'   => array($current_post_id),
            'meta_query'     => array(
                array(
                    'key'   => '_rpg_active',
                    'value' => '1',
                ),
            ),
        );
        
        $characters = get_posts($args);
        
        foreach ($characters as $character) {
            update_post_meta($character->ID, '_rpg_active', 0);
        }
    }

    /**
     * Clear character cache
     *
     * @param int $post_id Character post ID
     */
    public function clear_cache($post_id) {
        $cache_key = 'rpg_character_meta_' . $post_id;
        wp_cache_delete($cache_key);
    }
}