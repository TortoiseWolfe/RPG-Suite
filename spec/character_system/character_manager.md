# Character Manager Specification

## Purpose
The Character Manager provides a centralized API for all operations related to character creation, retrieval, updating, and deletion in our steampunk d7 system.

## Requirements
1. Support creating, retrieving, updating, and deleting characters
2. Enforce character ownership and limits per user
3. Manage active character status with proper concurrency control
4. Handle character attributes and skills using d7 system
5. Support invention and gadget creation mechanics
6. Trigger appropriate events for character state changes
7. Provide clear error handling and validation
8. Implement caching for performance optimization

## Class Definition

```php
/**
 * Character management system for d7 RPG
 *
 * @since 1.0.0
 */
class RPG_Suite_Character_Manager {
    /**
     * Event dispatcher instance
     *
     * @since 1.0.0
     * @var RPG_Suite_Event_Dispatcher
     */
    private $event_dispatcher;
    
    /**
     * Die code utility instance
     *
     * @since 1.0.0
     * @var RPG_Suite_Die_Code_Utility
     */
    private $die_code_utility;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     * @param RPG_Suite_Event_Dispatcher $event_dispatcher Event dispatcher.
     * @param RPG_Suite_Die_Code_Utility $die_code_utility Die code utility.
     */
    public function __construct(RPG_Suite_Event_Dispatcher $event_dispatcher, RPG_Suite_Die_Code_Utility $die_code_utility) {
        $this->event_dispatcher = $event_dispatcher;
        $this->die_code_utility = $die_code_utility;
    }
    
    /**
     * Create a new character
     *
     * @since 1.0.0
     * @param int   $user_id Owner user ID.
     * @param array $data    Character data.
     * @return int|WP_Error Character ID or error.
     */
    public function create_character($user_id, array $data) {
        // Validation and implementation logic
    }
    
    /**
     * Get a character by ID
     *
     * @since 1.0.0
     * @param int $character_id Character ID.
     * @return WP_Post|null Character post or null.
     */
    public function get_character($character_id) {
        // Implementation logic
    }
    
    /**
     * Get all characters for a user
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return WP_Post[] Array of character posts.
     */
    public function get_user_characters($user_id) {
        // Implementation logic
    }
    
    /**
     * Get a user's active character
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return WP_Post|null Active character or null.
     */
    public function get_active_character($user_id) {
        // Implementation logic
    }
    
    /**
     * Set a character as the user's active character
     *
     * Uses transient-based mutex for concurrency control.
     *
     * @since 1.0.0
     * @param int $user_id      User ID.
     * @param int $character_id Character ID.
     * @return bool|WP_Error Success or error.
     */
    public function set_active_character($user_id, $character_id) {
        // Implementation logic with concurrency control
    }
    
    /**
     * Update a character
     *
     * @since 1.0.0
     * @param int   $character_id Character ID.
     * @param array $data         Character data.
     * @return bool|WP_Error Success or error.
     */
    public function update_character($character_id, array $data) {
        // Implementation logic
    }
    
    /**
     * Delete a character
     *
     * @since 1.0.0
     * @param int $character_id Character ID.
     * @return bool|WP_Error Success or error.
     */
    public function delete_character($character_id) {
        // Implementation logic
    }
    
    /**
     * Create a character invention/gadget
     *
     * @since 1.0.0
     * @param int   $character_id   Character ID.
     * @param array $invention_data Invention data.
     * @return int|WP_Error Invention ID or error.
     */
    public function create_invention($character_id, array $invention_data) {
        // Implementation logic
    }
    
    /**
     * Get character inventions
     *
     * @since 1.0.0
     * @param int $character_id Character ID.
     * @return array List of inventions.
     */
    public function get_character_inventions($character_id) {
        // Implementation logic
    }
    
    /**
     * Check if a user can access a character
     *
     * @since 1.0.0
     * @param int $user_id      User ID.
     * @param int $character_id Character ID.
     * @return bool Can access.
     */
    public function can_user_access_character($user_id, $character_id) {
        // Implementation logic
    }
    
    /**
     * Get the maximum number of characters a user can have
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return int Character limit.
     */
    public function get_character_limit($user_id) {
        // Implementation logic
    }
    
    /**
     * Validate character attribute values according to d7 system
     *
     * @since 1.0.0
     * @param array $attributes Character attributes.
     * @return bool|WP_Error Valid or error.
     */
    public function validate_attributes(array $attributes) {
        // Implementation logic
    }
    
    /**
     * Validate character skill values according to d7 system
     *
     * @since 1.0.0
     * @param array $skills     Character skills.
     * @param array $attributes Character attributes.
     * @return bool|WP_Error Valid or error.
     */
    public function validate_skills(array $skills, array $attributes) {
        // Implementation logic
    }
    
    /**
     * Clear character cache
     *
     * @since 1.0.0
     * @param int $character_id Character ID.
     * @param int $user_id      User ID (optional).
     * @return void
     */
    public function clear_character_cache($character_id, $user_id = null) {
        // Implementation logic
    }
}
```

## Method Implementations

### Creating a Character

```php
/**
 * Create a new character
 *
 * @since 1.0.0
 * @param int   $user_id Owner user ID.
 * @param array $data    Character data.
 * @return int|WP_Error Character ID or error.
 */
public function create_character($user_id, array $data) {
    // Check character limit
    $user_characters = $this->get_user_characters($user_id);
    if (count($user_characters) >= $this->get_character_limit($user_id)) {
        return new WP_Error(
            'character_limit_reached',
            __('Maximum character limit reached.', 'rpg-suite')
        );
    }
    
    // Validate required fields
    if (empty($data['name'])) {
        return new WP_Error(
            'missing_character_name',
            __('Character name is required.', 'rpg-suite')
        );
    }
    
    // Validate attributes
    if (isset($data['attributes'])) {
        $attribute_validation = $this->validate_attributes($data['attributes']);
        if (is_wp_error($attribute_validation)) {
            return $attribute_validation;
        }
    } else {
        // Set default attributes
        $data['attributes'] = [
            'fortitude' => '2d7',
            'precision' => '2d7',
            'intellect' => '2d7',
            'charisma'  => '2d7',
        ];
    }
    
    // Validate skills if provided
    if (!empty($data['skills'])) {
        $skill_validation = $this->validate_skills($data['skills'], $data['attributes']);
        if (is_wp_error($skill_validation)) {
            return $skill_validation;
        }
    }
    
    // Prepare post data
    $post_data = [
        'post_title'   => sanitize_text_field($data['name']),
        'post_content' => isset($data['description']) ? wp_kses_post($data['description']) : '',
        'post_status'  => 'publish',
        'post_type'    => 'rpg_character',
        'post_author'  => $user_id,
    ];
    
    // Insert post
    $character_id = wp_insert_post($post_data, true);
    
    if (is_wp_error($character_id)) {
        return $character_id;
    }
    
    // Save attributes
    update_post_meta($character_id, '_rpg_attributes', $data['attributes']);
    
    // Save skills
    if (!empty($data['skills'])) {
        update_post_meta($character_id, '_rpg_skills', $data['skills']);
    }
    
    // Save class
    if (!empty($data['class'])) {
        update_post_meta($character_id, '_rpg_class', sanitize_text_field($data['class']));
    }
    
    // Save derived stats
    if (!empty($data['derived_stats'])) {
        update_post_meta($character_id, '_rpg_derived_stats', $data['derived_stats']);
    }
    
    // Initialize invention points and fate tokens
    update_post_meta($character_id, '_rpg_invention_points', isset($data['invention_points']) ? intval($data['invention_points']) : 5);
    update_post_meta($character_id, '_rpg_fate_tokens', isset($data['fate_tokens']) ? intval($data['fate_tokens']) : 1);
    
    // Make character active if it's the user's first character
    if (1 === count($user_characters)) {
        update_post_meta($character_id, '_rpg_active', true);
    } else {
        update_post_meta($character_id, '_rpg_active', false);
    }
    
    // Dispatch event
    $event_data = [
        'character_id' => $character_id,
        'user_id'      => $user_id,
    ];
    
    $this->event_dispatcher->dispatch('character_created', $event_data);
    
    return $character_id;
}
```

### Retrieving a Character

```php
/**
 * Get a character by ID
 *
 * @since 1.0.0
 * @param int $character_id Character ID.
 * @return WP_Post|null Character post or null.
 */
public function get_character($character_id) {
    $character = get_post($character_id);
    
    if (!$character || 'rpg_character' !== $character->post_type) {
        return null;
    }
    
    return $character;
}
```

### Getting User Characters

```php
/**
 * Get all characters for a user
 *
 * @since 1.0.0
 * @param int $user_id User ID.
 * @return WP_Post[] Array of character posts.
 */
public function get_user_characters($user_id) {
    // Check cache
    $cache_key = 'rpg_user_characters_' . $user_id;
    $cached_ids = wp_cache_get($cache_key, 'rpg_suite');
    
    if (false !== $cached_ids) {
        $characters = [];
        foreach ($cached_ids as $id) {
            $character = get_post($id);
            if ($character) {
                $characters[] = $character;
            }
        }
        return $characters;
    }
    
    $args = [
        'post_type'      => 'rpg_character',
        'author'         => $user_id,
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];
    
    $query = new WP_Query($args);
    $characters = $query->posts;
    
    // Cache the results
    $character_ids = array_map(function($post) {
        return $post->ID;
    }, $characters);
    
    wp_cache_set($cache_key, $character_ids, 'rpg_suite', 12 * HOUR_IN_SECONDS);
    
    return $characters;
}
```

### Getting Active Character

```php
/**
 * Get a user's active character
 *
 * @since 1.0.0
 * @param int $user_id User ID.
 * @return WP_Post|null Active character or null.
 */
public function get_active_character($user_id) {
    // Check transient cache first
    $cache_key = 'rpg_active_character_' . $user_id;
    $cached_id = get_transient($cache_key);
    
    if (false !== $cached_id) {
        $post = get_post($cached_id);
        if ($post && 'rpg_character' === $post->post_type) {
            return $post;
        }
    }
    
    // If not cached or invalid, run query
    $args = [
        'post_type'      => 'rpg_character',
        'author'         => $user_id,
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'   => '_rpg_active',
                'value' => true,
            ],
        ],
    ];
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        $character = $query->posts[0];
        // Cache for 12 hours
        set_transient($cache_key, $character->ID, 12 * HOUR_IN_SECONDS);
        return $character;
    }
    
    return null;
}
```

### Setting Active Character

```php
/**
 * Set a character as the user's active character
 *
 * Uses transient-based mutex for concurrency control.
 *
 * @since 1.0.0
 * @param int $user_id      User ID.
 * @param int $character_id Character ID.
 * @return bool|WP_Error Success or error.
 */
public function set_active_character($user_id, $character_id) {
    // Start transaction-like process with a mutex
    $mutex_key = 'rpg_character_activation_' . $user_id;
    $mutex = get_transient($mutex_key);
    
    if ($mutex) {
        return new WP_Error(
            'activation_in_progress',
            __('Another character activation is in progress. Please try again.', 'rpg-suite')
        );
    }
    
    // Set mutex with short expiration
    set_transient($mutex_key, true, 30);
    
    try {
        // Get all user's characters
        $user_characters = $this->get_user_characters($user_id);
        
        // Verify character ownership
        $character_found = false;
        foreach ($user_characters as $character) {
            if ($character->ID == $character_id) {
                $character_found = true;
                break;
            }
        }
        
        if (!$character_found) {
            delete_transient($mutex_key);
            return new WP_Error(
                'invalid_character',
                __('Character does not belong to this user.', 'rpg-suite')
            );
        }
        
        // Get previous active character
        $previous_active = $this->get_active_character($user_id);
        $previous_id = $previous_active ? $previous_active->ID : null;
        
        // Deactivate all characters
        foreach ($user_characters as $character) {
            update_post_meta($character->ID, '_rpg_active', false);
        }
        
        // Activate the selected character
        update_post_meta($character_id, '_rpg_active', true);
        
        // Clear caches
        $this->clear_character_cache($character_id, $user_id);
        
        // Dispatch event
        $event_data = [
            'character_id'         => $character_id,
            'user_id'              => $user_id,
            'previous_character_id' => $previous_id,
        ];
        
        $this->event_dispatcher->dispatch('character_activated', $event_data);
        
        // Release mutex
        delete_transient($mutex_key);
        
        return true;
    } catch (Exception $e) {
        // Release mutex in case of error
        delete_transient($mutex_key);
        return new WP_Error(
            'activation_failed',
            $e->getMessage()
        );
    }
}
```

### Validating Attributes and Skills

```php
/**
 * Validate character attribute values according to d7 system
 *
 * @since 1.0.0
 * @param array $attributes Character attributes.
 * @return bool|WP_Error Valid or error.
 */
public function validate_attributes(array $attributes) {
    $valid_attributes = ['fortitude', 'precision', 'intellect', 'charisma'];
    
    foreach ($attributes as $name => $value) {
        // Check that attribute exists
        if (!in_array($name, $valid_attributes, true)) {
            return new WP_Error(
                'invalid_attribute',
                sprintf(
                    /* translators: %s: attribute name */
                    __('Invalid attribute: %s', 'rpg-suite'),
                    $name
                )
            );
        }
        
        // Check die code format
        if (!$this->die_code_utility->is_valid_die_code($value)) {
            return new WP_Error(
                'invalid_die_code',
                sprintf(
                    /* translators: %s: die code */
                    __('Invalid die code format: %s', 'rpg-suite'),
                    $value
                )
            );
        }
        
        // Check minimum/maximum die values
        $parsed = $this->die_code_utility->parse_die_code($value);
        if ($parsed['dice'] < 1) {
            return new WP_Error(
                'invalid_die_code',
                __('Attributes must have at least 1 die.', 'rpg-suite')
            );
        }
        
        if ($parsed['dice'] > 10) {
            return new WP_Error(
                'invalid_die_code',
                __('Attributes cannot exceed 10 dice.', 'rpg-suite')
            );
        }
    }
    
    return true;
}

/**
 * Validate character skill values according to d7 system
 *
 * @since 1.0.0
 * @param array $skills     Character skills.
 * @param array $attributes Character attributes.
 * @return bool|WP_Error Valid or error.
 */
public function validate_skills(array $skills, array $attributes) {
    foreach ($skills as $name => $data) {
        // Check that skill has attribute and value
        if (!isset($data['attribute']) || !isset($data['value'])) {
            return new WP_Error(
                'invalid_skill_format',
                sprintf(
                    /* translators: %s: skill name */
                    __('Skill missing attribute or value: %s', 'rpg-suite'),
                    $name
                )
            );
        }
        
        // Check attribute exists
        if (!isset($attributes[$data['attribute']])) {
            return new WP_Error(
                'invalid_skill_attribute',
                sprintf(
                    /* translators: %s: attribute name */
                    __('Skill references non-existent attribute: %s', 'rpg-suite'),
                    $data['attribute']
                )
            );
        }
        
        // Check die code format
        if (!$this->die_code_utility->is_valid_die_code($data['value'])) {
            return new WP_Error(
                'invalid_skill_die_code',
                sprintf(
                    /* translators: %s: die code */
                    __('Invalid skill die code format: %s', 'rpg-suite'),
                    $data['value']
                )
            );
        }
        
        // Check skill doesn't exceed attribute by too much
        $skill_dice = $this->die_code_utility->parse_die_code($data['value'])['dice'];
        $attr_dice = $this->die_code_utility->parse_die_code($attributes[$data['attribute']])['dice'];
        
        if ($skill_dice > $attr_dice + 2) {
            return new WP_Error(
                'skill_exceeds_attribute',
                sprintf(
                    /* translators: %s: skill name */
                    __('Skill exceeds attribute by too much: %s', 'rpg-suite'),
                    $name
                )
            );
        }
    }
    
    return true;
}
```

### Cache Management

```php
/**
 * Clear character cache
 *
 * @since 1.0.0
 * @param int $character_id Character ID.
 * @param int $user_id      User ID (optional).
 * @return void
 */
public function clear_character_cache($character_id, $user_id = null) {
    // If user ID is not provided, get it from the character
    if (null === $user_id) {
        $character = get_post($character_id);
        if ($character) {
            $user_id = $character->post_author;
        }
    }
    
    // Clear active character cache
    if ($user_id) {
        delete_transient('rpg_active_character_' . $user_id);
        wp_cache_delete('rpg_user_characters_' . $user_id, 'rpg_suite');
    }
}
```

## Usage Examples

### Creating a Character

```php
// Get the Character Manager instance
global $rpg_suite;
$character_manager = $rpg_suite->get_character_manager();

// Create a character
$character_data = [
    'name'        => 'Professor Archibald Whistlebrook',
    'description' => 'A brilliant inventor with a fondness for steam-powered gadgets.',
    'class'       => 'mechwright',
    'attributes'  => [
        'fortitude' => '2d7+1',
        'precision' => '3d7',
        'intellect' => '4d7+2',
        'charisma'  => '2d7+1',
    ],
    'skills'      => [
        'Clockwork Mechanics' => [
            'attribute' => 'intellect',
            'value'     => '5d7',
        ],
        'Steam Engineering' => [
            'attribute' => 'intellect',
            'value'     => '4d7+2',
        ],
        'Aether Physics' => [
            'attribute' => 'intellect',
            'value'     => '4d7',
        ],
    ],
];

$character_id = $character_manager->create_character(get_current_user_id(), $character_data);

if (is_wp_error($character_id)) {
    echo $character_id->get_error_message();
} else {
    echo 'Character created successfully with ID: ' . $character_id;
}
```

### Setting Active Character

```php
// Get the Character Manager instance
global $rpg_suite;
$character_manager = $rpg_suite->get_character_manager();

// Set a character as active
$result = $character_manager->set_active_character(get_current_user_id(), $character_id);

if (is_wp_error($result)) {
    echo $result->get_error_message();
} else {
    echo 'Character activated successfully.';
}
```

### Updating a Character

```php
// Get the Character Manager instance
global $rpg_suite;
$character_manager = $rpg_suite->get_character_manager();

// Update character attributes
$update_data = [
    'attributes' => [
        'fortitude' => '3d7',  // Increased from 2d7+1
        'precision' => '3d7',
        'intellect' => '4d7+2',
        'charisma'  => '2d7+1',
    ],
];

$result = $character_manager->update_character($character_id, $update_data);

if (is_wp_error($result)) {
    echo $result->get_error_message();
} else {
    echo 'Character updated successfully.';
}
```

## Implementation Notes

1. All methods include proper error handling with descriptive error messages
2. Character validation ensures data integrity for the d7 system
3. Caching is implemented for frequently accessed data
4. Concurrency control prevents race conditions during character activation
5. Events are dispatched for major actions to allow integration with other components
6. All user inputs are properly sanitized before storage
7. Die code formatting and validation is delegated to the Die Code Utility
8. Character limit enforcement ensures users stay within their allowed limits