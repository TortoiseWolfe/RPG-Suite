# Autoloader Specification - REVISED

## Purpose
This specification defines the autoloader class for the RPG-Suite plugin, which handles class loading based on namespaces and provides PSR-4 compatibility.

## Requirements
1. Implement PSR-4 compatible autoloading
2. Handle class names that contain underscores properly
3. Provide efficient class loading based on namespaces
4. Support plugin directory structure
5. Register with SPL autoloader stack

## Critical Issues Addressed
1. **Underscore Handling**: Previous implementation incorrectly converted underscores to directory separators, causing class loading failures. This is now fixed by ONLY converting namespace separators and preserving underscores in class names.
2. **Namespace Resolution**: Previous implementation had issues with namespace resolution. The revised implementation properly handles namespaces.

## Class Definition

The Autoloader class should:
1. Be named `RPG_Suite_Autoloader`
2. Be defined in file `class-autoloader.php`
3. Have a namespace prefix property set to 'RPG_Suite_'
4. Have a base directory property for plugin classes
5. Initialize the base directory in the constructor
6. Register itself with SPL autoload registry
7. Have a load_class method that:
   - Checks if the class starts with our prefix
   - Gets the relative class name by removing the prefix
   - Converts namespace-like separators to directory separators (for nested directories)
   - Preserves underscores in class names (critical fix)
   - Requires the file if it exists

## Usage Example

In the main plugin file, require the autoloader, initialize it, and register it. This will ensure that:
- Autoloader loads before any other plugin classes
- Class names with underscores will load correctly
- Examples of correctly resolved paths:
  - RPG_Suite_Core_RPG_Suite -> includes/Core/RPG_Suite.php
  - RPG_Suite_Core_Die_Code_Utility -> includes/Core/Die_Code_Utility.php

## Implementation Notes

1. **Class Naming Convention**: All plugin classes should use the `RPG_Suite_` prefix followed by underscore-separated names
2. **Underscore Handling**: The autoloader should NOT convert underscores to directory separators, only handle the transition from RPG_Suite_Core_* to /includes/Core/* directory structure
3. **Underscore Preservation**: Underscores in class names are preserved in file paths, ensuring classes with underscores resolve correctly.
4. **Base Directory**: The autoloader assumes classes are in the `includes` directory.
5. **Class Prefix**: The autoloader only handles classes with the `RPG_Suite_` prefix.

## Testing Recommendations

1. Test autoloading of classes with underscores in their names
2. Test autoloading of classes in nested directories
3. Test edge cases around similar class names
4. Check performance with multiple class loading operations