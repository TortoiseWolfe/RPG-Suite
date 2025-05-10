# Template Loader Specification

## Purpose
The Template Loader provides a mechanism for loading custom templates for RPG-Suite content, allowing theme overrides while maintaining a consistent fallback structure.

## Requirements
1. Support template overrides in themes and child themes
2. Provide fallback templates within the plugin
3. Support templates for character display and management
4. Filter template paths for extensibility
5. Maintain consistent template hierarchy
6. Support BuddyPress theme compatibility

## Class Requirements

The Template Loader class should:

1. Be named `RPG_Suite_Template_Loader`
2. Be defined in file `class-template-loader.php`
3. Maintain template directory paths:
   - Plugin templates directory
   - Theme templates directory

4. Initialize necessary WordPress hooks:
   - template_include filter
   - single_template filter
   - archive_template filter

5. Implement core template methods:
   - template_loader(): Main template handler
   - single_template(): Handle single post templates
   - archive_template(): Handle archive templates
   - get_template_part(): Load template parts (partials)
   - locate_template(): Find template files in hierarchy
   - get_template(): Load templates with arguments

6. Support proper template hierarchy:
   - Check child theme first
   - Then parent theme
   - Fall back to plugin templates
   - Apply appropriate filters for extensibility

## Method Descriptions

### Template Loader

The template_loader() method should:
- Accept current template path parameter
- Check for embed context and skip processing if found
- Get queried object to determine content type
- For rpg_character singular views, return character single template
- For rpg_character archives, return character archive template
- For rpg_invention singular views, return invention single template
- For rpg_invention archives, return invention archive template
- Return unchanged template for other content types

### Single Template Handler

The single_template() method should:
- Accept current template path parameter
- Check global $post object for post type
- For rpg_character, return character single template
- For rpg_invention, return invention single template
- Return unchanged template for other post types

### Archive Template Handler

The archive_template() method should:
- Accept current template path parameter
- Check for rpg_character post type archives
- Check for rpg_invention post type archives
- Return appropriate template path or default

### Template Path Resolution

The get_template_path() method should:
- Accept template name and default path parameters
- Check theme directories first using locate_template()
- Look in theme rpg-suite directory and root
- Fall back to plugin template directory
- Verify file existence before returning path
- Return default path if no template found

### Template Parts

The get_template_part() method should:
- Accept slug, name, and args parameters
- Check theme for slug-name.php first
- Check plugin for slug-name.php
- Fall back to slug.php in theme
- Fall back to slug.php in plugin
- Apply rpg_suite_get_template_part filter
- Load template with arguments when found

### Template Location

The locate_template() method should:
- Accept template name, path, and default path parameters
- Use default theme and plugin paths if not provided
- Look in theme paths first (priority)
- Fall back to plugin default path
- Apply rpg_suite_locate_template filter

### Template Loading

The get_template() method should:
- Accept template name, args, path and default path parameters
- Locate template using locate_template method
- Verify file existence
- Apply rpg_suite_get_template filter
- Fire before/after template part actions
- Extract args to make them available as variables
- Include located template

## Template Hierarchy

The template hierarchy follows this order of precedence:

1. Child Theme: `/rpg-suite/[template-name].php`
2. Parent Theme: `/rpg-suite/[template-name].php`
3. Child Theme: `/[template-name].php`
4. Parent Theme: `/[template-name].php`
5. Plugin: `/templates/[template-name].php`

## Default Templates Provided

The plugin includes these default templates:

1. **Archive Templates**
   - `archive-character.php` - Character listings
   - `archive-invention.php` - Invention listings

2. **Single Templates**
   - `single-character.php` - Single character display
   - `single-invention.php` - Single invention display

3. **Template Parts**
   - `content-character.php` - Character content
   - `content-invention.php` - Invention content
   - `character-attributes.php` - Character attributes display
   - `character-skills.php` - Character skills display
   - `character-inventions.php` - Character inventions list

## BuddyPress Integration

For BuddyPress compatibility, the template loader should:

1. Include an is_buddypress_theme() method to check compatibility
2. Check if the current theme supports BuddyPress
3. Add special handling for BuddyPress-specific themes like bp-default and buddyx
4. Use BuddyPress-specific templates when appropriate
5. Respect the BuddyPress template hierarchy
6. Fall back to plugin templates when needed

## Usage Examples

### Using Template Parts

To use template parts in a theme:
- Access template loader via global $rpg_suite
- Use get_template_part method with appropriate slug and name
- Pass relevant arguments like character_id
- Display character attributes, skills, and other components

### Creating a Custom Template

To override plugin templates in a theme, files should be created in:
```
mytheme/
└── rpg-suite/
    ├── single-character.php
    ├── archive-character.php
    └── character-attributes.php
```

## Implementation Notes

1. **Theme Compatibility**: The template system respects theme hierarchy
2. **Extensibility**: All template paths can be filtered by plugins
3. **BuddyPress Support**: Special handling for BuddyPress themes
4. **Template Arguments**: Arguments can be passed to templates
5. **Action Hooks**: Pre/post template loading hooks for extensions
6. **Fallback System**: Default templates ensure content always displays
7. **Performance**: Template paths are efficiently located and cached
8. **Class Naming**: Follows the RPG_Suite_ prefix convention for consistency