<?php
/**
 * Character Post Type
 *
 * Handles registration and configuration of the character post type.
 *
 * @package    RPG_Suite
 * @subpackage Character
 * @since      0.1.0
 */

/**
 * Character Post Type Class
 *
 * Registers and configures the character post type, meta boxes, and admin columns.
 */
class RPG_Suite_Character_Post_Type {

    /**
     * Post type name
     *
     * @var string
     */
    const POST_TYPE = 'rpg_character';

    /**
     * Initialize the class
     *
     * Sets up hooks for post type registration and admin UI customization.
     */
    public function __construct() {
        // Register post type
        add_action('init', array($this, 'register_post_type'));
        
        // Add meta boxes for character attributes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save meta box data
        add_action('save_post_' . self::POST_TYPE, array($this, 'save_meta_boxes'), 10, 2);
        
        // Customize admin columns
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        
        // Customize post update messages
        add_filter('post_updated_messages', array($this, 'updated_messages'));
    }

    /**
     * Register character post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __('Characters', 'rpg-suite'),
            'singular_name'      => __('Character', 'rpg-suite'),
            'menu_name'          => __('Characters', 'rpg-suite'),
            'name_admin_bar'     => __('Character', 'rpg-suite'),
            'add_new'            => __('Add New', 'rpg-suite'),
            'add_new_item'       => __('Add New Character', 'rpg-suite'),
            'new_item'           => __('New Character', 'rpg-suite'),
            'edit_item'          => __('Edit Character', 'rpg-suite'),
            'view_item'          => __('View Character', 'rpg-suite'),
            'all_items'          => __('All Characters', 'rpg-suite'),
            'search_items'       => __('Search Characters', 'rpg-suite'),
            'parent_item_colon'  => __('Parent Characters:', 'rpg-suite'),
            'not_found'          => __('No characters found.', 'rpg-suite'),
            'not_found_in_trash' => __('No characters found in Trash.', 'rpg-suite')
        );
        
        $capabilities = array(
            'edit_post'          => 'edit_post',
            'read_post'          => 'read_post',
            'delete_post'        => 'delete_post',
            'edit_posts'         => 'edit_posts',
            'edit_others_posts'  => 'edit_others_posts',
            'publish_posts'      => 'publish_posts',
            'read_private_posts' => 'read_private_posts',
            'delete_posts'       => 'delete_posts',
            'delete_others_posts'=> 'delete_others_posts'
        );
        
        $rewrite = array(
            'slug'       => 'character',
            'with_front' => true,
            'pages'      => true,
            'feeds'      => true,
            'ep_mask'    => EP_PERMALINK,
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true, 
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => $rewrite,
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 30,
            'menu_icon'           => 'dashicons-admin-users',
            'supports'            => array('title', 'editor', 'thumbnail', 'author', 'custom-fields'),
            'capability_type'     => 'post',  // CRITICAL: Use standard post capabilities only
            'map_meta_cap'        => true,    // Enable capability mapping
            'capabilities'        => $capabilities,  // EXPLICIT capability mapping to ensure proper editing
        );
        
        // Register the post type
        register_post_type(self::POST_TYPE, $args);
        
        // Set a flag to flush rewrite rules
        if (!get_option('rpg_suite_permalinks_flushed')) {
            update_option('rpg_suite_permalinks_flushed', '0');
        }
    }

    /**
     * Add character meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'rpg_character_attributes',
            __('Character Attributes', 'rpg-suite'),
            array($this, 'render_attributes_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
        
        add_meta_box(
            'rpg_character_class',
            __('Character Class', 'rpg-suite'),
            array($this, 'render_class_meta_box'),
            self::POST_TYPE,
            'side',
            'default'
        );
        
        add_meta_box(
            'rpg_character_status',
            __('Character Status', 'rpg-suite'),
            array($this, 'render_status_meta_box'),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Render attributes meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_attributes_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('rpg_character_attributes_save', 'rpg_character_attributes_nonce');
        
        // Get saved attributes
        $fortitude = get_post_meta($post->ID, '_rpg_attribute_fortitude', true) ?: '2d7';
        $precision = get_post_meta($post->ID, '_rpg_attribute_precision', true) ?: '2d7';
        $intellect = get_post_meta($post->ID, '_rpg_attribute_intellect', true) ?: '2d7';
        $charisma  = get_post_meta($post->ID, '_rpg_attribute_charisma', true) ?: '2d7';
        
        // Output the fields
        ?>
        <div class="rpg-suite-attributes">
            <p class="description"><?php _e('Attributes use the d7 system format (e.g., "2d7+1").', 'rpg-suite'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th><label for="rpg_attribute_fortitude"><?php _e('Fortitude', 'rpg-suite'); ?></label></th>
                    <td>
                        <input type="text" id="rpg_attribute_fortitude" name="rpg_attribute_fortitude" 
                               value="<?php echo esc_attr($fortitude); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="rpg_attribute_precision"><?php _e('Precision', 'rpg-suite'); ?></label></th>
                    <td>
                        <input type="text" id="rpg_attribute_precision" name="rpg_attribute_precision" 
                               value="<?php echo esc_attr($precision); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="rpg_attribute_intellect"><?php _e('Intellect', 'rpg-suite'); ?></label></th>
                    <td>
                        <input type="text" id="rpg_attribute_intellect" name="rpg_attribute_intellect" 
                               value="<?php echo esc_attr($intellect); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="rpg_attribute_charisma"><?php _e('Charisma', 'rpg-suite'); ?></label></th>
                    <td>
                        <input type="text" id="rpg_attribute_charisma" name="rpg_attribute_charisma" 
                               value="<?php echo esc_attr($charisma); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render class meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_class_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('rpg_character_class_save', 'rpg_character_class_nonce');
        
        // Get saved class
        $class = get_post_meta($post->ID, '_rpg_class', true);
        
        // Available classes
        $classes = array(
            'aeronaut'     => __('Aeronaut', 'rpg-suite'),
            'mechwright'   => __('Mechwright', 'rpg-suite'),
            'aethermancer' => __('Aethermancer', 'rpg-suite'),
            'diplomat'     => __('Diplomat', 'rpg-suite'),
        );
        
        // Output the select field
        ?>
        <div class="rpg-suite-class">
            <p class="description"><?php _e('Select the character\'s profession.', 'rpg-suite'); ?></p>
            
            <select id="rpg_class" name="rpg_class">
                <option value=""><?php _e('Select Class', 'rpg-suite'); ?></option>
                <?php foreach ($classes as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($class, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Render status meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_status_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('rpg_character_status_save', 'rpg_character_status_nonce');
        
        // Get saved values
        $active = (bool) get_post_meta($post->ID, '_rpg_active', true);
        $invention_points = (int) get_post_meta($post->ID, '_rpg_invention_points', true) ?: 0;
        $fate_tokens = (int) get_post_meta($post->ID, '_rpg_fate_tokens', true) ?: 0;
        
        // Output the fields
        ?>
        <div class="rpg-suite-status">
            <p>
                <label>
                    <input type="checkbox" name="rpg_active" value="1" <?php checked($active); ?>>
                    <?php _e('Active Character', 'rpg-suite'); ?>
                </label>
            </p>
            
            <p>
                <label for="rpg_invention_points"><?php _e('Invention Points:', 'rpg-suite'); ?></label>
                <input type="number" id="rpg_invention_points" name="rpg_invention_points" 
                       value="<?php echo esc_attr($invention_points); ?>" min="0" step="1" style="width: 60px;">
            </p>
            
            <p>
                <label for="rpg_fate_tokens"><?php _e('Fate Tokens:', 'rpg-suite'); ?></label>
                <input type="number" id="rpg_fate_tokens" name="rpg_fate_tokens" 
                       value="<?php echo esc_attr($fate_tokens); ?>" min="0" step="1" style="width: 60px;">
            </p>
        </div>
        <?php
    }

    /**
     * Save meta box data
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post    Post object
     */
    public function save_meta_boxes($post_id, $post) {
        // Check if we're autosaving
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Process attributes meta box
        if (isset($_POST['rpg_character_attributes_nonce']) && 
            wp_verify_nonce($_POST['rpg_character_attributes_nonce'], 'rpg_character_attributes_save')) {
            
            if (isset($_POST['rpg_attribute_fortitude'])) {
                update_post_meta($post_id, '_rpg_attribute_fortitude', sanitize_text_field($_POST['rpg_attribute_fortitude']));
            }
            
            if (isset($_POST['rpg_attribute_precision'])) {
                update_post_meta($post_id, '_rpg_attribute_precision', sanitize_text_field($_POST['rpg_attribute_precision']));
            }
            
            if (isset($_POST['rpg_attribute_intellect'])) {
                update_post_meta($post_id, '_rpg_attribute_intellect', sanitize_text_field($_POST['rpg_attribute_intellect']));
            }
            
            if (isset($_POST['rpg_attribute_charisma'])) {
                update_post_meta($post_id, '_rpg_attribute_charisma', sanitize_text_field($_POST['rpg_attribute_charisma']));
            }
        }
        
        // Process class meta box
        if (isset($_POST['rpg_character_class_nonce']) && 
            wp_verify_nonce($_POST['rpg_character_class_nonce'], 'rpg_character_class_save')) {
            
            if (isset($_POST['rpg_class'])) {
                update_post_meta($post_id, '_rpg_class', sanitize_text_field($_POST['rpg_class']));
            }
        }
        
        // Process status meta box
        if (isset($_POST['rpg_character_status_nonce']) && 
            wp_verify_nonce($_POST['rpg_character_status_nonce'], 'rpg_character_status_save')) {
            
            // Handle active status
            $active = isset($_POST['rpg_active']) ? 1 : 0;
            update_post_meta($post_id, '_rpg_active', $active);
            
            // If this character is being set as active, deactivate other characters for this user
            if ($active) {
                $author_id = $post->post_author;
                $this->deactivate_other_characters($post_id, $author_id);
            }
            
            // Handle invention points
            if (isset($_POST['rpg_invention_points'])) {
                update_post_meta($post_id, '_rpg_invention_points', absint($_POST['rpg_invention_points']));
            }
            
            // Handle fate tokens
            if (isset($_POST['rpg_fate_tokens'])) {
                update_post_meta($post_id, '_rpg_fate_tokens', absint($_POST['rpg_fate_tokens']));
            }
        }
    }

    /**
     * Deactivate other characters for a user
     *
     * @param int $current_post_id Current character post ID
     * @param int $author_id       Author user ID
     */
    private function deactivate_other_characters($current_post_id, $author_id) {
        $args = array(
            'post_type'      => self::POST_TYPE,
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
     * Add custom admin columns
     *
     * @param array $columns Array of column names
     * @return array Modified array of column names
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            // Insert custom columns after title
            if ($key === 'title') {
                $new_columns[$key] = $value;
                $new_columns['rpg_class'] = __('Class', 'rpg-suite');
                $new_columns['rpg_active'] = __('Active', 'rpg-suite');
            } else {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }

    /**
     * Display content for custom columns
     *
     * @param string $column  Column name
     * @param int    $post_id Post ID
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'rpg_class':
                $class = get_post_meta($post_id, '_rpg_class', true);
                $classes = array(
                    'aeronaut'     => __('Aeronaut', 'rpg-suite'),
                    'mechwright'   => __('Mechwright', 'rpg-suite'),
                    'aethermancer' => __('Aethermancer', 'rpg-suite'),
                    'diplomat'     => __('Diplomat', 'rpg-suite'),
                );
                echo isset($classes[$class]) ? esc_html($classes[$class]) : '—';
                break;
                
            case 'rpg_active':
                $active = (bool) get_post_meta($post_id, '_rpg_active', true);
                echo $active ? '<span class="dashicons dashicons-yes" style="color: green;"></span>' : '—';
                break;
        }
    }

    /**
     * Customize post update messages
     *
     * @param array $messages Post update messages
     * @return array Modified post update messages
     */
    public function updated_messages($messages) {
        global $post;
        
        $messages[self::POST_TYPE] = array(
            0  => '', // Unused. Messages start at index 1.
            1  => __('Character updated.', 'rpg-suite'),
            2  => __('Custom field updated.', 'rpg-suite'),
            3  => __('Custom field deleted.', 'rpg-suite'),
            4  => __('Character updated.', 'rpg-suite'),
            /* translators: %s: date and time of the revision */
            5  => isset($_GET['revision']) ? sprintf(__('Character restored to revision from %s', 'rpg-suite'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6  => __('Character published.', 'rpg-suite'),
            7  => __('Character saved.', 'rpg-suite'),
            8  => __('Character submitted.', 'rpg-suite'),
            9  => sprintf(
                __('Character scheduled for: <strong>%1$s</strong>.', 'rpg-suite'),
                date_i18n(__('M j, Y @ G:i', 'rpg-suite'), strtotime($post->post_date))
            ),
            10 => __('Character draft updated.', 'rpg-suite')
        );
        
        return $messages;
    }
}