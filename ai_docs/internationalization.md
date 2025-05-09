# Internationalization (i18n) Guidelines

## Overview

RPG-Suite fully supports internationalization to make the plugin available to a global WordPress audience. Following WordPress best practices, all user-facing strings should be properly prepared for translation.

## Text Domain

The plugin uses the text domain `rpg-suite` for all translations. This text domain is declared in the plugin header:

```php
/**
 * Plugin Name: RPG-Suite
 * Plugin URI: https://example.com/rpg-suite
 * Description: A role-playing game plugin with d7 system for WordPress and BuddyPress.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: rpg-suite
 * Domain Path: /languages
 */
```

## Loading Text Domain

The text domain is loaded during plugin initialization:

```php
/**
 * Load plugin text domain.
 *
 * @since 1.0.0
 * @return void
 */
public function load_textdomain() {
    load_plugin_textdomain(
        'rpg-suite',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
```

This function is hooked into WordPress's `plugins_loaded` action:

```php
add_action('plugins_loaded', array($this, 'load_textdomain'));
```

## Translation Functions

### Basic String Translation

For simple strings, use the `__()` function:

```php
$message = __('Character saved successfully.', 'rpg-suite');
```

### Outputting Translated Strings

When directly outputting a translated string, use the `_e()` function:

```php
_e('Create New Character', 'rpg-suite');
```

### String With Context

For strings that might need disambiguation in different contexts, use `_x()`:

```php
$label = _x('Attributes', 'character sheet section heading', 'rpg-suite');
```

### Pluralization

For strings that change based on numbers, use `_n()`:

```php
$message = sprintf(
    _n(
        '%d invention created.',
        '%d inventions created.',
        $count,
        'rpg-suite'
    ),
    $count
);
```

### Escaping Translated Strings

Always escape output properly. WordPress provides translation-specific escaping functions:

```php
// For HTML attributes
echo esc_attr_e('Activate', 'rpg-suite');

// For HTML content
echo esc_html_e('Character saved successfully.', 'rpg-suite');

// For URLs
$url = esc_url(__('https://example.com/help', 'rpg-suite'));
```

## String Format Guidelines

### Consistent Voice and Terminology

- Use present tense, active voice
- Be consistent with terminology across all strings
- Use sentence case for most UI text

### Variables in Strings

For strings containing variables, use placeholders and `sprintf()`:

```php
$message = sprintf(
    __('Character %s activated successfully.', 'rpg-suite'),
    esc_html($character_name)
);
```

For multiple variables, use numbered placeholders:

```php
$message = sprintf(
    __('Character %1$s created by %2$s.', 'rpg-suite'),
    esc_html($character_name),
    esc_html($author_name)
);
```

### HTML in Strings

Keep HTML minimal in translatable strings:

```php
// Good: Separate HTML from translatable text
printf(
    '<p class="message">%s</p>',
    esc_html__('Character saved successfully.', 'rpg-suite')
);

// Avoid: HTML mixed with translatable text
_e('<p class="message">Character saved successfully.</p>', 'rpg-suite');
```

## Translation Files

### Directory Structure

Translation files are stored in the `/languages` directory:

```
RPG-Suite/
└── languages/
    ├── rpg-suite.pot       # Template file
    ├── rpg-suite-fr_FR.po  # French translation
    ├── rpg-suite-fr_FR.mo  # French translation (compiled)
    ├── rpg-suite-de_DE.po  # German translation
    └── rpg-suite-de_DE.mo  # German translation (compiled)
```

### Generating POT File

The POT (Portable Object Template) file can be generated using WP-CLI:

```bash
wp i18n make-pot . languages/rpg-suite.pot --domain=rpg-suite
```

Or using a tool like Poedit or GlotPress.

## JavaScript Internationalization

For JavaScript files, use WordPress's `wp_localize_script()` function to pass translated strings:

```php
function enqueue_scripts() {
    wp_enqueue_script(
        'rpg-suite-admin',
        plugin_dir_url(__FILE__) . 'assets/js/admin.js',
        array('jquery'),
        RPG_SUITE_VERSION,
        true
    );
    
    wp_localize_script(
        'rpg-suite-admin',
        'rpgSuiteI18n',
        array(
            'saveSuccess' => __('Character saved successfully.', 'rpg-suite'),
            'saveError' => __('Error saving character.', 'rpg-suite'),
            'invalidDieCode' => __('Invalid die code format. Please use the format "XdY+Z" (e.g., "3d7+2").', 'rpg-suite'),
            'confirmDelete' => __('Are you sure you want to delete this character?', 'rpg-suite'),
        )
    );
}
```

Then in JavaScript:

```javascript
function saveCharacter() {
    // ...
    if (response.success) {
        alert(rpgSuiteI18n.saveSuccess);
    } else {
        alert(rpgSuiteI18n.saveError);
    }
}
```

## RTL (Right-to-Left) Support

The plugin supports RTL languages through CSS:

```css
/* RTL styles */
.rtl .rpg-character-attributes {
    flex-direction: row-reverse;
}

.rtl .rpg-attribute-name {
    margin-right: 0;
    margin-left: 5px;
}
```

## Testing Translations

During development, test with language debugging enabled:

```php
// Add to wp-config.php during development
define('WP_DEBUG_DISPLAY', true);
define('WP_DEBUG', true);
define('WPLANG', 'fr_FR'); // Test with a specific locale
```

## Translation Workflow

1. Make all strings translatable during development
2. Generate/update the POT file before releases
3. Provide translators with the updated POT file
4. Include compiled MO files in the plugin release
5. Review translations for context and accuracy

## Common Pitfalls to Avoid

1. **Concatenating strings**: Don't split sentences into multiple parts
2. **Hard-coded strings**: Ensure all user-facing text is translatable
3. **Missing context**: Provide context for ambiguous strings
4. **Format inconsistency**: Maintain consistent terminology
5. **Skipping pluralization**: Use `_n()` for countable items
6. **HTML in translations**: Minimize HTML in translatable strings