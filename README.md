# RPG-Suite

## Project Overview

RPG-Suite is a modular WordPress plugin for implementing RPG (Role-Playing Game) mechanics on WordPress sites using BuddyPress for social integration. The plugin allows users to create and manage characters, earn experience points, and unlock additional features through gameplay.

## Core Design Principles

1. **Modular Architecture**: Subsystems can be enabled/disabled independently
2. **Progressive Enhancement**: Features unlock through gameplay, not paywalls
3. **Clean Integration**: Works with BuddyPress and BuddyX theme without modifications
4. **Event-Driven**: Uses a Symfony-style event dispatcher wrapped around WordPress hooks

## Subsystems

### Core
- Event system for inter-subsystem communication
- Character management with multiple characters per user
- Experience point system with progression tracking
- BuddyPress profile integration

### Health (Planned)
- Character health points and status effects
- Visual health display in character profiles
- Damage application and healing

### Geo (Planned)
- Character positioning and map zones
- Zone definitions with properties
- Character movement between zones

### Dice (Planned)
- Dice notation parsing and execution
- Roll tracking and history
- Skill checks with difficulty classes

### Inventory (Planned)
- Item management and equipment
- Character inventory tracking
- Item properties and effects

### Combat (Planned)
- Turn-based encounter system
- Initiative tracking
- Combat actions and resolution

### Quest (Planned)
- Narrative quest structures
- Quest tracking and objectives
- Reward system integration

## Technical Architecture

### Plugin Structure
```
rpg-suite/
├── rpg-suite.php                  # Main plugin file with initialization 
├── includes/                      # Core plugin infrastructure
│   ├── class-rpg-suite.php        # Main plugin class
│   ├── class-activator.php        # Plugin activation handling
│   ├── class-deactivator.php      # Plugin deactivation handling
│   └── class-autoloader.php       # PSR-4 autoloader
├── src/                           # Plugin subsystems and components
│   ├── Core/                      # Core subsystem
│   │   ├── class-core.php         # Core functionality
│   │   ├── class-event.php        # Base event class
│   │   ├── class-event-dispatcher.php  # Event dispatcher
│   │   ├── class-event-subscriber.php  # Event subscriber interface
│   │   └── Components/            # Core components
│   │       └── class-profile-integration.php  # BuddyPress integration
│   └── Character/                 # Character subsystem
│       ├── class-character-manager.php  # Character management
│       └── class-character-post-type.php  # Character post type definition
├── templates/                     # Template files
│   └── profile/                   # BuddyPress profile templates
├── assets/                        # CSS, JS, and images
│   ├── css/
│   ├── js/
│   └── images/
└── languages/                     # Translations
```

### Class Namespaces
- `RPG\Suite\Includes` - Core plugin infrastructure
- `RPG\Suite\Core` - Core subsystem
- `RPG\Suite\Character` - Character management
- `RPG\Suite\Health` - Health subsystem
- `RPG\Suite\Geo` - Geo subsystem
- `RPG\Suite\Dice` - Dice subsystem
- `RPG\Suite\Inventory` - Inventory subsystem
- `RPG\Suite\Combat` - Combat subsystem
- `RPG\Suite\Quest` - Quest subsystem

## Character System

### Character Classes
The plugin includes the following steampunk-themed character classes:

1. **Sky Captain**
   - Masters of airship navigation and aerial combat
   - Focus on Dexterity and Intelligence
   - Equipped with navigational tools and service weapons

2. **Inventor**
   - Brilliant minds who create mechanical marvels
   - Focus on Intelligence and Wisdom
   - Equipped with portable toolkits and custom gadgets

3. **Diplomat**
   - Shrewd negotiators navigating complex politics
   - Focus on Charisma and Intelligence
   - Equipped with official documents and cipher devices

4. **Mechanic**
   - Skilled technicians maintaining complex machinery
   - Focus on Strength and Constitution
   - Equipped with steam-powered tools and reinforced gloves

### Progression System
Players earn experience points through gameplay to unlock features:

| Feature | XP Required | Description |
|---------|-------------|-------------|
| Character Editing | 1,000 | Edit character name and reallocate attribute points |
| Character Respawn | 2,500 | Revive fallen characters with penalties |
| Multiple Characters | 5,000 | Create up to 3 characters |
| Character Switching | 7,500 | Switch between your characters |
| Advanced Customization | 10,000 | Access to full character customization |

## BuddyPress Integration

The plugin integrates with BuddyPress to display character information in user profiles:

- Character display in profile header
- Character status and attributes
- Character class and level
- Character switching for users with multiple characters
- Character profile tab with detailed information

### Theme Compatibility
- Compatible with standard BuddyPress themes
- Special support for BuddyX theme
- Multiple hook points for maximum compatibility
- CSS targeting specific theme elements

## Development Guidelines

### Coding Standards
- Follow WordPress PHP Coding Standards
- PSR-4 autoloading
- Object-oriented approach
- Proper documentation

### Version Control
- Feature branches for new functionality
- Pull requests for code reviews
- Semantic versioning (MAJOR.MINOR.PATCH)

### Testing
- Test character creation and management
- Test BuddyPress integration
- Test feature unlocking through XP
- Test with multiple character classes
- Test death and respawn mechanics

## Installation (Development)

1. Clone this repository into your WordPress plugins directory
2. Run `composer install` to install dependencies
3. Activate the plugin through the WordPress admin

## Implementation Roadmap

1. **Phase 1**: Core infrastructure and autoloading
2. **Phase 2**: Character management system
3. **Phase 3**: BuddyPress profile integration
4. **Phase 4**: Experience and progression system
5. **Phase 5**: Additional subsystems (Health, Geo, etc.)

## Contributing

Please read the CONTRIBUTING.md file for details on contributing to this project.

## License

This project is licensed under the GPL v2 or later - see the LICENSE file for details.