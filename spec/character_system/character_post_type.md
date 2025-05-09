# Character Post Type Specification

## Purpose
This specification defines the custom post type for characters in the RPG-Suite plugin, including its registration, meta fields, and admin integration.

## Requirements
1. Register a custom post type for characters
2. Define and register custom meta fields for character data
3. Add meta boxes for character editing
4. Set up proper labels and capabilities
5. Configure archive and single views
6. Handle post type registration during plugin activation

## Class Definition

```php
/**
 * Character Post Type registration and management
 *
 * @since 1.0.0
 */
class RPG_Suite_Character_Post_Type {
    /**
     * Post type name
     *
     * @since 1.0.0
     * @var string
     */
    const POST_TYPE = 'rpg_character';
    
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
     * Initialize the post type
     *
     * @since 1.0.0
     * @return void
     */
    public function init() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_meta'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . self::POST_TYPE, array($this, 'save_meta_boxes'), 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('post_updated_messages', array($this, 'updated_messages'));
    }
    
    /**
     * Register the character post type
     *
     * @since 1.0.0
     * @return void
     */
    public function register_post_type() {
        // Implementation logic
    }
    
    /**
     * Register meta fields
     *
     * @since 1.0.0
     * @return void
     */
    public function register_meta() {
        // Implementation logic
    }
    
    /**
     * Add meta boxes
     *
     * @since 1.0.0
     * @param WP_Post $post The post object.
     * @return void
     */
    public function add_meta_boxes($post) {
        // Implementation logic
    }
    
    /**
     * Save meta box data
     *
     * @since 1.0.0
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @return void
     */
    public function save_meta_boxes($post_id, $post) {
        // Implementation logic
    }
    
    /**
     * Add custom columns to character list
     *
     * @since 1.0.0
     * @param array $columns Array of columns.
     * @return array Modified columns.
     */
    public function add_custom_columns($columns) {
        // Implementation logic
    }
    
    /**
     * Display content in custom columns
     *
     * @since 1.0.0
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     * @return void
     */
    public function custom_column_content($column, $post_id) {
        // Implementation logic
    }
    
    /**
     * Customize post updated messages
     *
     * @since 1.0.0
     * @param array $messages Array of messages.
     * @return array Modified messages.
     */
    public function updated_messages($messages) {
        // Implementation logic
    }
}
```

## Method Implementations

### Registering Post Type

```php
/**
 * Register the character post type
 *
 * @since 1.0.0
 * @return void
 */
public function register_post_type() {
    $labels = array(
        'name'                  => _x('Characters', 'Post type general name', 'rpg-suite'),
        'singular_name'         => _x('Character', 'Post type singular name', 'rpg-suite'),
        'menu_name'             => _x('Characters', 'Admin Menu text', 'rpg-suite'),
        'name_admin_bar'        => _x('Character', 'Add New on Toolbar', 'rpg-suite'),
        'add_new'               => __('Add New', 'rpg-suite'),
        'add_new_item'          => __('Add New Character', 'rpg-suite'),
        'new_item'              => __('New Character', 'rpg-suite'),
        'edit_item'             => __('Edit Character', 'rpg-suite'),
        'view_item'             => __('View Character', 'rpg-suite'),
        'all_items'             => __('All Characters', 'rpg-suite'),
        'search_items'          => __('Search Characters', 'rpg-suite'),
        'parent_item_colon'     => __('Parent Characters:', 'rpg-suite'),
        'not_found'             => __('No characters found.', 'rpg-suite'),
        'not_found_in_trash'    => __('No characters found in Trash.', 'rpg-suite'),
        'featured_image'        => _x('Character Image', 'Overrides the "Featured Image" phrase', 'rpg-suite'),
        'set_featured_image'    => _x('Set character image', 'Overrides the "Set featured image" phrase', 'rpg-suite'),
        'remove_featured_image' => _x('Remove character image', 'Overrides the "Remove featured image" phrase', 'rpg-suite'),
        'use_featured_image'    => _x('Use as character image', 'Overrides the "Use as featured image" phrase', 'rpg-suite'),
        'archives'              => _x('Character archives', 'The post type archive label used in nav menus', 'rpg-suite'),
        'insert_into_item'      => _x('Insert into character', 'Overrides the "Insert into post"/"Insert into page" phrase', 'rpg-suite'),
        'uploaded_to_this_item' => _x('Uploaded to this character', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'rpg-suite'),
        'filter_items_list'     => _x('Filter characters list', 'Screen reader text for the filter links heading on the post type listing screen', 'rpg-suite'),
        'items_list_navigation' => _x('Characters list navigation', 'Screen reader text for the pagination heading on the post type listing screen', 'rpg-suite'),
        'items_list'            => _x('Characters list', 'Screen reader text for the items list heading on the post type listing screen', 'rpg-suite'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => 'rpg-suite',
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'character'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
    );

    register_post_type(self::POST_TYPE, $args);
}
```

### Registering Meta Fields

```php
/**
 * Register meta fields
 *
 * @since 1.0.0
 * @return void
 */
public function register_meta() {
    // Registration of character attributes
    register_post_meta(self::POST_TYPE, '_rpg_attributes', array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'object',
        'sanitize_callback' => array($this, 'sanitize_attributes'),
        'auth_callback'     => function() {
            return current_user_can('edit_posts');
        },
    ));
    
    // Registration of character skills
    register_post_meta(self::POST_TYPE, '_rpg_skills', array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'object',
        'sanitize_callback' => array($this, 'sanitize_skills'),
        'auth_callback'     => function() {
            return current_user_can('edit_posts');
        },
    ));
    
    // Registration of character class
    register_post_meta(self::POST_TYPE, '_rpg_class', array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function() {
            return current_user_can('edit_posts');
        },
    ));
    
    // Registration of active status
    register_post_meta(self::POST_TYPE, '_rpg_active', array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'auth_callback'     => function() {
            return current_user_can('edit_posts');
        },
    ));
    
    // Registration of invention points
    register_post_meta(self::POST_TYPE, '_rpg_invention_points', array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'auth_callback'     => function() {
            return current_user_can('edit_posts');
        },
    ));
    
    // Registration of fate tokens
    register_post_meta(self::POST_TYPE, '_rpg_fate_tokens', array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'auth_callback'     => function() {
            return current_user_can('edit_posts');
        },
    ));
}
```

## Integration with WordPress and BuddyPress

The character post type integrates with WordPress and BuddyPress through:

1. **REST API Support** - Enables modern block editor usage and API access
2. **Custom Capability Handling** - Uses WordPress permission system
3. **Meta Field Registration** - Makes character data available to REST API
4. **Custom Archive Templates** - Character listings in frontend
5. **BuddyPress Profile Integration** - Display character data in profiles

## Security Considerations

1. **Capability Checks** - All operations validate user permissions
2. **Data Sanitization** - All input/output properly sanitized
3. **Nonce Verification** - Form submissions verified with nonces
4. **Field Authorization** - Meta fields have auth_callback checks

## Performance Optimization

1. **Efficient Queries** - Custom post type uses proper indexing
2. **Minimal Admin Load** - Admin assets only loaded when needed
3. **Targeted Meta Registration** - Only necessary fields exposed to REST

## Implementation Notes

1. The post type registration happens during plugin initialization
2. The admin UI leverages WordPress core UI patterns
3. The d7 system is integrated into meta boxes for attribute editing
4. The post type is designed to work with the character manager