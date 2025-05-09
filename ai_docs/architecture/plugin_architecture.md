# RPG-Suite Plugin Architecture

## High-Level Architecture

RPG-Suite follows a modular architecture with clear separation of concerns:

```
RPG-Suite/
├── rpg-suite.php              # Plugin main file
├── uninstall.php              # Clean uninstallation procedures
├── includes/
│   ├── class-autoloader.php   # PSR-4 autoloader
│   ├── class-rpg-suite.php    # Main plugin class
│   ├── class-activator.php    # Plugin activation logic
│   │   └── database-schema.php # Database schema definitions
│   ├── class-deactivator.php  # Plugin deactivation logic
│   ├── core/                  # Core functionality
│   │   ├── interface-event-subscriber.php # Event listener interface
│   │   ├── class-event.php              # Event object
│   │   ├── class-event-dispatcher.php    # Event system
│   │   ├── class-die-code-utility.php   # D7 dice utility
│   │   └── class-error-handler.php      # Error handling
│   ├── character/             # Character management
│   │   ├── class-character-manager.php   # Character operations
│   │   ├── class-character-post-type.php # Custom post type registration
│   │   ├── class-character-meta-handler.php # Meta data handling
│   │   └── class-invention-manager.php   # Invention management
│   ├── admin/                 # Admin functionality
│   │   ├── class-admin.php             # Admin main class
│   │   ├── class-character-editor.php   # Character editing UI
│   │   └── class-settings.php          # Plugin settings
│   ├── integrations/          # Third-party integrations
│   │   ├── class-integration-manager.php # Manages integrations
│   │   ├── buddypress/        # BuddyPress integration
│   │   │   ├── class-buddypress-integration.php # Main integration class
│   │   │   ├── class-profile-display.php  # Profile display logic
│   │   │   └── class-buddypress-character-subscriber.php # Event subscriber
│   │   └── ...
│   └── utils/                 # Utility functions
│       ├── class-template-loader.php     # Template loading
│       └── functions.php                 # Helper functions
└── assets/                    # Frontend assets
    ├── css/
    │   ├── rpg-suite-admin.css          # Admin styles
    │   └── rpg-suite-public.css         # Public styles
    ├── js/
    │   ├── rpg-suite-admin.js           # Admin scripts
    │   └── rpg-suite-public.js          # Public scripts
    └── images/
```

## Component Responsibilities

### Main Plugin Class (`RPG_Suite`)
- Initializes all subsystems
- Provides global access point via `$rpg_suite`
- Manages plugin lifecycle
- Handles plugin requirements and dependencies

### Autoloader
- PSR-4 compliant class loading
- Handles namespace to directory mapping

### Core Subsystem
- Provides fundamental services used by other components
- Implements event system for decoupled communication
- Handles WordPress hooks and their registration
- Provides die code utility for d7 system
- Error handling services

### Character Subsystem
- Manages the character post type
- Handles character creation, editing, and deletion
- Manages relationships between users and characters
- Tracks active character status
- Handles invention management

### Admin Subsystem
- Provides admin UI for character management
- Handles plugin settings
- Registers custom post type meta boxes

### Integration Manager
- Manages third-party integrations
- Handles graceful fallbacks when integrations aren't available

### BuddyPress Integration
- Displays character information on BuddyPress profiles
- Provides character switching interface
- Ensures proper hook registration with BuddyPress

## Communication Patterns

### Event System
Components communicate through an event system to maintain loose coupling:

1. Components can dispatch events
2. Other components can subscribe to events
3. Events carry relevant data between components

Example flow:
- Character Manager changes active character
- "character_activated" event is dispatched
- BuddyPress Profile component updates display in response

### Dependency Injection
- Components receive their dependencies through constructors
- Improves testability and flexibility
- Reduces hard dependencies between components

### Service Locator Pattern
The main plugin instance can be accessed via the global `$rpg_suite` variable or through a static method:
```php
// Global variable approach
global $rpg_suite;
$character_manager = $rpg_suite->get_character_manager();

// Static method approach
$character_manager = RPG_Suite::get_instance()->get_character_manager();
```

## WordPress Integration

### Hook Registration
- Hooks are registered at appropriate points in WordPress lifecycle
- BuddyPress hooks use correct priorities
- Activation/deactivation hooks handle plugin lifecycle

### Custom Post Types
- Character data stored as custom post type
- Custom meta fields for character attributes
- User relationships managed through post meta

### Database Schema
- Custom post types registered for characters and inventions
- Meta tables used for character attributes, skills, and other data
- Proper indexes for performance optimization

## Error Handling Strategy

### Consistent Error Types
- WordPress-style errors using `WP_Error` objects
- Descriptive error codes and messages
- Function return values indicate success/failure

### Graceful Fallbacks
- Check for dependencies before executing dependent code
- Provide meaningful error messages
- Degrade functionality gracefully when dependencies are missing

## Plugin Lifecycle

### Activation
- Register custom post types
- Create database tables if needed
- Set default options
- Check requirements (WordPress version, BuddyPress, etc.)

### Deactivation
- Clean up temporary data
- Flush rewrite rules
- Maintain user data for later reactivation

### Uninstallation
- Remove all plugin data if configured
- Remove custom post types and their data
- Remove plugin options

## Internationalization

All user-facing strings use WordPress translation functions:
- `__()` for simple translations
- `_e()` for translated output
- `_n()` for plurals
- `_x()` for translations with context

Text domain loaded early in plugin initialization.

## Asset Management

### CSS/JS Enqueueing
- Assets loaded only when needed
- Admin scripts only loaded on admin pages
- Public scripts only loaded on relevant public pages
- Dependencies properly declared
- Versioning for cache busting

### CSS Organization
- BuddyX theme compatibility through variable usage
- Scoped selectors to prevent conflicts

## Testing Strategy

### Unit Testing
- PHP Unit for testing utility classes
- Separation of logic from WordPress specifics for better testability

### Integration Testing
- WordPress test suite for WordPress integration
- Mock objects for external dependencies

### Manual Testing Checklist
- Plugin activation/deactivation
- Character creation, editing, deletion
- BuddyPress profile integration
- Admin interface
- Frontend display

## Coding Standards

The plugin follows WordPress Coding Standards with PSR-4 class loading:
- WordPress naming conventions for functions and hooks
- PSR-4 for class names and namespaces
- Comprehensive inline documentation
- PHPDoc blocks for all classes and methods