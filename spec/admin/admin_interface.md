# Admin Interface Specification

## Purpose
This specification defines the admin interface for managing RPG-Suite characters, inventions, and plugin settings.

## Requirements
1. Character creation and editing interface
2. Invention management interface 
3. Plugin settings page
4. Support for the d7 dice system in the UI
5. Easy character-to-user assignment

## Admin Pages Structure

```
RPG-Suite (top-level menu)
├── Characters (submenu)
│   ├── All Characters
│   └── Add New
├── Inventions (submenu)
│   ├── All Inventions
│   └── Add New
├── Character Classes (submenu)
│   ├── All Classes
│   └── Add New
└── Settings (submenu)
```

## Component Structure

### Admin Main Class

The Admin Main Class should:
1. Be named `RPG_Suite_Admin`
2. Be defined in file `class-admin.php`
3. Have private properties for character_manager and die_code_utility (RPG_Suite_Character_Manager and RPG_Suite_Die_Code_Utility)
4. Accept these dependencies in its constructor
5. Initialize admin hooks in a separate init() method
6. Register admin menu pages and handle access control
7. Enqueue admin assets (CSS/JS) conditionally based on current admin page
8. Register meta boxes for character and invention post types
9. Handle saving character and invention meta data
10. Customize admin columns for RPG-Suite post types

### Character Editor Class

The Character Editor Class should:
1. Be named `RPG_Suite_Character_Editor`
2. Be defined in file `class-character-editor.php`
3. Have a private property for die_code_utility (RPG_Suite_Die_Code_Utility)
4. Accept this dependency in its constructor
5. Provide methods for rendering meta boxes:
   - Attributes meta box for editing character attributes
   - Skills meta box for managing character skills
   - Owner meta box for assigning characters to users and managing active status

### Settings Class

The Settings Class should:
1. Be named `RPG_Suite_Settings`
2. Be defined in file `class-settings.php`
3. Provide methods for registering plugin settings
4. Render the settings page with proper form controls
5. Handle settings validation and sanitization
6. Include options for:
   - Default character limit per user
   - Dice rolling animation toggle
   - Data removal on uninstallation

## Character Edit Screen

The character attributes meta box should:
- Retrieve saved attributes or use defaults
- Include nonce field for security
- Display a table with attribute names, die code inputs, and roll buttons
- Make each attribute editable with proper labels
- Include client-side validation for die code format
- Add a roll button for each attribute to test values
- Display a description of the die code format

### Skills Meta Box

The skills meta box should:
- Retrieve saved skills or use an empty array
- Get attributes for the attribute dropdown
- Include nonce field for security
- Display a table with skill name, attribute selection, and die code
- Show existing skills with editable fields
- Include a template row for adding new skills
- Provide add/remove buttons for skills
- Support dynamic addition of multiple skills
- Include JavaScript to handle adding/removing skills
- Validate die codes using the same format as attributes

### Character Owner Meta Box

The character owner meta box should:
- Show the current owner of the character
- Provide a dropdown of users who can own characters
- Display character limit information for the selected user
- Include an option to set the character as active
- Warn that setting as active will replace any previously active character
- Include nonce field for security

## Invention Edit Screen

The invention meta box should:
- Retrieve saved invention data (complexity, components, effects)
- Include a dropdown to select the inventor character
- Display a complexity field (number input with min/max)
- Show a repeatable field for components with add/remove buttons
- Include a textarea for effects description
- Provide proper descriptions for each field
- Include nonce field for security

## Settings Page

The settings page should:
- Check user capabilities (manage_options)
- Handle settings form submission with nonce verification
- Display current settings with appropriate form fields
- Include options for:
  - Default character limit (number input)
  - Dice animation toggle (checkbox)
  - Data removal on uninstall (checkbox with warning)
- Show a submit button for saving changes
- Display success/error notices after form submission

## JavaScript for Dice Rolling

The admin JavaScript should include:
- Die code validation using regular expressions
- Input validation for die code fields
- Dice rolling functionality when roll buttons are clicked
- Parsing of die codes (XdY+Z format)
- Random number generation for each die
- Display of roll results showing individual dice and totals
- Dynamic skill row addition and removal
- Dynamic component row addition and removal
- Proper event delegation for dynamically added elements

## CSS Styling

The admin CSS should:
- Style die code inputs and roll buttons
- Format tables for attributes and skills
- Style roll results for readability
- Add visual cues for valid/invalid inputs
- Maintain consistency with WordPress admin styles
- Provide responsive design for different screen sizes

## Implementation Notes

1. **Accessibility**: All admin screens follow WordPress accessibility guidelines
2. **Security**: All inputs are properly validated and sanitized
3. **Nonce Verification**: All form submissions include nonce verification
4. **Capability Checks**: User capabilities are checked before displaying admin pages
5. **Consistent Styling**: UI elements match WordPress admin styles
6. **Internationalization**: All strings are properly localized
7. **Admin Notices**: Success/error messages displayed using WordPress admin notices
8. **Script Loading**: Admin scripts only loaded on relevant plugin pages
9. **Form Submission**: Form data is properly escaped on display and sanitized on save
10. **Error Handling**: Clear error messages provided for validation failures
11. **Class Naming**: All class names follow the RPG_Suite_ prefix convention for consistency