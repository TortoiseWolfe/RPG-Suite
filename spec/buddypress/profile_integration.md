# BuddyPress Profile Integration Specification

## Purpose
This specification defines how RPG-Suite integrates with BuddyPress to display character information on user profiles and provide character management functionality.

## Requirements
1. Display active character information in BuddyPress profile header
2. Provide character switching interface on profile
3. Ensure compatibility with BuddyX theme
4. Register hooks at appropriate times in BuddyPress lifecycle
5. Make character data accessible through global plugin instance
6. Display d7 system attributes and skills

## Component Structure

### Profile Display Class

```php
/**
 * BuddyPress profile display integration
 */
class Profile_Display {
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
     * Register hooks
     * 
     * @return void
     */
    public function register_hooks() {
        // Add character display to profile header
        add_action('bp_before_member_header_meta', [$this, 'display_character_info']);
        
        // Add character switching tab
        add_action('bp_setup_nav', [$this, 'setup_character_nav'], 100);
        
        // Add assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Display character info in profile header
     * 
     * @return void
     */
    public function display_character_info() {
        // Implementation logic
    }
    
    /**
     * Set up character navigation tab
     * 
     * @return void
     */
    public function setup_character_nav() {
        // Implementation logic
    }
    
    /**
     * Enqueue assets for profile display
     * 
     * @return void
     */
    public function enqueue_assets() {
        // Implementation logic
    }
    
    /**
     * Display character management screen
     * 
     * @return void
     */
    public function display_character_screen() {
        // Implementation logic
    }
}
```

### BuddyPress Integration Class

```php
/**
 * BuddyPress integration manager
 */
class BuddyPress_Integration {
    /**
     * @var Character_Manager
     */
    private $character_manager;
    
    /**
     * @var Profile_Display
     */
    private $profile_display;
    
    /**
     * @var Event_Dispatcher
     */
    private $event_dispatcher;
    
    /**
     * Constructor
     * 
     * @param Character_Manager $character_manager
     * @param Event_Dispatcher $event_dispatcher
     * @param Die_Code_Utility $die_code_utility
     */
    public function __construct(Character_Manager $character_manager, Event_Dispatcher $event_dispatcher, Die_Code_Utility $die_code_utility) {
        $this->character_manager = $character_manager;
        $this->event_dispatcher = $event_dispatcher;
        $this->die_code_utility = $die_code_utility;
    }
    
    /**
     * Initialize the integration
     * 
     * @return void
     */
    public function initialize() {
        // Ensure BuddyPress is active
        if (!function_exists('buddypress')) {
            return;
        }
        
        // Initialize components
        $this->profile_display = new Profile_Display($this->character_manager, $this->die_code_utility);
        
        // Register hooks
        add_action('bp_init', [$this, 'register_hooks'], 20);
        
        // Register event subscribers
        $this->register_event_subscribers();
    }
    
    /**
     * Register hooks
     * 
     * @return void
     */
    public function register_hooks() {
        $this->profile_display->register_hooks();
    }
    
    /**
     * Register event subscribers
     * 
     * @return void
     */
    private function register_event_subscribers() {
        $this->event_dispatcher->add_subscriber(new BuddyPress_Character_Subscriber($this->profile_display));
    }
}
```

## Profile Display Implementation

### Character Information Display
The active character information will be displayed in the BuddyPress profile header:

```php
public function display_character_info() {
    // Get displayed user ID
    $user_id = bp_displayed_user_id();
    
    // Get active character
    $character = $this->character_manager->get_active_character($user_id);
    
    if (!$character) {
        echo '<div class="rpg-character-info rpg-no-character">';
        echo '<p class="rpg-no-character-message">' . __('No active character', 'rpg-suite') . '</p>';
        echo '</div>';
        return;
    }
    
    // Get character data
    $attributes = get_post_meta($character->ID, '_rpg_attributes', true);
    $derived_stats = get_post_meta($character->ID, '_rpg_derived_stats', true);
    $character_class = get_post_meta($character->ID, '_rpg_class', true);
    $inventions = $this->character_manager->get_character_inventions($character->ID);
    
    // Display character info
    echo '<div class="rpg-character-info">';
    echo '<h4 class="rpg-character-name">' . esc_html($character->post_title) . '</h4>';
    
    if (!empty($character_class)) {
        echo '<div class="rpg-character-class">' . esc_html($character_class) . '</div>';
    }
    
    echo '<div class="rpg-character-attributes">';
    
    if (!empty($attributes)) {
        foreach ($attributes as $key => $value) {
            echo '<div class="rpg-attribute">';
            echo '<span class="rpg-attribute-name">' . esc_html($key) . '</span>';
            echo '<span class="rpg-attribute-value">' . esc_html($value) . '</span>';
            echo '</div>';
        }
    }
    
    echo '</div>'; // .rpg-character-attributes
    
    // Show a featured invention if available
    if (!empty($inventions)) {
        $featured = reset($inventions);
        echo '<div class="rpg-featured-invention">';
        echo '<h5>' . __('Notable Invention', 'rpg-suite') . '</h5>';
        echo '<div class="rpg-invention-name">' . esc_html($featured['name']) . '</div>';
        echo '</div>';
    }
    
    echo '</div>'; // .rpg-character-info
}
```

### Character Management Tab
A character management tab will be added to the BuddyPress profile:

```php
public function setup_character_nav() {
    // Only add tab for profile owner
    if (!bp_is_my_profile() && !current_user_can('manage_options')) {
        return;
    }
    
    bp_core_new_nav_item([
        'name' => __('Characters', 'rpg-suite'),
        'slug' => 'characters',
        'screen_function' => [$this, 'display_character_screen'],
        'position' => 80,
        'default_subnav_slug' => 'manage'
    ]);
    
    bp_core_new_subnav_item([
        'name' => __('Manage Characters', 'rpg-suite'),
        'slug' => 'manage',
        'parent_slug' => 'characters',
        'parent_url' => bp_displayed_user_domain() . 'characters/',
        'screen_function' => [$this, 'display_character_screen'],
        'position' => 10
    ]);
    
    // Add inventions sub-tab
    bp_core_new_subnav_item([
        'name' => __('Inventions', 'rpg-suite'),
        'slug' => 'inventions',
        'parent_slug' => 'characters',
        'parent_url' => bp_displayed_user_domain() . 'characters/',
        'screen_function' => [$this, 'display_inventions_screen'],
        'position' => 20
    ]);
}
```

### Character Switching Interface
The character management screen will include switching functionality:

```php
public function display_character_content() {
    // Get user ID
    $user_id = bp_displayed_user_id();
    
    // Get characters
    $characters = $this->character_manager->get_user_characters($user_id);
    $active_character = $this->character_manager->get_active_character($user_id);
    $active_id = $active_character ? $active_character->ID : 0;
    
    // Process form submissions
    if (isset($_POST['rpg_character_action']) && wp_verify_nonce($_POST['rpg_character_nonce'], 'rpg_character_action')) {
        if ($_POST['rpg_character_action'] === 'activate' && isset($_POST['character_id'])) {
            $character_id = intval($_POST['character_id']);
            $this->character_manager->set_active_character($user_id, $character_id);
            $active_id = $character_id;
        }
    }
    
    // Display character list
    echo '<div class="rpg-character-management">';
    echo '<h2>' . __('Your Characters', 'rpg-suite') . '</h2>';
    
    if (empty($characters)) {
        echo '<p>' . __('You have no characters yet.', 'rpg-suite') . '</p>';
    } else {
        echo '<ul class="rpg-character-list">';
        
        foreach ($characters as $character) {
            $is_active = ($character->ID === $active_id);
            
            echo '<li class="rpg-character-item' . ($is_active ? ' rpg-active-character' : '') . '">';
            echo '<div class="rpg-character-header">';
            echo '<h3 class="rpg-character-name">' . esc_html($character->post_title) . '</h3>';
            
            // Get character class
            $character_class = get_post_meta($character->ID, '_rpg_class', true);
            if (!empty($character_class)) {
                echo '<span class="rpg-character-class">' . esc_html($character_class) . '</span>';
            }
            
            if ($is_active) {
                echo '<span class="rpg-active-label">' . __('Active', 'rpg-suite') . '</span>';
            } else {
                echo '<form method="post" class="rpg-activate-form">';
                echo '<input type="hidden" name="rpg_character_action" value="activate">';
                echo '<input type="hidden" name="character_id" value="' . esc_attr($character->ID) . '">';
                echo wp_nonce_field('rpg_character_action', 'rpg_character_nonce', true, false);
                echo '<button type="submit" class="rpg-activate-button">' . __('Activate', 'rpg-suite') . '</button>';
                echo '</form>';
            }
            
            echo '</div>'; // .rpg-character-header
            
            // Display character info
            $attributes = get_post_meta($character->ID, '_rpg_attributes', true);
            $inventions = $this->character_manager->get_character_inventions($character->ID);
            
            echo '<div class="rpg-character-details">';
            
            if (!empty($attributes)) {
                echo '<div class="rpg-attributes">';
                foreach ($attributes as $key => $value) {
                    echo '<div class="rpg-attribute">';
                    echo '<span class="rpg-attribute-name">' . esc_html($key) . '</span>';
                    echo '<span class="rpg-attribute-value">' . esc_html($value) . '</span>';
                    
                    // Add basic d7 description
                    $parsed = $this->die_code_utility->parse_die_code($value);
                    echo '<span class="rpg-dice-code">(' . $parsed['dice'] . 'd7';
                    if ($parsed['modifier'] > 0) {
                        echo '+' . $parsed['modifier'];
                    }
                    echo ')</span>';
                    
                    echo '</div>';
                }
                echo '</div>';
            }
            
            // Show inventions count
            if (!empty($inventions)) {
                echo '<div class="rpg-inventions-count">';
                echo sprintf(
                    _n('%d Invention', '%d Inventions', count($inventions), 'rpg-suite'),
                    count($inventions)
                );
                echo '</div>';
            }
            
            echo '</div>'; // .rpg-character-details
            
            echo '</li>'; // .rpg-character-item
        }
        
        echo '</ul>'; // .rpg-character-list
    }
    
    // Add button to create new character if under limit
    $character_limit = $this->character_manager->get_character_limit($user_id);
    if (count($characters) < $character_limit) {
        echo '<a href="' . esc_url(admin_url('post-new.php?post_type=rpg_character')) . '" class="rpg-new-character-button">' . __('Create New Character', 'rpg-suite') . '</a>';
    }
    
    echo '</div>'; // .rpg-character-management
}

/**
 * Display inventions screen
 * 
 * @return void
 */
public function display_inventions_screen() {
    // Set up template
    add_action('bp_template_content', [$this, 'display_inventions_content']);
    bp_core_load_template('buddypress/members/single/plugins');
}

/**
 * Display inventions content
 * 
 * @return void
 */
public function display_inventions_content() {
    // Get displayed user ID
    $user_id = bp_displayed_user_id();
    
    // Get active character
    $active_character = $this->character_manager->get_active_character($user_id);
    
    if (!$active_character) {
        echo '<div class="rpg-no-character">';
        echo '<p>' . __('No active character to display inventions for.', 'rpg-suite') . '</p>';
        echo '</div>';
        return;
    }
    
    // Get inventions
    $inventions = $this->character_manager->get_character_inventions($active_character->ID);
    
    echo '<div class="rpg-inventions">';
    echo '<h2>' . sprintf(__('%s\'s Inventions', 'rpg-suite'), esc_html($active_character->post_title)) . '</h2>';
    
    if (empty($inventions)) {
        echo '<p>' . __('This character has not created any inventions yet.', 'rpg-suite') . '</p>';
    } else {
        echo '<ul class="rpg-inventions-list">';
        
        foreach ($inventions as $invention) {
            echo '<li class="rpg-invention-item">';
            echo '<div class="rpg-invention-header">';
            echo '<h3 class="rpg-invention-name">' . esc_html($invention['name']) . '</h3>';
            echo '<div class="rpg-invention-complexity">Complexity: ' . esc_html($invention['complexity']) . '</div>';
            echo '</div>';
            
            echo '<div class="rpg-invention-description">' . esc_html($invention['description']) . '</div>';
            
            if (!empty($invention['components'])) {
                echo '<div class="rpg-invention-components">';
                echo '<h4>' . __('Components', 'rpg-suite') . '</h4>';
                echo '<ul>';
                foreach ($invention['components'] as $component) {
                    echo '<li>' . esc_html($component) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            echo '<div class="rpg-invention-effects">';
            echo '<h4>' . __('Effects', 'rpg-suite') . '</h4>';
            echo '<p>' . esc_html($invention['effects']) . '</p>';
            echo '</div>';
            
            echo '</li>'; // .rpg-invention-item
        }
        
        echo '</ul>'; // .rpg-inventions-list
    }
    
    // Add button to create new invention
    if (bp_is_my_profile()) {
        echo '<a href="' . esc_url(admin_url('admin.php?page=rpg-suite-inventions&character_id=' . $active_character->ID)) . '" class="rpg-new-invention-button">' . __('Create New Invention', 'rpg-suite') . '</a>';
    }
    
    echo '</div>'; // .rpg-inventions
}
```

## CSS Integration

### Profile Display Styling
Basic CSS will be added to style the character display in BuddyPress profiles, focusing on functionality rather than specific aesthetic:

```php
public function enqueue_assets() {
    if (bp_is_user()) {
        wp_enqueue_style(
            'rpg-suite-buddypress',
            plugin_dir_url(RPG_SUITE_PLUGIN_FILE) . 'assets/css/buddypress.css',
            [],
            RPG_SUITE_VERSION
        );
    }
}
```

### BuddyX Theme Compatibility
Ensure compatibility with the BuddyX theme through essential CSS classes:

```css
/* BuddyX theme compatibility */
.buddyx .rpg-character-info {
    margin-bottom: 20px;
    background: var(--buddyx-profile-header-bg);
    border-radius: 4px;
    padding: 15px;
}

.buddyx .rpg-character-name {
    margin-top: 0;
    color: var(--buddyx-profile-header-color);
}

.buddyx .rpg-character-attributes {
    display: flex;
    flex-wrap: wrap;
}

.buddyx .rpg-attribute {
    margin-right: 15px;
    margin-bottom: 5px;
}

.buddyx .rpg-attribute-name {
    font-weight: bold;
    margin-right: 5px;
}

.buddyx .rpg-attribute-value {
    color: var(--buddyx-primary-color);
}

/* Minimal character management styling */
.buddyx .rpg-character-management h2,
.buddyx .rpg-inventions h2 {
    margin-bottom: 15px;
}

.buddyx .rpg-character-item,
.buddyx .rpg-invention-item {
    margin-bottom: 15px;
    padding: 15px;
}
```

## Event Integration

### BuddyPress Character Subscriber
The integration will respond to character events:

```php
/**
 * Subscriber for character events in BuddyPress
 */
class BuddyPress_Character_Subscriber implements Event_Subscriber {
    /**
     * @var Profile_Display
     */
    private $profile_display;
    
    /**
     * Constructor
     * 
     * @param Profile_Display $profile_display
     */
    public function __construct(Profile_Display $profile_display) {
        $this->profile_display = $profile_display;
    }
    
    /**
     * Get subscribed events
     * 
     * @return array
     */
    public static function get_subscribed_events() {
        return [
            'character_activated' => 'on_character_activated',
            'character_updated' => 'on_character_updated',
            'character_deleted' => 'on_character_deleted',
            'invention_created' => 'on_invention_created',
            'invention_updated' => 'on_invention_updated',
        ];
    }
    
    /**
     * Handle character activation
     * 
     * @param Event $event
     * @return void
     */
    public function on_character_activated(Event $event) {
        // Refresh profile cache if needed
    }
    
    /**
     * Handle character update
     * 
     * @param Event $event
     * @return void
     */
    public function on_character_updated(Event $event) {
        // Refresh profile cache if needed
    }
    
    /**
     * Handle character deletion
     * 
     * @param Event $event
     * @return void
     */
    public function on_character_deleted(Event $event) {
        // Clean up and refresh profile
    }
    
    /**
     * Handle invention creation
     * 
     * @param Event $event
     * @return void
     */
    public function on_invention_created(Event $event) {
        // Update relevant profile sections
    }
    
    /**
     * Handle invention update
     * 
     * @param Event $event
     * @return void
     */
    public function on_invention_updated(Event $event) {
        // Update relevant profile sections
    }
}
```

## Implementation Notes

1. **Hook Registration Timing**: Hooks must be registered at the correct time in the BuddyPress lifecycle:
   ```php
   add_action('bp_init', [$this, 'register_hooks'], 20);
   ```

2. **Component Access**: Character data should be accessible through the global plugin instance:
   ```php
   global $rpg_suite;
   $character = $rpg_suite->character_manager->get_active_character($user_id);
   ```

3. **Permission Checks**: Always verify user permissions before displaying management UI:
   ```php
   if (!bp_is_my_profile() && !current_user_can('manage_options')) {
       return;
   }
   ```

4. **BuddyX Compatibility**: Ensure compatibility with the BuddyX theme:
   ```css
   .buddyx .rpg-character-name {
       color: var(--buddyx-profile-header-color);
   }
   ```

5. **d7 System Display**: Present d7 dice codes in a clear format without heavy styling:
   ```php
   $parsed = $this->die_code_utility->parse_die_code($value);
   echo '<span class="rpg-dice-code">(' . $parsed['dice'] . 'd7';
   if ($parsed['modifier'] > 0) {
       echo '+' . $parsed['modifier'];
   }
   echo ')</span>';
   ```

6. **Security**: Always validate and sanitize input/output:
   ```php
   echo wp_nonce_field('rpg_character_action', 'rpg_character_nonce', true, false);
   // And then verify:
   if (isset($_POST['rpg_character_action']) && wp_verify_nonce($_POST['rpg_character_nonce'], 'rpg_character_action')) {
       // Process form
   }
   ```