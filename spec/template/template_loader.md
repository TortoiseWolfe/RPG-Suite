# Template Loader Specification

## Purpose
The Template Loader provides a mechanism for loading custom templates for RPG-Suite content, allowing theme overrides while maintaining a consistent fallback structure.

## Requirements
1. Support template overrides in themes and child themes
2. Provide fallback templates within the plugin
3. Support templates for character display and management
4. Filter template paths for extensibility
5. Maintain consistent template hierarchy
6. Support BuddyPress theme compatibility

## Class Definition

```php
/**
 * Template loading system for RPG-Suite
 *
 * @since 1.0.0
 */
class RPG_Suite_Template_Loader {
    /**
     * Template directory in the plugin
     *
     * @since 1.0.0
     * @var string
     */
    private $plugin_template_directory;
    
    /**
     * Template directory name in themes
     *
     * @since 1.0.0
     * @var string
     */
    private $theme_template_directory;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->plugin_template_directory = RPG_SUITE_PLUGIN_DIR . 'templates/';
        $this->theme_template_directory = 'rpg-suite/';
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks() {
        add_filter('template_include', array($this, 'template_loader'));
        add_filter('single_template', array($this, 'single_template'));
        add_filter('archive_template', array($this, 'archive_template'));
    }
    
    /**
     * Main template loader
     *
     * @since 1.0.0
     * @param string $template Template path.
     * @return string Modified template path.
     */
    public function template_loader($template) {
        // Implementation logic
    }
    
    /**
     * Single post template loader
     *
     * @since 1.0.0
     * @param string $template Template path.
     * @return string Modified template path.
     */
    public function single_template($template) {
        // Implementation logic
    }
    
    /**
     * Archive template loader
     *
     * @since 1.0.0
     * @param string $template Template path.
     * @return string Modified template path.
     */
    public function archive_template($template) {
        // Implementation logic
    }
    
    /**
     * Get template part
     *
     * @since 1.0.0
     * @param string $slug Template slug.
     * @param string $name Template name.
     * @param array  $args Template arguments.
     * @return void
     */
    public function get_template_part($slug, $name = '', $args = array()) {
        // Implementation logic
    }
    
    /**
     * Locate template
     *
     * @since 1.0.0
     * @param string $template_name Template name.
     * @param string $template_path Template path.
     * @param string $default_path Default path.
     * @return string Template path.
     */
    public function locate_template($template_name, $template_path = '', $default_path = '') {
        // Implementation logic
    }
    
    /**
     * Get template
     *
     * @since 1.0.0
     * @param string $template_name Template name.
     * @param array  $args          Template arguments.
     * @param string $template_path Template path.
     * @param string $default_path  Default path.
     * @return void
     */
    public function get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
        // Implementation logic
    }
}
```

## Method Implementations

### Template Loader

```php
/**
 * Main template loader
 *
 * @since 1.0.0
 * @param string $template Template path.
 * @return string Modified template path.
 */
public function template_loader($template) {
    if (is_embed()) {
        return $template;
    }
    
    $object = get_queried_object();
    
    if (is_singular('rpg_character')) {
        return $this->get_template_path('single-character.php', $template);
    } elseif (is_post_type_archive('rpg_character')) {
        return $this->get_template_path('archive-character.php', $template);
    } elseif (is_singular('rpg_invention')) {
        return $this->get_template_path('single-invention.php', $template);
    } elseif (is_post_type_archive('rpg_invention')) {
        return $this->get_template_path('archive-invention.php', $template);
    }
    
    return $template;
}

/**
 * Single post template loader
 *
 * @since 1.0.0
 * @param string $template Template path.
 * @return string Modified template path.
 */
public function single_template($template) {
    global $post;
    
    if ($post->post_type === 'rpg_character') {
        return $this->get_template_path('single-character.php', $template);
    } elseif ($post->post_type === 'rpg_invention') {
        return $this->get_template_path('single-invention.php', $template);
    }
    
    return $template;
}

/**
 * Archive template loader
 *
 * @since 1.0.0
 * @param string $template Template path.
 * @return string Modified template path.
 */
public function archive_template($template) {
    if (is_post_type_archive('rpg_character')) {
        return $this->get_template_path('archive-character.php', $template);
    } elseif (is_post_type_archive('rpg_invention')) {
        return $this->get_template_path('archive-invention.php', $template);
    }
    
    return $template;
}
```

### Template Path Resolution

```php
/**
 * Get template path
 *
 * @since 1.0.0
 * @param string $template_name Template name.
 * @param string $default_path  Default template path.
 * @return string Template path.
 */
private function get_template_path($template_name, $default_path) {
    // Check theme directories first
    $template = locate_template(array(
        $this->theme_template_directory . $template_name,
        $template_name,
    ));
    
    // Get default template from plugin
    if (!$template) {
        $template = $this->plugin_template_directory . $template_name;
    }
    
    // If template exists, use it; otherwise, use default
    if (file_exists($template)) {
        return $template;
    }
    
    return $default_path;
}
```

### Template Parts

```php
/**
 * Get template part (for templates like the character loop)
 *
 * @since 1.0.0
 * @param string $slug Template slug.
 * @param string $name Template name.
 * @param array  $args Template arguments.
 * @return void
 */
public function get_template_part($slug, $name = '', $args = array()) {
    $template = '';
    
    // Look in yourtheme/rpg-suite/slug-name.php and yourtheme/rpg-suite/slug.php
    if ($name) {
        $template = locate_template(array(
            $this->theme_template_directory . "{$slug}-{$name}.php",
            "{$slug}-{$name}.php",
        ));
    }
    
    // Get default slug-name.php
    if (!$template && $name && file_exists($this->plugin_template_directory . "{$slug}-{$name}.php")) {
        $template = $this->plugin_template_directory . "{$slug}-{$name}.php";
    }
    
    // If template file doesn't exist, look for the slug.php
    if (!$template) {
        $template = locate_template(array(
            $this->theme_template_directory . "{$slug}.php",
            "{$slug}.php",
        ));
    }
    
    // Get default slug.php
    if (!$template && file_exists($this->plugin_template_directory . "{$slug}.php")) {
        $template = $this->plugin_template_directory . "{$slug}.php";
    }
    
    // Allow 3rd party plugins to filter template file from their plugin
    $template = apply_filters('rpg_suite_get_template_part', $template, $slug, $name);
    
    if ($template) {
        load_template($template, false, $args);
    }
}
```

### Template Location

```php
/**
 * Locate a template and return the path for inclusion
 *
 * @since 1.0.0
 * @param string $template_name Template name.
 * @param string $template_path Template path.
 * @param string $default_path  Default path.
 * @return string Template path.
 */
public function locate_template($template_name, $template_path = '', $default_path = '') {
    if (!$template_path) {
        $template_path = $this->theme_template_directory;
    }
    
    if (!$default_path) {
        $default_path = $this->plugin_template_directory;
    }
    
    // Look within passed path within the theme - this is priority
    $template = locate_template(array(
        trailingslashit($template_path) . $template_name,
        $template_name,
    ));
    
    // Get default template
    if (!$template && file_exists(trailingslashit($default_path) . $template_name)) {
        $template = trailingslashit($default_path) . $template_name;
    }
    
    // Return what we found
    return apply_filters('rpg_suite_locate_template', $template, $template_name, $template_path);
}
```

### Template Loading

```php
/**
 * Get template and include it with arguments
 *
 * @since 1.0.0
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 * @return void
 */
public function get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
    $located = $this->locate_template($template_name, $template_path, $default_path);
    
    if (!file_exists($located)) {
        /* translators: %s template */
        _doing_it_wrong(__FUNCTION__, sprintf(__('%s does not exist.', 'rpg-suite'), '<code>' . $located . '</code>'), '1.0.0');
        return;
    }
    
    // Allow 3rd party plugin filter template file from their plugin.
    $located = apply_filters('rpg_suite_get_template', $located, $template_name, $args, $template_path, $default_path);
    
    do_action('rpg_suite_before_template_part', $template_name, $template_path, $located, $args);
    
    // Extract args if they exist to make them available as variables in the template
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    include $located;
    
    do_action('rpg_suite_after_template_part', $template_name, $template_path, $located, $args);
}
```

## Template Hierarchy

The template hierarchy follows this order of precedence:

1. Child Theme: `/rpg-suite/[template-name].php`
2. Parent Theme: `/rpg-suite/[template-name].php`
3. Child Theme: `/[template-name].php`
4. Parent Theme: `/[template-name].php`
5. Plugin: `/templates/[template-name].php`

## Default Templates Provided

The plugin includes these default templates:

1. **Archive Templates**
   - `archive-character.php` - Character listings
   - `archive-invention.php` - Invention listings

2. **Single Templates**
   - `single-character.php` - Single character display
   - `single-invention.php` - Single invention display

3. **Template Parts**
   - `content-character.php` - Character content
   - `content-invention.php` - Invention content
   - `character-attributes.php` - Character attributes display
   - `character-skills.php` - Character skills display
   - `character-inventions.php` - Character inventions list

## BuddyPress Integration

For BuddyPress compatibility, the template loader:

1. Checks if the current theme is BuddyPress compatible
2. Uses BuddyPress-specific templates when appropriate
3. Respects the BuddyPress template hierarchy
4. Falls back to plugin templates when needed

```php
/**
 * Check if current theme is BuddyPress compatible
 *
 * @since 1.0.0
 * @return bool
 */
private function is_buddypress_theme() {
    return current_theme_supports('buddypress') || in_array(
        get_template(),
        array('bp-default', 'buddyx'),
        true
    );
}
```

## Usage Examples

### Using Template Parts

```php
// In a theme template
global $rpg_suite;
$template_loader = $rpg_suite->get_template_loader();

// Load character attributes template part
$template_loader->get_template_part('character', 'attributes', array(
    'character_id' => get_the_ID(),
));

// Load character skills template part
$template_loader->get_template_part('character', 'skills', array(
    'character_id' => get_the_ID(),
));
```

### Creating a Custom Template

To override plugin templates in a theme, create files in the theme directory:

```
mytheme/
└── rpg-suite/
    ├── single-character.php
    ├── archive-character.php
    └── character-attributes.php
```

## Implementation Notes

1. **Theme Compatibility**: The template system respects theme hierarchy
2. **Extensibility**: All template paths can be filtered by plugins
3. **BuddyPress Support**: Special handling for BuddyPress themes
4. **Template Arguments**: Arguments can be passed to templates
5. **Action Hooks**: Pre/post template loading hooks for extensions
6. **Fallback System**: Default templates ensure content always displays
7. **Performance**: Template paths are efficiently located and cached