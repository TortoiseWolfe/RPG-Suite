# BuddyPress Profile Integration Specification - React Revision

## Purpose
This specification defines how RPG-Suite integrates with BuddyPress to display React-based character sheets on user profiles while providing seamless character management functionality.

## Requirements
1. Display active character information using React components
2. Provide real-time character switching without page reloads
3. Ensure compatibility with BuddyX theme
4. Register hooks at appropriate times in BuddyPress lifecycle
5. Provide mount points for React components
6. Pass WordPress user data to React through localized scripts

## Key Architectural Changes
1. **React-Based Character Sheets**: Dynamic UI for responsive character display
2. **REST API Integration**: All character data operations through REST endpoints
3. **No Page Reloads**: Character switching happens via React state updates
4. **Multi-Layer Caching**: Improved performance through client-side state management
5. **WordPress Authentication**: React reads user data but never manages auth

## Component Structure

### BuddyPress Integration Class

The BuddyPress Integration class should:
1. Be named `RPG_Suite_BuddyPress_Integration`
2. Be defined in file `class-buddypress-integration.php`
3. Have dependencies on:
   - RPG_Suite_Character_Manager
   - RPG_Suite_Event_Dispatcher
   - RPG_Suite_Cache_Manager (new)
4. Initialize the integration with these steps:
   - Check if BuddyPress is active
   - Register hooks for React mount points
   - Enqueue React application bundle
   - Localize user and character data for React
5. Implement methods:
   - initialize_hooks(): Register profile display hooks
   - render_react_mount_point(): Create div for React app
   - enqueue_react_assets(): Load React bundle and dependencies
   - localize_character_data(): Pass data to React

## React Mount Point Implementation

```php
/**
 * Render React mount point for character sheet
 */
public function render_react_mount_point() {
    // Only display on BP profile pages
    if (!function_exists('bp_is_user') || !bp_is_user()) {
        return;
    }
    
    $user_id = bp_displayed_user_id();
    $active_character = $this->character_manager->get_active_character($user_id);
    
    if (!$active_character) {
        echo '<div class="rpg-suite-no-character">' . 
             esc_html__('No active character', 'rpg-suite') . 
             '</div>';
        return;
    }
    
    // React mount point with data attributes
    echo '<div id="rpg-character-sheet-root" 
               class="rpg-suite-react-app"
               data-character-id="' . esc_attr($active_character->ID) . '"
               data-user-id="' . esc_attr($user_id) . '"
               data-can-edit="' . esc_attr(current_user_can('edit_rpg_character', $active_character->ID) ? 'true' : 'false') . '">
          </div>';
    
    // Character switcher mount point (separate component)
    if (get_current_user_id() === $user_id) {
        echo '<div id="rpg-character-switcher-root" 
                   class="rpg-suite-switcher"
                   data-user-id="' . esc_attr($user_id) . '">
              </div>';
    }
}
```

## Asset Enqueueing with React

```php
/**
 * Enqueue React application and dependencies
 */
public function enqueue_react_assets() {
    if (!bp_is_user()) {
        return;
    }
    
    // React app bundle (built by webpack)
    wp_enqueue_script(
        'rpg-suite-react-app',
        RPG_SUITE_PLUGIN_URL . 'react-app/build/main.js',
        array('wp-api-fetch', 'wp-element'),
        RPG_SUITE_VERSION,
        true
    );
    
    // React app styles
    wp_enqueue_style(
        'rpg-suite-react-app',
        RPG_SUITE_PLUGIN_URL . 'react-app/build/main.css',
        array(),
        RPG_SUITE_VERSION
    );
    
    // Localize data for React
    $this->localize_character_data();
}

/**
 * Pass data to React application
 */
private function localize_character_data() {
    $user_id = bp_displayed_user_id();
    $current_user_id = get_current_user_id();
    $active_character = $this->character_manager->get_active_character($user_id);
    $user_characters = ($current_user_id === $user_id) 
        ? $this->character_manager->get_user_characters($user_id) 
        : array();
    
    // Prepare character data for React
    $characters_data = array();
    foreach ($user_characters as $character) {
        $characters_data[] = array(
            'id' => $character->ID,
            'name' => $character->post_title,
            'class' => get_post_meta($character->ID, '_rpg_class', true),
            'isActive' => (bool) get_post_meta($character->ID, '_rpg_active', true),
            'attributes' => array(
                'fortitude' => get_post_meta($character->ID, '_rpg_attribute_fortitude', true),
                'precision' => get_post_meta($character->ID, '_rpg_attribute_precision', true),
                'intellect' => get_post_meta($character->ID, '_rpg_attribute_intellect', true),
                'charisma' => get_post_meta($character->ID, '_rpg_attribute_charisma', true),
            ),
        );
    }
    
    wp_localize_script('rpg-suite-react-app', 'rpgSuiteData', array(
        'api' => array(
            'root' => rest_url('rpg-suite/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ),
        'user' => array(
            'id' => $current_user_id,
            'displayName' => wp_get_current_user()->display_name,
            'capabilities' => array_keys(wp_get_current_user()->allcaps),
            'avatar' => get_avatar_url($current_user_id),
        ),
        'profile' => array(
            'userId' => $user_id,
            'isOwner' => $current_user_id === $user_id,
        ),
        'characters' => $characters_data,
        'activeCharacterId' => $active_character ? $active_character->ID : null,
        'i18n' => array(
            'switchCharacter' => __('Switch Character', 'rpg-suite'),
            'editCharacter' => __('Edit Character', 'rpg-suite'),
            'noCharacter' => __('No active character', 'rpg-suite'),
            'loading' => __('Loading...', 'rpg-suite'),
            'error' => __('An error occurred', 'rpg-suite'),
        ),
    ));
}
```

## Hook Registration for React

```php
/**
 * Initialize BuddyPress hooks for React integration
 */
public function initialize_hooks() {
    // Add React mount points to profile
    add_action('bp_before_member_body', array($this, 'render_react_mount_point'), 10);
    
    // Enqueue React assets on profile pages
    add_action('wp_enqueue_scripts', array($this, 'enqueue_react_assets'), 20);
    
    // Register REST API endpoints for React
    add_action('rest_api_init', array($this, 'register_rest_endpoints'));
    
    // Handle cache invalidation for React
    add_action('rpg_character_updated', array($this, 'handle_character_update'));
    add_action('rpg_character_switched', array($this, 'handle_character_switch'));
}
```

## REST API Endpoints for React

```php
/**
 * Register REST endpoints for React frontend
 */
public function register_rest_endpoints() {
    // Get character data
    register_rest_route('rpg-suite/v1', '/characters/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_character_data'),
        'permission_callback' => array($this, 'check_character_permissions'),
    ));
    
    // Update character
    register_rest_route('rpg-suite/v1', '/characters/(?P<id>\d+)', array(
        'methods' => 'PATCH',
        'callback' => array($this, 'update_character_data'),
        'permission_callback' => array($this, 'check_character_edit_permissions'),
    ));
    
    // Switch active character
    register_rest_route('rpg-suite/v1', '/characters/switch', array(
        'methods' => 'POST',
        'callback' => array($this, 'switch_active_character'),
        'permission_callback' => 'is_user_logged_in',
    ));
    
    // Get user's characters
    register_rest_route('rpg-suite/v1', '/users/(?P<id>\d+)/characters', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_user_characters'),
        'permission_callback' => '__return_true',
    ));
}
```

## React Component Integration

The React application handles:

1. **Character Sheet Display**: Dynamic rendering of character attributes
2. **Real-Time Updates**: Changes reflect immediately without reload
3. **Character Switching**: Seamless switching between characters
4. **Responsive Design**: Mobile-friendly interface
5. **Theme Integration**: Inherits BuddyX theme styles

### Example React Component Structure

```javascript
// React app entry point
import React from 'react';
import ReactDOM from 'react-dom/client';
import CharacterSheet from './components/CharacterSheet';
import CharacterSwitcher from './components/CharacterSwitcher';

// Mount character sheet
const sheetRoot = document.getElementById('rpg-character-sheet-root');
if (sheetRoot) {
    const root = ReactDOM.createRoot(sheetRoot);
    root.render(
        <CharacterSheet 
            characterId={sheetRoot.dataset.characterId}
            userId={sheetRoot.dataset.userId}
            canEdit={sheetRoot.dataset.canEdit === 'true'}
        />
    );
}

// Mount character switcher
const switcherRoot = document.getElementById('rpg-character-switcher-root');
if (switcherRoot) {
    const root = ReactDOM.createRoot(switcherRoot);
    root.render(
        <CharacterSwitcher 
            userId={switcherRoot.dataset.userId}
        />
    );
}
```

## Caching Strategy

The integration implements multi-layer caching:

1. **WordPress Transients**: Character data cached server-side
2. **REST API Headers**: HTTP caching for API responses
3. **React State**: Client-side caching in Redux/Context
4. **Service Worker**: Optional offline support (future)

## Implementation Notes

1. **No Full Page Reloads**: All character operations happen via React
2. **WordPress Authentication**: React never handles login/logout
3. **Progressive Enhancement**: Basic functionality without JavaScript
4. **Performance First**: Optimistic updates and smart caching
5. **Accessibility**: WCAG 2.1 AA compliance for React components
6. **Mobile Support**: Touch-friendly interface design
7. **Theme Compatibility**: Inherits BuddyX styles where possible
8. **Error Handling**: Graceful degradation with user feedback

## Migration Path

For existing installations:

1. Deploy backend fixes first
2. Add React bundle progressively
3. Maintain PHP fallback templates initially
4. Migrate users to React interface gradually
5. Remove legacy code after full adoption

## Testing Requirements

1. **Unit Tests**: Jest for React components
2. **Integration Tests**: REST API endpoints
3. **E2E Tests**: Cypress for user workflows
4. **Performance Tests**: Lighthouse metrics
5. **Accessibility Tests**: aXe integration