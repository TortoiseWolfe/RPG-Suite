# Admin Interface Specification

## Purpose
This specification defines the admin interface for managing RPG-Suite characters, inventions, and plugin settings.

## Requirements
1. Character creation and editing interface
2. Invention management interface 
3. Plugin settings page
4. Support for the d7 dice system in the UI
5. Easy character-to-user assignment

## Admin Pages Structure

```
RPG-Suite (top-level menu)
├── Characters (submenu)
│   ├── All Characters
│   └── Add New
├── Inventions (submenu)
│   ├── All Inventions
│   └── Add New
├── Character Classes (submenu)
│   ├── All Classes
│   └── Add New
└── Settings (submenu)
```

## Component Structure

### Admin Main Class

```php
/**
 * Main admin class for RPG-Suite
 */
class RPG_Suite_Admin {
    /**
     * @var Character_Manager
     */
    private $character_manager;
    
    /**
     * @var Die_Code_Utility
     */
    private $die_code_utility;
    
    /**
     * Constructor
     * 
     * @param Character_Manager $character_manager
     * @param Die_Code_Utility $die_code_utility
     */
    public function __construct(Character_Manager $character_manager, Die_Code_Utility $die_code_utility) {
        $this->character_manager = $character_manager;
        $this->die_code_utility = $die_code_utility;
    }
    
    /**
     * Initialize admin hooks
     * 
     * @return void
     */
    public function init() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post_rpg_character', array($this, 'save_character_meta'));
        add_action('save_post_rpg_invention', array($this, 'save_invention_meta'));
        add_filter('manage_rpg_character_posts_columns', array($this, 'character_columns'));
        add_action('manage_rpg_character_posts_custom_column', array($this, 'character_column_content'), 10, 2);
    }
    
    /**
     * Register admin menu pages
     * 
     * @return void
     */
    public function register_admin_menu() {
        // Implementation logic
    }
    
    /**
     * Enqueue admin assets
     * 
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Implementation logic
    }
    
    /**
     * Register meta boxes
     * 
     * @return void
     */
    public function register_meta_boxes() {
        // Implementation logic
    }
    
    /**
     * Save character meta
     * 
     * @param int $post_id Character ID
     * @return void
     */
    public function save_character_meta($post_id) {
        // Implementation logic
    }
    
    /**
     * Save invention meta
     * 
     * @param int $post_id Invention ID
     * @return void
     */
    public function save_invention_meta($post_id) {
        // Implementation logic
    }
    
    /**
     * Customize character list columns
     * 
     * @param array $columns
     * @return array
     */
    public function character_columns($columns) {
        // Implementation logic
    }
    
    /**
     * Display character column content
     * 
     * @param string $column
     * @param int $post_id
     * @return void
     */
    public function character_column_content($column, $post_id) {
        // Implementation logic
    }
}
```

### Character Editor Class

```php
/**
 * Character editing interface
 */
class RPG_Suite_Character_Editor {
    /**
     * @var Die_Code_Utility
     */
    private $die_code_utility;
    
    /**
     * Constructor
     * 
     * @param Die_Code_Utility $die_code_utility
     */
    public function __construct(Die_Code_Utility $die_code_utility) {
        $this->die_code_utility = $die_code_utility;
    }
    
    /**
     * Display character attributes meta box
     * 
     * @param WP_Post $post
     * @return void
     */
    public function attributes_meta_box($post) {
        // Implementation logic
    }
    
    /**
     * Display character skills meta box
     * 
     * @param WP_Post $post
     * @return void
     */
    public function skills_meta_box($post) {
        // Implementation logic
    }
    
    /**
     * Display character owner meta box
     * 
     * @param WP_Post $post
     * @return void
     */
    public function owner_meta_box($post) {
        // Implementation logic
    }
}
```

### Settings Class

```php
/**
 * Plugin settings page
 */
class RPG_Suite_Settings {
    /**
     * Register settings
     * 
     * @return void
     */
    public function register_settings() {
        // Implementation logic
    }
    
    /**
     * Display settings page
     * 
     * @return void
     */
    public function render_settings_page() {
        // Implementation logic
    }
    
    /**
     * Render settings fields
     * 
     * @return void
     */
    public function render_settings_fields() {
        // Implementation logic
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        // Implementation logic
    }
}
```

## Character Edit Screen

The character edit screen will include custom meta boxes for d7-based attributes:

```php
public function attributes_meta_box($post) {
    // Get saved attributes
    $attributes = get_post_meta($post->ID, '_rpg_attributes', true);
    if (!is_array($attributes)) {
        $attributes = array(
            'fortitude' => '2d7',
            'precision' => '2d7',
            'intellect' => '2d7',
            'charisma' => '2d7'
        );
    }
    
    wp_nonce_field('rpg_character_attributes', 'rpg_character_attributes_nonce');
    
    echo '<table class="form-table rpg-attributes-table">';
    echo '<tr>';
    echo '<th scope="row">' . __('Attribute Name', 'rpg-suite') . '</th>';
    echo '<th scope="row">' . __('Die Code', 'rpg-suite') . '</th>';
    echo '<th scope="row">' . __('Roll', 'rpg-suite') . '</th>';
    echo '</tr>';
    
    foreach ($attributes as $name => $value) {
        echo '<tr>';
        echo '<th scope="row"><label for="rpg_attr_' . esc_attr($name) . '">' . esc_html(ucfirst($name)) . '</label></th>';
        echo '<td>';
        echo '<input type="text" id="rpg_attr_' . esc_attr($name) . '" name="rpg_attributes[' . esc_attr($name) . ']" value="' . esc_attr($value) . '" class="regular-text rpg-die-code" />';
        echo '</td>';
        echo '<td>';
        echo '<button type="button" class="button rpg-roll-button" data-die-code="' . esc_attr($value) . '">' . __('Roll', 'rpg-suite') . '</button>';
        echo '<span class="rpg-roll-result"></span>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    echo '<p class="description">' . __('Enter die codes in the format "XdY+Z" (e.g., "3d7+2").', 'rpg-suite') . '</p>';
}
```

### Skills Meta Box

```php
public function skills_meta_box($post) {
    // Get saved skills
    $skills = get_post_meta($post->ID, '_rpg_skills', true);
    if (!is_array($skills)) {
        $skills = array();
    }
    
    // Get attributes for dropdown
    $attributes = get_post_meta($post->ID, '_rpg_attributes', true);
    if (!is_array($attributes)) {
        $attributes = array(
            'fortitude' => '2d7',
            'precision' => '2d7',
            'intellect' => '2d7',
            'charisma' => '2d7'
        );
    }
    
    wp_nonce_field('rpg_character_skills', 'rpg_character_skills_nonce');
    
    echo '<div id="rpg-skills-container">';
    echo '<table class="form-table rpg-skills-table">';
    echo '<tr>';
    echo '<th scope="row">' . __('Skill Name', 'rpg-suite') . '</th>';
    echo '<th scope="row">' . __('Attribute', 'rpg-suite') . '</th>';
    echo '<th scope="row">' . __('Die Code', 'rpg-suite') . '</th>';
    echo '<th></th>';
    echo '</tr>';
    
    // Existing skills
    if (!empty($skills)) {
        foreach ($skills as $name => $data) {
            echo '<tr class="rpg-skill-row">';
            echo '<td><input type="text" name="rpg_skill_names[]" value="' . esc_attr($name) . '" class="regular-text" /></td>';
            
            echo '<td><select name="rpg_skill_attributes[]">';
            foreach ($attributes as $attr_name => $attr_value) {
                $selected = ($data['attribute'] === $attr_name) ? 'selected' : '';
                echo '<option value="' . esc_attr($attr_name) . '" ' . $selected . '>' . esc_html(ucfirst($attr_name)) . '</option>';
            }
            echo '</select></td>';
            
            echo '<td><input type="text" name="rpg_skill_values[]" value="' . esc_attr($data['value']) . '" class="regular-text rpg-die-code" /></td>';
            echo '<td><button type="button" class="button rpg-remove-skill">' . __('Remove', 'rpg-suite') . '</button></td>';
            echo '</tr>';
        }
    }
    
    // Template row for new skills (hidden by default)
    echo '<tr class="rpg-skill-row rpg-skill-template" style="display:none;">';
    echo '<td><input type="text" name="rpg_skill_names[]" value="" class="regular-text" /></td>';
    
    echo '<td><select name="rpg_skill_attributes[]">';
    foreach ($attributes as $attr_name => $attr_value) {
        echo '<option value="' . esc_attr($attr_name) . '">' . esc_html(ucfirst($attr_name)) . '</option>';
    }
    echo '</select></td>';
    
    echo '<td><input type="text" name="rpg_skill_values[]" value="2d7" class="regular-text rpg-die-code" /></td>';
    echo '<td><button type="button" class="button rpg-remove-skill">' . __('Remove', 'rpg-suite') . '</button></td>';
    echo '</tr>';
    
    echo '</table>';
    
    echo '<button type="button" class="button rpg-add-skill">' . __('Add Skill', 'rpg-suite') . '</button>';
    echo '</div>';
    
    echo '<p class="description">' . __('Skills are specializations of attributes with their own die codes.', 'rpg-suite') . '</p>';
}
```

### Character Owner Meta Box

```php
public function owner_meta_box($post) {
    // Get current owner
    $current_owner = $post->post_author;
    
    // Get users who can own characters
    $users = get_users(array(
        'role__in' => array('administrator', 'editor', 'author', 'subscriber')
    ));
    
    wp_nonce_field('rpg_character_owner', 'rpg_character_owner_nonce');
    
    echo '<select name="post_author_override" id="post_author_override">';
    foreach ($users as $user) {
        $selected = ($user->ID == $current_owner) ? 'selected' : '';
        echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . ' (' . esc_html($user->user_login) . ')</option>';
    }
    echo '</select>';
    
    // Character limit info
    $character_count = count_user_posts($current_owner, 'rpg_character');
    $character_limit = $this->character_manager->get_character_limit($current_owner);
    
    echo '<p class="description">';
    printf(
        __('This user currently has %1$d of %2$d allowed characters.', 'rpg-suite'),
        $character_count,
        $character_limit
    );
    echo '</p>';
    
    // Active character status
    $is_active = get_post_meta($post->ID, '_rpg_active', true);
    
    echo '<p>';
    echo '<label for="rpg_active">';
    echo '<input type="checkbox" name="rpg_active" id="rpg_active" value="1" ' . checked($is_active, true, false) . ' />';
    echo __('Set as active character', 'rpg-suite');
    echo '</label>';
    echo '</p>';
    
    echo '<p class="description">' . __('If checked, this will become the user\'s active character, replacing any previously active character.', 'rpg-suite') . '</p>';
}
```

## Invention Edit Screen

```php
public function inventions_meta_box($post) {
    // Get saved invention data
    $complexity = get_post_meta($post->ID, '_rpg_complexity', true);
    $components = get_post_meta($post->ID, '_rpg_components', true);
    $effects = get_post_meta($post->ID, '_rpg_effects', true);
    $character_id = get_post_meta($post->ID, '_rpg_character_id', true);
    
    if (!is_array($components)) {
        $components = array();
    }
    
    wp_nonce_field('rpg_invention_data', 'rpg_invention_data_nonce');
    
    echo '<table class="form-table">';
    
    // Inventor character
    echo '<tr>';
    echo '<th scope="row"><label for="rpg_character_id">' . __('Inventor Character', 'rpg-suite') . '</label></th>';
    echo '<td>';
    
    // Get available characters
    $characters = get_posts(array(
        'post_type' => 'rpg_character',
        'posts_per_page' => -1,
    ));
    
    if (!empty($characters)) {
        echo '<select name="rpg_character_id" id="rpg_character_id">';
        echo '<option value="">' . __('Select a character', 'rpg-suite') . '</option>';
        
        foreach ($characters as $character) {
            $selected = selected($character->ID, $character_id, false);
            echo '<option value="' . esc_attr($character->ID) . '" ' . $selected . '>' . esc_html($character->post_title) . '</option>';
        }
        
        echo '</select>';
    } else {
        echo __('No characters available', 'rpg-suite');
    }
    
    echo '</td>';
    echo '</tr>';
    
    // Complexity
    echo '<tr>';
    echo '<th scope="row"><label for="rpg_complexity">' . __('Complexity', 'rpg-suite') . '</label></th>';
    echo '<td>';
    echo '<input type="number" name="rpg_complexity" id="rpg_complexity" value="' . esc_attr($complexity) . '" class="small-text" min="1" max="30" />';
    echo '<p class="description">' . __('The difficulty level of creating this invention (1-30).', 'rpg-suite') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    // Components
    echo '<tr>';
    echo '<th scope="row">' . __('Components', 'rpg-suite') . '</th>';
    echo '<td id="rpg-components-container">';
    
    if (!empty($components)) {
        foreach ($components as $component) {
            echo '<div class="rpg-component-row">';
            echo '<input type="text" name="rpg_components[]" value="' . esc_attr($component) . '" class="regular-text" />';
            echo '<button type="button" class="button rpg-remove-component">' . __('Remove', 'rpg-suite') . '</button>';
            echo '</div>';
        }
    }
    
    echo '<div class="rpg-component-row">';
    echo '<input type="text" name="rpg_components[]" value="" class="regular-text" />';
    echo '<button type="button" class="button rpg-remove-component">' . __('Remove', 'rpg-suite') . '</button>';
    echo '</div>';
    
    echo '<button type="button" class="button rpg-add-component">' . __('Add Component', 'rpg-suite') . '</button>';
    echo '<p class="description">' . __('The components required to create this invention.', 'rpg-suite') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    // Effects
    echo '<tr>';
    echo '<th scope="row"><label for="rpg_effects">' . __('Effects', 'rpg-suite') . '</label></th>';
    echo '<td>';
    echo '<textarea name="rpg_effects" id="rpg_effects" class="large-text" rows="5">' . esc_textarea($effects) . '</textarea>';
    echo '<p class="description">' . __('The effects of this invention when used.', 'rpg-suite') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
}
```

## Settings Page

```php
public function render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save settings if submitted
    if (isset($_POST['rpg_suite_settings_nonce']) && wp_verify_nonce($_POST['rpg_suite_settings_nonce'], 'rpg_suite_settings')) {
        update_option('rpg_suite_character_limit', absint($_POST['rpg_suite_character_limit']));
        update_option('rpg_suite_dice_animation', isset($_POST['rpg_suite_dice_animation']) ? 1 : 0);
        update_option('rpg_suite_remove_data_on_uninstall', isset($_POST['rpg_suite_remove_data_on_uninstall']) ? 1 : 0);
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved.', 'rpg-suite') . '</p></div>';
    }
    
    // Get current settings
    $character_limit = get_option('rpg_suite_character_limit', 2);
    $dice_animation = get_option('rpg_suite_dice_animation', 1);
    $remove_data = get_option('rpg_suite_remove_data_on_uninstall', 0);
    
    echo '<div class="wrap">';
    echo '<h1>' . __('RPG-Suite Settings', 'rpg-suite') . '</h1>';
    
    echo '<form method="post" action="">';
    wp_nonce_field('rpg_suite_settings', 'rpg_suite_settings_nonce');
    
    echo '<table class="form-table">';
    
    // Character limit
    echo '<tr>';
    echo '<th scope="row"><label for="rpg_suite_character_limit">' . __('Default Character Limit', 'rpg-suite') . '</label></th>';
    echo '<td>';
    echo '<input type="number" name="rpg_suite_character_limit" id="rpg_suite_character_limit" value="' . esc_attr($character_limit) . '" class="small-text" min="1" max="10" />';
    echo '<p class="description">' . __('Maximum number of characters a user can create by default.', 'rpg-suite') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    // Dice animation
    echo '<tr>';
    echo '<th scope="row">' . __('Dice Animation', 'rpg-suite') . '</th>';
    echo '<td>';
    echo '<label for="rpg_suite_dice_animation">';
    echo '<input type="checkbox" name="rpg_suite_dice_animation" id="rpg_suite_dice_animation" value="1" ' . checked($dice_animation, 1, false) . ' />';
    echo __('Enable dice rolling animation', 'rpg-suite');
    echo '</label>';
    echo '<p class="description">' . __('Show animation when dice are rolled.', 'rpg-suite') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    // Data removal
    echo '<tr>';
    echo '<th scope="row">' . __('Plugin Uninstallation', 'rpg-suite') . '</th>';
    echo '<td>';
    echo '<label for="rpg_suite_remove_data_on_uninstall">';
    echo '<input type="checkbox" name="rpg_suite_remove_data_on_uninstall" id="rpg_suite_remove_data_on_uninstall" value="1" ' . checked($remove_data, 1, false) . ' />';
    echo __('Remove all plugin data when uninstalling', 'rpg-suite');
    echo '</label>';
    echo '<p class="description">' . __('Warning: This will delete all characters and inventions when the plugin is deleted.', 'rpg-suite') . '</p>';
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
    
    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="' . __('Save Changes', 'rpg-suite') . '" />';
    echo '</p>';
    
    echo '</form>';
    echo '</div>';
}
```

## JavaScript for Dice Rolling

```javascript
jQuery(document).ready(function($) {
    // Die code validation
    function isValidDieCode(dieCode) {
        return /^\d+d7([+-]\d+)?$/i.test(dieCode);
    }
    
    // Die code input validation
    $('.rpg-die-code').on('change', function() {
        const dieCode = $(this).val();
        if (!isValidDieCode(dieCode)) {
            alert('Invalid die code format. Please use the format "XdY+Z" (e.g., "3d7+2").');
            $(this).val('2d7');
        }
    });
    
    // Dice rolling
    $('.rpg-roll-button').on('click', function() {
        const dieCode = $(this).data('die-code');
        const resultContainer = $(this).siblings('.rpg-roll-result');
        
        // Update the die code from the input if changed
        const dieCodeInput = $(this).closest('tr').find('.rpg-die-code');
        if (dieCodeInput.length) {
            $(this).data('die-code', dieCodeInput.val());
        }
        
        // Parse die code
        const dieCodePattern = /^(\d+)d7(?:([+-])(\d+))?$/i;
        const matches = dieCode.match(dieCodePattern);
        
        if (!matches) {
            resultContainer.text('Invalid die code');
            return;
        }
        
        const numDice = parseInt(matches[1], 10);
        let modifier = 0;
        
        if (matches[2] && matches[3]) {
            modifier = parseInt(matches[3], 10);
            if (matches[2] === '-') {
                modifier = -modifier;
            }
        }
        
        // Roll the dice
        let rolls = [];
        let total = 0;
        
        for (let i = 0; i < numDice; i++) {
            const roll = Math.floor(Math.random() * 7) + 1;
            rolls.push(roll);
            total += roll;
        }
        
        total += modifier;
        
        // Display results
        let resultText = 'Rolled ' + numDice + 'd7';
        if (modifier !== 0) {
            resultText += modifier > 0 ? '+' + modifier : modifier;
        }
        resultText += ': [' + rolls.join(', ') + ']';
        if (modifier !== 0) {
            resultText += ' ' + (modifier > 0 ? '+' : '') + modifier;
        }
        resultText += ' = ' + total;
        
        resultContainer.text(resultText);
    });
    
    // Add skill
    $('.rpg-add-skill').on('click', function() {
        const template = $('.rpg-skill-template').clone();
        template.removeClass('rpg-skill-template').show();
        $(this).closest('#rpg-skills-container').find('table').append(template);
    });
    
    // Remove skill
    $(document).on('click', '.rpg-remove-skill', function() {
        // Don't remove if it's the only visible row
        const visibleRows = $(this).closest('table').find('.rpg-skill-row:visible');
        if (visibleRows.length > 1) {
            $(this).closest('.rpg-skill-row').remove();
        } else {
            $(this).closest('.rpg-skill-row').find('input[type="text"]').val('');
        }
    });
    
    // Add component
    $('.rpg-add-component').on('click', function() {
        const template = $('.rpg-component-row:last').clone();
        template.find('input').val('');
        $('#rpg-components-container').append(template);
    });
    
    // Remove component
    $(document).on('click', '.rpg-remove-component', function() {
        // Don't remove if it's the only row
        const rows = $('.rpg-component-row');
        if (rows.length > 1) {
            $(this).closest('.rpg-component-row').remove();
        } else {
            $(this).closest('.rpg-component-row').find('input').val('');
        }
    });
});
```

## Implementation Notes

1. **Accessibility**: All admin screens follow WordPress accessibility guidelines
2. **Security**: All inputs are properly validated and sanitized
3. **Nonce Verification**: All form submissions include nonce verification
4. **Capability Checks**: User capabilities are checked before displaying admin pages
5. **Consistent Styling**: UI elements match WordPress admin styles
6. **Internationalization**: All strings are properly localized
7. **Admin Notices**: Success/error messages displayed using WordPress admin notices
8. **Script Loading**: Admin scripts only loaded on relevant plugin pages