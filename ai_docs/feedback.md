# RPG-Suite Development Feedback and Challenges

## Critical Issues in Current Implementation

### 1. Autoloader Implementation Issues
- **Description**: Class autoloader fails to properly resolve class paths when classes contain underscores
- **Root cause**: Autoloader implementation incorrectly converts all underscores in class names to directory separators
- **Example**: The class name `RPG_Suite\Core\RPG_Suite` is being converted to file path `includes/Core/RPG/Suite.php` instead of `includes/Core/RPG_Suite.php`
- **Impact**: Fatal errors when classes cannot be found, particularly with core classes that have underscores
- **Solution**: Update autoloader to ONLY convert namespace separators to directory separators, preserving underscores in class names

### 2. Character Post Type Capability Conflicts
- **Description**: Character editing fails with permission errors and conflicts with GamiPress
- **Root cause**: Character post type is registered with default post capabilities instead of custom capabilities
- **Impact**: 
  - Edit screens show "You attempted to edit an item that doesn't exist"
  - GamiPress badges override character URLs
  - Character switching functionality fails
- **Example**: Using `'capability_type' => 'post'` instead of `'capability_type' => 'rpg_character', 'map_meta_cap' => true`
- **Solution**: Register post type with custom capability type and enable map_meta_cap

### 3. BuddyPress Integration Failure
- **Description**: Characters don't display in BuddyPress profiles, especially in BuddyX theme
- **Root causes**:
  - Integration hooks registered at the wrong timing in BuddyPress lifecycle
  - Character display not properly targeting BuddyX theme structure
  - Hooks inconsistently registered in multiple places
- **Impact**:
  - Characters invisible in BuddyPress profiles
  - Edit links redirect to wrong pages
  - Character switching doesn't work
- **Solution**: Register hooks at correct timing (bp_init with priority 20), implement multiple display methods including JavaScript injection

### 4. Character Meta Field Auth Callbacks
- **Description**: Meta field auth callbacks using incorrect capability checks
- **Root cause**: Using generic capabilities instead of post-specific ones
- **Example**: Using `'auth_callback' => function() { return current_user_can('edit_posts'); }` instead of specific capabilities for the character post type
- **Impact**: Permission issues when editing character meta fields
- **Solution**: Update auth callbacks to check post type and use the appropriate capability

### 5. URL Conflicts
- **Description**: URLs for character editing conflict with other plugins (GamiPress)
- **Root causes**:
  - Similar URL structures between plugins
  - Improper use of `get_edit_post_link()` function
- **Impact**:
  - Clicking "Edit Character" leads to GamiPress badge editor
  - Character management interfaces inaccessible
- **Solution**: Use direct admin URLs instead of get_edit_post_link()

## Implementation Requirements

### Autoloader Requirements
1. ONLY convert namespace separators (`\`) to directory separators, not underscores
2. Preserve underscores in class names when resolving to file paths
3. Use a fixed namespace prefix of `RPG_Suite\\`

### Post Type Requirements
1. Use custom capability type: `'capability_type' => 'rpg_character'`
2. Enable capability mapping: `'map_meta_cap' => true`
3. Explicitly assign custom capabilities to administrator during activation
4. Custom URL handling for editing to avoid conflicts

### BuddyPress Integration Requirements
1. Register hooks at `bp_init` with priority 20 (after BuddyPress is fully loaded)
2. Implement multiple display methods for different themes
3. Add direct DOM injection for BuddyX theme
4. Use multiple hook points to ensure display works
5. Implement proper character information display in profile

### Meta Registration Requirements
1. Use post-specific capability checks in auth callbacks
2. Verify post type before checking capabilities
3. Implement correct auth callback structure:
   - Check if post is the correct type
   - Check if user has the specific capability for that post
   - Only then allow access

## Version Development Strategy

For version 0.3.0, focus on minimal working implementation:
1. Fix critical autoloader to correctly handle underscores
2. Fix character post type registration with proper capabilities
3. Implement reliable BuddyPress profile integration
4. Fix meta field authorization
5. Fix URL conflicts

Defer complex features to later versions:
- Dice rolling system
- Invention system
- Advanced character sheets
- Templates and styling