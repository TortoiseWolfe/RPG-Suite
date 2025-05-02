# RPG Suite for WordPress

A comprehensive WordPress plugin package that transforms standard WordPress sites into interactive RPG/tabletop-style adventure games, leveraging BuddyPress for the social layer.

## Overview

RPG Suite provides a modular system with toggle-able subsystems that site admins can activate or deactivate from a central dashboard. The plugin is designed with a clean architecture, separation of concerns, and extensibility in mind.

## Requirements

- WordPress 6.8+
- PHP 8.2+
- BuddyPress (latest stable)
- BuddyX Theme (recommended)

## Features

RPG Suite includes the following subsystems:

| Subsystem | Purpose | Key Features | Default State |
|-----------|---------|--------------|---------------|
| **Core** | Foundation & API gateway | Role bridge (Player/GM), unified settings, autoload, event dispatcher | **Always On** |
| **Health** | Stat engine | HP CRUD, status effects, REST endpoints | Enabled |
| **Geo** | Position & movement | Map widget, zone CPT, privacy toggle | Enabled |
| **Dice** | Randomisation utilities | Polyhedral dice, skill checks, advantage/disadv. | Enabled |
| **Inventory** | Items & equipment | Item CPT, weight, slots, drag‑drop UI | Enabled |
| **Combat** | Turn‑based encounters | Initiative, attack formulas, combat log | Enabled |
| **Quest** | Narrative & rewards | Branching quests, reward hooks | *Opt-in* |

## Architecture

RPG Suite follows a modern WordPress plugin architecture:

- **Event Bus**: Symfony-style dispatcher wrapped around `do_action` for strong typing
- **Capabilities Matrix**: Fine-grained capabilities mapped to WordPress roles
- **REST API**: Complete API for headless or external control
- **Custom Post Types**: `rpg_character`, `rpg_item`, `rpg_zone`, `rpg_quest`, `rpg_encounter`
- **Custom Tables**: For complex relationships and performance-critical data

## Installation

1. Download the latest release
2. Upload to your WordPress plugins directory
3. Activate the plugin through the WordPress admin panel
4. Visit the RPG Suite dashboard to configure subsystems

## Development

### Setup

```bash
# Clone the repository
git clone https://github.com/your-username/rpg-suite.git
cd rpg-suite

# Install dependencies
composer install
npm install

# Run the development build
npm run dev
```

### Testing

```bash
# Run PHP tests
composer test

# Run JavaScript tests
npm test
```

## License

GPL-2.0+

## Credits

Developed by [Your Name/Organization]