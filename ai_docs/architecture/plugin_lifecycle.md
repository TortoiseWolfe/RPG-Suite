# Plugin Lifecycle Management

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

This document outlines how RPG-Suite manages its lifecycle, including activation, deactivation, uninstallation processes, and React application deployment.

## Installation and Activation

### Minimum Requirements

RPG-Suite requires:
- WordPress 5.8 or higher
- PHP 7.4 or higher
- BuddyPress 8.0 or higher
- MySQL 5.7 or higher or MariaDB 10.3 or higher
- Node.js 16+ (for development)

### Pre-Activation Build Process

Before activating the plugin, the React application must be built:

```bash
# Build React app for production
cd react-app
npm install
npm run build

# Or use the provided build script
./build.sh
```

### Activation Procedure

When the plugin is activated, it performs these essential tasks:

1. **Requirement Check**: Verify WordPress and PHP versions
2. **BuddyPress Check**: Verify BuddyPress is active
3. **React Build Check**: Verify React app is built
4. **Register Post Types**: Register the character post type with proper capabilities
5. **Register REST Routes**: Set up API endpoints for React
6. **Register Meta Fields**: Register character attribute meta fields
7. **Create Database Tables**: If using custom cache tables
8. **Flush Rewrite Rules**: Update permalink structure
9. **Initialize Cache**: Set up caching infrastructure

### Activation Hook

The plugin uses WordPress's standard activation hook:

```php
register_activation_hook(__FILE__, array('RPG_Suite_Activator', 'activate'));
```

### Post Type Registration with Capabilities

During activation, the character post type is registered with proper capability mapping:

```php
// Fix the capability issue from feedback
register_post_type('rpg_character', [
    'labels' => [...],
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'capability_type' => 'rpg_character',
    'map_meta_cap' => true,
    'capabilities' => [
        'edit_post' => 'edit_rpg_character',
        'read_post' => 'read_rpg_character',
        'delete_post' => 'delete_rpg_character',
        'edit_posts' => 'edit_rpg_characters',
        'edit_others_posts' => 'edit_others_rpg_characters',
        'publish_posts' => 'publish_rpg_characters',
        'read_private_posts' => 'read_private_rpg_characters',
    ],
]);

// Grant capabilities to admin role
$admin_role = get_role('administrator');
if ($admin_role) {
    $admin_role->add_cap('edit_rpg_character');
    $admin_role->add_cap('edit_rpg_characters');
    $admin_role->add_cap('edit_others_rpg_characters');
    $admin_role->add_cap('publish_rpg_characters');
    $admin_role->add_cap('read_private_rpg_characters');
    $admin_role->add_cap('delete_rpg_characters');
}
```

### React App Verification

The activator checks for the React build:

```php
private function check_react_build() {
    $react_build_path = RPG_SUITE_PLUGIN_PATH . 'react-app/build/main.js';
    
    if (!file_exists($react_build_path)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('RPG-Suite requires the React app to be built. Please run npm run build in the react-app directory.', 'rpg-suite'),
            __('Plugin Activation Error', 'rpg-suite'),
            ['back_link' => true]
        );
    }
}
```

### Dependency Handling

If required dependencies are missing:

1. Display an admin notice explaining what's missing
2. Auto-deactivate the plugin
3. Provide links to install required plugins
4. Show instructions for building React app

## Deactivation

When the plugin is deactivated, it:

1. **Preserves Data**: All character data remains in the database
2. **Flushes Rewrite Rules**: Cleans up permalink structure
3. **Clears Caches**: Removes all cache layers
4. **Removes Capabilities**: Cleans up custom capabilities
5. **Preserves React Build**: Keeps compiled assets

### Deactivation Hook

```php
register_deactivation_hook(__FILE__, array('RPG_Suite_Deactivator', 'deactivate'));

// In deactivator class
public static function deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Clear all caches
    $cache_manager = new RPG_Suite_Cache_Manager();
    $cache_manager->clear_all();
    
    // Remove capabilities
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->remove_cap('edit_rpg_character');
        // Remove other capabilities...
    }
}
```

## Uninstallation

When the plugin is deleted, it can optionally remove all plugin data:

1. **Check Setting**: Only delete data if the user has enabled this option
2. **Remove Characters**: Delete all character posts
3. **Remove Options**: Delete all plugin options
4. **Remove User Meta**: Delete related user metadata
5. **Drop Custom Tables**: Remove cache tables if created
6. **Remove React Build**: Clean up build artifacts

### Uninstall File

```php
// In uninstall.php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if we should remove data
$remove_data = get_option('rpg_suite_remove_data_on_uninstall', false);
if (!$remove_data) {
    return;
}

// Delete character posts
$character_posts = get_posts([
    'post_type' => 'rpg_character',
    'numberposts' => -1,
    'fields' => 'ids',
]);

foreach ($character_posts as $post_id) {
    wp_delete_post($post_id, true);
}

// Delete plugin options
delete_option('rpg_suite_version');
delete_option('rpg_suite_cache_version');
delete_option('rpg_suite_remove_data_on_uninstall');

// Delete user meta
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_rpg_%'");

// Drop custom tables if they exist
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rpg_cache");

// Remove React build directory
$upload_dir = wp_upload_dir();
$react_dir = $upload_dir['basedir'] . '/rpg-suite-react';
if (is_dir($react_dir)) {
    recursive_rmdir($react_dir);
}
```

## Version Management

The plugin tracks its version to manage updates:

1. **Version Storage**: Store both plugin and React app versions
2. **Version Check**: Compare stored versions with current versions
3. **Update Process**: Run migrations when versions change
4. **Cache Busting**: Update cache versions for React

```php
public function check_version() {
    $current_version = RPG_SUITE_VERSION;
    $stored_version = get_option('rpg_suite_version', '0.0.0');
    
    if (version_compare($current_version, $stored_version, '>')) {
        $this->run_updates($stored_version, $current_version);
        update_option('rpg_suite_version', $current_version);
        
        // Increment cache version for React
        $cache_version = get_option('rpg_suite_cache_version', 0);
        update_option('rpg_suite_cache_version', $cache_version + 1);
    }
}
```

## Development Workflow

### Local Development Setup

1. **Clone Repository**
2. **Install PHP Dependencies** (if using Composer)
3. **Install React Dependencies**:
   ```bash
   cd react-app
   npm install
   ```
4. **Start Development Server**:
   ```bash
   npm run start
   ```
5. **Build for Testing**:
   ```bash
   npm run build
   ```

### Docker Development (Optional)

```yaml
# docker-compose.yml
services:
  wordpress:
    image: wordpress:latest
    # ... WordPress config
  
  react-builder:
    image: node:16-alpine
    volumes:
      - ./react-app:/app
    working_dir: /app
    command: npm run build:watch
```

## Deployment Process

### Production Build

1. **Update Version Numbers**:
   - Plugin header in `rpg-suite.php`
   - Package.json in `react-app/`
   
2. **Build React App**:
   ```bash
   cd react-app
   npm run build
   ```

3. **Run Tests**:
   ```bash
   npm test
   phpunit
   ```

4. **Create Distribution**:
   ```bash
   ./build.sh --production
   ```

### Continuous Integration

```yaml
# .github/workflows/deploy.yml
name: Build and Deploy
on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
      - run: |
          cd react-app
          npm ci
          npm run build
          npm test
      - run: |
          composer install --no-dev
          ./build.sh --production
```

## Error Handling

The plugin handles errors by:

1. **Build Verification**: Check React build exists
2. **Dependency Validation**: Verify all requirements
3. **Informative Messages**: Display helpful admin notices
4. **Graceful Deactivation**: Auto-deactivate when requirements aren't met
5. **Error Logging**: Log errors for troubleshooting
6. **Fallback Templates**: Provide basic functionality without React

## Testing Methodology

### Activation Testing
1. Test with missing React build
2. Test with missing dependencies
3. Test capability assignment
4. Test database table creation

### Deactivation Testing
1. Verify data preservation
2. Check capability removal
3. Test cache clearing
4. Verify clean deactivation

### Update Testing
1. Test version migrations
2. Verify cache busting
3. Check backward compatibility
4. Test React app updates

## Multisite Support

Enhanced multisite support includes:

1. **Network Activation**: Support plugin activation across all sites
2. **Per-Site Configuration**: Allow configuration on a per-site basis
3. **Shared React Build**: Use single build across network
4. **Site-Specific Caching**: Separate caches per site

## Performance Considerations

1. **Lazy Load React**: Only load on required pages
2. **Asset Optimization**: Minify and compress builds
3. **CDN Support**: Allow serving React from CDN
4. **Conditional Loading**: Check user capabilities before loading

## Security Measures

1. **Build Integrity**: Verify build file checksums
2. **Nonce Validation**: Check nonces in React requests
3. **Capability Checks**: Verify user permissions
4. **Secure Updates**: Use secure update mechanisms

## Monitoring and Maintenance

1. **Health Checks**: Monitor React app status
2. **Error Tracking**: Log JavaScript errors
3. **Performance Metrics**: Track load times
4. **Update Notifications**: Alert admins to updates