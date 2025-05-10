# Main Plugin Class Specification

## Purpose
The main plugin class (`RPG_Suite`) serves as the central access point and initialization mechanism for the RPG-Suite plugin.

## Requirements
1. Create a singleton instance accessible via global variable
2. Initialize all subsystems in the correct order
3. Provide access to core components via public properties
4. Handle plugin hooks and lifecycle events
5. Maintain backward compatibility with any existing code

## Class Definition

## Main Plugin Class Requirements

The main plugin class should:

1. Be named `RPG_Suite` and defined in file `class-rpg-suite.php`
2. Implement a singleton pattern with:
   - Private static instance property
   - Private constructor to prevent direct instantiation
   - Public static get_instance() method

3. Maintain public properties for:
   - Plugin version
   - Plugin name
   - Character manager reference (RPG_Suite_Character_Manager)
   - Event dispatcher reference (RPG_Suite_Event_Dispatcher)
   - Other core component references

4. Include initialization methods:
   - load_dependencies() - Load required files
   - initialize_core() - Set up core components
   - initialize_character_system() - Set up character management
   - initialize_integrations() - Set up plugin integrations

## Usage Example

The main plugin class should be accessible globally:

1. In the plugin main file, initialize the global instance after the autoloader
2. Register global $rpg_suite variable to provide access
3. Enable component access from anywhere in the codebase via the global variable
4. Allow direct method calls to component functions through the global instance

## Implementation Notes
1. The constructor should be private to enforce singleton pattern
2. Component initialization order matters:
   - Core components first (event system, etc.)
   - Character system next
   - Integrations last (as they may depend on other systems)
3. Public properties should be set during initialization
4. Hook registration should happen in the initialization methods
5. The global variable should be registered early
6. Component instances should be stored as public properties for easy access
7. Class naming follows the RPG_Suite_ prefix convention for all plugin classes