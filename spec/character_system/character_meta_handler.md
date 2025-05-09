# Character Meta Handler Specification

## Purpose
The Character Meta Handler is responsible for managing character metadata, including saving, retrieving, and validating character attributes, skills, and other properties specific to the d7 system.

## Requirements
1. Save character metadata in a consistent format
2. Retrieve character metadata with proper defaults
3. Validate character data according to d7 system rules
4. Handle format conversion between database and application formats
5. Provide sanitization for all character data
6. Support caching for efficient data access

## Class Definition

```php
/**
 * Character meta data handling
 *
 * @since 1.0.0
 */
class RPG_Suite_Character_Meta_Handler {
    /**
     * Die code utility
     *
     * @since 1.0.0
     * @var RPG_Suite_Die_Code_Utility
     */
    private $die_code_utility;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     * @param RPG_Suite_Die_Code_Utility $die_code_utility Die code utility.
     */
    public function __construct(RPG_Suite_Die_Code_Utility $die_code_utility) {
        $this->die_code_utility = $die_code_utility;
    }
    
    /**
     * Save character meta data
     *
     * @since 1.0.0
     * @param int   $post_id Character post ID.
     * @param array $data    Character data to save.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function save_character_meta($post_id, array $data) {
        // Implementation logic
    }
    
    /**
     * Get character meta data
     *
     * @since 1.0.0
     * @param int  $post_id Character post ID.
     * @param bool $use_cache Whether to use cached data.
     * @return array Character meta data.
     */
    public function get_character_meta($post_id, $use_cache = true) {
        // Implementation logic
    }
    
    /**
     * Validate character data
     *
     * @since 1.0.0
     * @param array $data Character data to validate.
     * @return bool|WP_Error True if valid, WP_Error if invalid.
     */
    public function validate_character_data(array $data) {
        // Implementation logic
    }
    
    /**
     * Get default attributes
     *
     * @since 1.0.0
     * @param string $class Optional character class.
     * @return array Default attributes.
     */
    public function get_default_attributes($class = '') {
        // Implementation logic
    }
    
    /**
     * Get default skills
     *
     * @since 1.0.0
     * @param string $class Optional character class.
     * @return array Default skills.
     */
    public function get_default_skills($class = '') {
        // Implementation logic
    }
    
    /**
     * Format character data for display
     *
     * @since 1.0.0
     * @param array $data Raw character data.
     * @return array Formatted character data.
     */
    public function format_character_data(array $data) {
        // Implementation logic
    }
    
    /**
     * Clear character meta cache
     *
     * @since 1.0.0
     * @param int $post_id Character post ID.
     * @return void
     */
    public function clear_cache($post_id) {
        // Implementation logic
    }
}
```

## Method Implementations

### Saving Character Meta

```php
/**
 * Save character meta data
 *
 * @since 1.0.0
 * @param int   $post_id Character post ID.
 * @param array $data    Character data to save.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
public function save_character_meta($post_id, array $data) {
    // Ensure we have a valid post ID
    $post = get_post($post_id);
    if (!$post || 'rpg_character' !== $post->post_type) {
        return new WP_Error(
            'invalid_post',
            __('Invalid character post.', 'rpg-suite')
        );
    }
    
    // Validate data
    $validation = $this->validate_character_data($data);
    if (is_wp_error($validation)) {
        return $validation;
    }
    
    // Start saving data
    $updated = array();
    
    // Save attributes
    if (isset($data['attributes'])) {
        $attributes = $this->sanitize_attributes($data['attributes']);
        update_post_meta($post_id, '_rpg_attributes', $attributes);
        $updated[] = 'attributes';
    }
    
    // Save skills
    if (isset($data['skills'])) {
        $skills = $this->sanitize_skills($data['skills']);
        update_post_meta($post_id, '_rpg_skills', $skills);
        $updated[] = 'skills';
    }
    
    // Save class
    if (isset($data['class'])) {
        $class = sanitize_text_field($data['class']);
        update_post_meta($post_id, '_rpg_class', $class);
        $updated[] = 'class';
    }
    
    // Save active status
    if (isset($data['active'])) {
        $active = (bool) $data['active'];
        update_post_meta($post_id, '_rpg_active', $active);
        $updated[] = 'active';
    }
    
    // Save invention points
    if (isset($data['invention_points'])) {
        $points = absint($data['invention_points']);
        update_post_meta($post_id, '_rpg_invention_points', $points);
        $updated[] = 'invention_points';
    }
    
    // Save fate tokens
    if (isset($data['fate_tokens'])) {
        $tokens = absint($data['fate_tokens']);
        update_post_meta($post_id, '_rpg_fate_tokens', $tokens);
        $updated[] = 'fate_tokens';
    }
    
    // Save derived stats
    if (isset($data['derived_stats'])) {
        $stats = $this->sanitize_derived_stats($data['derived_stats']);
        update_post_meta($post_id, '_rpg_derived_stats', $stats);
        $updated[] = 'derived_stats';
    }
    
    // Clear cache
    $this->clear_cache($post_id);
    
    return true;
}
```

### Getting Character Meta

```php
/**
 * Get character meta data
 *
 * @since 1.0.0
 * @param int  $post_id Character post ID.
 * @param bool $use_cache Whether to use cached data.
 * @return array Character meta data.
 */
public function get_character_meta($post_id, $use_cache = true) {
    // Check cache first if enabled
    if ($use_cache) {
        $cache_key = 'rpg_character_meta_' . $post_id;
        $cached_data = wp_cache_get($cache_key, 'rpg_suite');
        
        if (false !== $cached_data) {
            return $cached_data;
        }
    }
    
    // Get character post
    $post = get_post($post_id);
    if (!$post || 'rpg_character' !== $post->post_type) {
        return array();
    }
    
    // Get all meta data
    $attributes = get_post_meta($post_id, '_rpg_attributes', true);
    $skills = get_post_meta($post_id, '_rpg_skills', true);
    $class = get_post_meta($post_id, '_rpg_class', true);
    $active = get_post_meta($post_id, '_rpg_active', true);
    $invention_points = get_post_meta($post_id, '_rpg_invention_points', true);
    $fate_tokens = get_post_meta($post_id, '_rpg_fate_tokens', true);
    $derived_stats = get_post_meta($post_id, '_rpg_derived_stats', true);
    
    // Set defaults if needed
    if (!is_array($attributes) || empty($attributes)) {
        $attributes = $this->get_default_attributes($class);
    }
    
    if (!is_array($skills)) {
        $skills = $this->get_default_skills($class);
    }
    
    if ('' === $invention_points) {
        $invention_points = 5; // Default starting points
    }
    
    if ('' === $fate_tokens) {
        $fate_tokens = 1; // Default starting tokens
    }
    
    if (!is_array($derived_stats)) {
        $derived_stats = $this->calculate_derived_stats($attributes);
    }
    
    // Compile data
    $data = array(
        'id'               => $post_id,
        'name'             => $post->post_title,
        'description'      => $post->post_content,
        'owner_id'         => $post->post_author,
        'attributes'       => $attributes,
        'skills'           => $skills,
        'class'            => $class,
        'active'           => (bool) $active,
        'invention_points' => (int) $invention_points,
        'fate_tokens'      => (int) $fate_tokens,
        'derived_stats'    => $derived_stats,
    );
    
    // Cache the result if caching is enabled
    if ($use_cache) {
        wp_cache_set($cache_key, $data, 'rpg_suite', 12 * HOUR_IN_SECONDS);
    }
    
    return $data;
}
```

### Getting Default Attributes

```php
/**
 * Get default attributes
 *
 * @since 1.0.0
 * @param string $class Optional character class.
 * @return array Default attributes.
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
    
    /**
     * Filter default character attributes
     *
     * @since 1.0.0
     * @param array  $attributes Default attributes.
     * @param string $class      Character class.
     */
    return apply_filters('rpg_suite_default_attributes', $attributes, $class);
}
```

### Calculating Derived Stats

```php
/**
 * Calculate derived stats from attributes
 *
 * @since 1.0.0
 * @param array $attributes Character attributes.
 * @return array Derived stats.
 */
private function calculate_derived_stats(array $attributes) {
    $stats = array();
    
    // Parse attribute die codes
    $fortitude_parsed = $this->die_code_utility->parse_die_code($attributes['fortitude']);
    $precision_parsed = $this->die_code_utility->parse_die_code($attributes['precision']);
    $intellect_parsed = $this->die_code_utility->parse_die_code($attributes['intellect']);
    $charisma_parsed = $this->die_code_utility->parse_die_code($attributes['charisma']);
    
    // Calculate Vitality (based on Fortitude)
    $stats['vitality'] = ($fortitude_parsed['dice'] * 5) + $fortitude_parsed['modifier'];
    
    // Calculate Movement (based on Precision)
    $stats['movement'] = 5 + $precision_parsed['dice'];
    
    // Calculate Initiative (based on Intellect and Precision)
    $stats['initiative'] = $precision_parsed['dice'] + floor($intellect_parsed['dice'] / 2);
    
    // Calculate Will (based on Intellect and Charisma)
    $stats['will'] = $intellect_parsed['dice'] + floor($charisma_parsed['dice'] / 2);
    
    /**
     * Filter derived character stats
     *
     * @since 1.0.0
     * @param array $stats      Derived stats.
     * @param array $attributes Character attributes.
     */
    return apply_filters('rpg_suite_derived_stats', $stats, $attributes);
}
```

### Validating Character Data

```php
/**
 * Validate character data
 *
 * @since 1.0.0
 * @param array $data Character data to validate.
 * @return bool|WP_Error True if valid, WP_Error if invalid.
 */
public function validate_character_data(array $data) {
    // Validate attributes
    if (isset($data['attributes'])) {
        $valid_attributes = array('fortitude', 'precision', 'intellect', 'charisma');
        
        foreach ($data['attributes'] as $key => $value) {
            // Check that attribute exists
            if (!in_array($key, $valid_attributes, true)) {
                return new WP_Error(
                    'invalid_attribute',
                    sprintf(
                        /* translators: %s: attribute name */
                        __('Invalid attribute: %s', 'rpg-suite'),
                        $key
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
        }
    }
    
    // Validate skills
    if (isset($data['skills']) && isset($data['attributes'])) {
        foreach ($data['skills'] as $name => $skill_data) {
            // Check that skill has attribute and value
            if (!isset($skill_data['attribute']) || !isset($skill_data['value'])) {
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
            if (!isset($data['attributes'][$skill_data['attribute']])) {
                return new WP_Error(
                    'invalid_skill_attribute',
                    sprintf(
                        /* translators: %s: attribute name */
                        __('Skill references non-existent attribute: %s', 'rpg-suite'),
                        $skill_data['attribute']
                    )
                );
            }
            
            // Check die code format
            if (!$this->die_code_utility->is_valid_die_code($skill_data['value'])) {
                return new WP_Error(
                    'invalid_skill_die_code',
                    sprintf(
                        /* translators: %s: die code */
                        __('Invalid skill die code format: %s', 'rpg-suite'),
                        $skill_data['value']
                    )
                );
            }
        }
    }
    
    // Validate class
    if (isset($data['class'])) {
        $valid_classes = array('aeronaut', 'mechwright', 'aethermancer', 'diplomat', '');
        
        if (!in_array($data['class'], $valid_classes, true)) {
            return new WP_Error(
                'invalid_class',
                sprintf(
                    /* translators: %s: class name */
                    __('Invalid character class: %s', 'rpg-suite'),
                    $data['class']
                )
            );
        }
    }
    
    return true;
}
```

### Data Sanitization

```php
/**
 * Sanitize attributes
 *
 * @since 1.0.0
 * @param array $attributes Attributes to sanitize.
 * @return array Sanitized attributes.
 */
private function sanitize_attributes($attributes) {
    if (!is_array($attributes)) {
        return $this->get_default_attributes();
    }
    
    $sanitized = array();
    $valid_keys = array('fortitude', 'precision', 'intellect', 'charisma');
    
    foreach ($valid_keys as $key) {
        if (isset($attributes[$key]) && $this->die_code_utility->is_valid_die_code($attributes[$key])) {
            $sanitized[$key] = $attributes[$key];
        } else {
            // Use default if invalid
            $default_attributes = $this->get_default_attributes();
            $sanitized[$key] = $default_attributes[$key];
        }
    }
    
    return $sanitized;
}

/**
 * Sanitize skills
 *
 * @since 1.0.0
 * @param array $skills Skills to sanitize.
 * @return array Sanitized skills.
 */
private function sanitize_skills($skills) {
    if (!is_array($skills)) {
        return array();
    }
    
    $sanitized = array();
    
    foreach ($skills as $key => $data) {
        $sanitized_key = sanitize_text_field($key);
        
        if (!isset($data['attribute']) || !isset($data['value'])) {
            continue;
        }
        
        $attribute = sanitize_text_field($data['attribute']);
        $value = $data['value'];
        
        // Validate die code format
        if ($this->die_code_utility->is_valid_die_code($value)) {
            $sanitized[$sanitized_key] = array(
                'attribute' => $attribute,
                'value'     => $value,
            );
        }
    }
    
    return $sanitized;
}

/**
 * Sanitize derived stats
 *
 * @since 1.0.0
 * @param array $stats Stats to sanitize.
 * @return array Sanitized stats.
 */
private function sanitize_derived_stats($stats) {
    if (!is_array($stats)) {
        return array();
    }
    
    $sanitized = array();
    $valid_keys = array('vitality', 'movement', 'initiative', 'will');
    
    foreach ($valid_keys as $key) {
        if (isset($stats[$key])) {
            $sanitized[$key] = absint($stats[$key]);
        }
    }
    
    return $sanitized;
}
```

## Usage Example

```php
// Get the Character Meta Handler instance
global $rpg_suite;
$meta_handler = $rpg_suite->get_character_meta_handler();

// Get character data
$character_data = $meta_handler->get_character_meta($character_id);

// Update character data
$new_data = array(
    'attributes' => array(
        'fortitude' => '3d7', // Increased from 2d7+1
    ),
    'invention_points' => 10, // Updated points
);

$result = $meta_handler->save_character_meta($character_id, $new_data);

if (is_wp_error($result)) {
    echo $result->get_error_message();
} else {
    echo 'Character updated successfully.';
}
```

## Integration with Other Components

The Character Meta Handler integrates with:

1. **Character Manager** - Provides data layer for manager operations
2. **Die Code Utility** - Validates and manipulates d7 die codes
3. **WordPress Cache API** - Caches character data for performance
4. **WordPress Meta API** - Stores and retrieves post meta data

## Implementation Notes

1. All character data is properly validated before saving
2. Default values are provided for required fields
3. Caching improves performance for frequently accessed characters
4. Derived stats are calculated from attributes automatically
5. The class includes hook points for customization and extension
6. Strict sanitization ensures data integrity
7. Error handling follows WordPress patterns