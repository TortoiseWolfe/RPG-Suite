# RPG-Suite Plugin Architecture

**Author:** TurtleWolfe
**Repository:** https://github.com/TortoiseWolfe/RPG-Suite

## High-Level Architecture

RPG-Suite follows a hybrid architecture combining traditional WordPress PHP backend with modern React-based frontend for character sheets. This approach provides optimal performance for dynamic character data while maintaining full BuddyPress/BuddyX compatibility.

### Directory Structure

```
RPG-Suite/
├── rpg-suite.php              # Plugin main file
├── uninstall.php              # Clean uninstallation procedures
├── includes/                  # Core PHP functionality
│   ├── core/                  # Core classes
│   │   ├── class-autoloader.php
│   │   ├── class-rpg-suite.php
│   │   ├── class-activator.php
│   │   └── class-deactivator.php
│   ├── api/                   # REST API endpoints
│   │   ├── class-character-api.php
│   │   └── class-cache-api.php
│   ├── character/             # Character management
│   │   ├── class-character-manager.php
│   │   └── class-character-meta-handler.php
│   ├── cache/                 # Caching layer
│   │   └── class-cache-manager.php
│   └── integrations/          # Third-party integrations
│       └── buddypress/
│           └── class-buddypress-integration.php
├── react-app/                 # React frontend
│   ├── src/
│   │   ├── components/       # React components
│   │   ├── hooks/           # Custom React hooks
│   │   ├── store/           # State management
│   │   └── api/             # API client
│   ├── build/               # Compiled assets
│   └── package.json
├── assets/                   # Legacy assets
│   ├── css/
│   └── js/
├── docker/                   # Docker configurations
│   └── react-builder/
└── languages/               # Internationalization
```

## Development Approach

The architecture combines WordPress best practices with modern frontend development:

1. **Phase 1: Core Plugin Structure & Fixes**
   - Fix character post type capabilities
   - Implement missing core classes
   - Basic admin interface
   - Multi-layer caching foundation

2. **Phase 2: REST API & Caching**
   - RESTful endpoints for character data
   - Authentication and permissions
   - Cache management API
   - Response optimization

3. **Phase 3: React Character Sheet**
   - Component architecture
   - State management setup
   - Real-time updates
   - Performance optimization

4. **Phase 4: BuddyPress Integration**
   - React components in BuddyPress
   - Character switching UI
   - Profile enhancements
   - BuddyX theme compatibility

5. **Phase 5: Advanced Features**
   - Event system with WebSocket support
   - Die code utility with visual rolls
   - Invention system UI
   - Advanced caching strategies

## Component Responsibilities

### Main Plugin Class (`RPG_Suite`)
- Initializes the plugin
- Provides global access point via `$rpg_suite`
- Registers core hooks
- Initializes key components as public properties

### Autoloader
- Handles class loading
- Maps namespaces to directories
- Uses PSR-4 convention

### Character System
- Registers the character post type with appropriate capabilities
- Handles character metadata storage and retrieval
- Manages active character status for users
- Supports multiple characters per player (limit of 2 by default)

### BuddyPress Integration
- Displays active character on BuddyPress profiles
- Hooks into BuddyPress at the correct time in the lifecycle
- Provides character switching UI

## WordPress Integration

### Character Post Type
- Uses standard post capabilities for simplicity
- Ensures proper post editing in WordPress admin
- Registers meta fields for character attributes

```
register_post_type('rpg_character', [
    'labels' => [...],
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,  // Enable block editor support
    'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
    'has_archive' => false,
    'capability_type' => 'post',  // Use standard post capabilities
    'map_meta_cap' => true,
]);
```

### Meta Fields
- Register character attributes as post meta
- Use standard meta API for compatibility

## Global Accessibility

The plugin provides multiple access patterns:

- PHP: Global `$rpg_suite` variable for backend access
- React: Context providers for frontend state
- REST API: Standardized endpoints for data access
- Events: Custom event system for cross-component communication
- Cache: Unified caching layer for all data types

## Implementation Priorities

1. **Post Type Functionality**
   - Ensure character post type is properly registered
   - Verify editing works correctly in WordPress admin
   - Ensure text is properly visible in editor

2. **Character Management**
   - Store character ownership
   - Allow multiple characters per player
   - Track active character status

3. **BuddyPress Display**
   - Show active character on profile
   - Enable character switching

## Frontend Implementation

### React Components
- Modular component architecture
- Lazy loading for performance
- Error boundaries for stability
- Memoization for optimization

### Styling Strategy
- CSS Modules for component isolation
- Styled-components for dynamic styles
- BuddyX theme compatibility
- Responsive design principles
- Dark mode support

## Testing Strategy

### Backend Testing
- PHPUnit for WordPress integration tests
- REST API endpoint testing
- Cache layer verification
- Performance benchmarking

### Frontend Testing
- Jest for React component tests
- React Testing Library for user interactions
- Cypress for E2E testing
- Performance profiling

### Integration Testing
- BuddyPress compatibility
- Character switching flows
- Real-time update verification
- Cross-browser testing

## Coding Standards

The plugin follows WordPress Coding Standards:
- WordPress naming conventions for functions and hooks
- PSR-4 for class names and namespaces
- Comprehensive inline documentation