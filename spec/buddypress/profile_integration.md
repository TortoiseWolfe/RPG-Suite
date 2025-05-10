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

The Profile Display class should:
1. Be named `RPG_Suite_Profile_Display`
2. Be defined in file `class-profile-display.php`
3. Have dependencies on RPG_Suite_Character_Manager and RPG_Suite_Die_Code_Utility
4. Initialize with proper constructor parameters
5. Register hooks for:
   - Displaying character info in profile header
   - Setting up character navigation tabs
   - Enqueueing CSS and JavaScript assets
6. Implement methods for:
   - display_character_info(): Shows active character in profile
   - setup_character_nav(): Adds character management tab
   - enqueue_assets(): Loads styles for BP profiles
   - display_character_screen(): Shows character management screen

**Critical: Hook registration must occur at the correct time in BuddyPress lifecycle**

### BuddyPress Integration Class

The BuddyPress Integration class should:
1. Be named `RPG_Suite_BuddyPress_Integration`
2. Be defined in file `class-buddypress-integration.php`
3. Have dependencies on:
   - RPG_Suite_Character_Manager
   - RPG_Suite_Event_Dispatcher
   - RPG_Suite_Die_Code_Utility
4. Store a Profile_Display instance
5. Initialize the integration with these steps:
   - Check if BuddyPress is active
   - Create the Profile_Display instance
   - **CRITICAL: Register hooks on 'bp_init' with priority 20** to ensure BP is fully loaded
   - Register event subscribers
6. Implement methods:
   - initialize(): Sets up the integration
   - register_hooks(): Calls the Profile Display hook registration
   - register_event_subscribers(): Sets up event listening

**This timing issue is critical - hooks must be registered after BuddyPress is fully loaded**

## Profile Display Implementation

### Character Information Display

The display_character_info() method should:

1. Get the displayed user ID using bp_displayed_user_id()
2. Retrieve the active character using the character manager
3. Display a message if no character is active
4. If a character exists, display:
   - Character name with proper linking
   - Character class
   - Character attributes with formatted values
   - Featured invention if available
5. Use proper HTML structure with appropriate CSS classes
6. Apply proper escaping for all output

**CRITICAL: The following implementations must also be added:**
1. Multiple hook points for different theme compatibility
2. Fallback JavaScript injection for compatibility with BuddyX theme
3. Direct DOM targeting for BuddyX-specific elements
4. Debug logging to track when and if display functions are called

### Character Management Tab

The setup_character_nav() method should:

1. Check permissions before adding tabs (only show for profile owner or admin)
2. Add a main navigation item for Characters
3. Add sub-navigation items:
   - Manage Characters (default)
   - Inventions
4. Set up proper screen functions for each tab
5. Ensure URLs are correctly generated with bp_displayed_user_domain()
6. Position tabs appropriately in the navigation menu

### Character Switching Interface

The display_character_content() method should:

1. Get the current user's characters from the character manager
2. Identify which character is currently active
3. Process character switching form submissions with nonce verification
4. Display a list of the user's characters with:
   - Character name and class
   - Active/inactive status
   - Activation button for inactive characters
   - Character attributes with proper d7 dice notation
   - Count of inventions for each character
5. Show a button to create a new character if under the character limit
6. Apply proper styling and HTML structure for the interface

The display_inventions_screen() method should:
1. Set up the proper BuddyPress template structure
2. Load the inventions content via template hooks

The display_inventions_content() method should:
1. Get the active character for the current user
2. Display a message if no active character exists
3. Show a list of the character's inventions with:
   - Invention name and complexity
   - Description and effects
   - Components list
4. Include a button to create new inventions for the profile owner

## CSS Integration

### Profile Display Styling

The enqueue_assets() method should:

1. Check if the current page is a BuddyPress user profile
2. Conditionally enqueue CSS for BuddyPress integration
3. Include appropriate dependencies and version information
4. Register the CSS with WordPress properly

### BuddyX Theme Compatibility

CSS should be provided for specific BuddyX theme compatibility including:

1. Styling for the character info container
2. Character name styling that respects theme colors
3. Attribute display formatting (flex layout)
4. Proper spacing and margins for all elements
5. Color scheme integration with theme variables
6. Responsive design considerations
7. Character management interface styling

**CRITICAL: BuddyX compatibility requires both appropriate CSS and JavaScript targeting of theme-specific elements**

## Event Integration

### BuddyPress Character Subscriber

The BuddyPress Character Subscriber class should:

1. Be named `RPG_Suite_BuddyPress_Character_Subscriber`
2. Be defined in file `class-buddypress-character-subscriber.php`
3. Implement the RPG_Suite_Event_Subscriber interface
4. Have a dependency on RPG_Suite_Profile_Display
5. Subscribe to these events:
   - character_activated
   - character_updated
   - character_deleted
   - invention_created
   - invention_updated
6. Implement event handlers for each event type
7. Handle profile cache refreshing and UI updates as needed

## Implementation Notes

1. **Hook Registration Timing**: Hooks must be registered at the correct time in the BuddyPress lifecycle, using bp_init with priority 20 to ensure BuddyPress is fully loaded

2. **Component Access**: Character data should be accessible through the global plugin instance with proper method calls

3. **Permission Checks**: Always verify user permissions before displaying management UI by checking bp_is_my_profile() and current_user_can()

4. **BuddyX Compatibility**: Ensure compatibility with the BuddyX theme using both specific CSS classes and JavaScript DOM targeting

5. **d7 System Display**: Present d7 dice codes in a clear format for character attributes

6. **Security**: Always validate and sanitize input/output with WordPress functions and nonce verification

7. **Multiple Hook Points**: Register display functions on multiple hook points to ensure compatibility with different themes

8. **URL Handling**: Ensure character edit URLs use direct admin URL construction rather than get_edit_post_link() to avoid conflicts with GamiPress

9. **Class Naming**: All class names follow the RPG_Suite_ prefix convention for consistency