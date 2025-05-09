# Character Management System Design

## Overview
The character management system is a core component of RPG-Suite that handles creation, storage, retrieval, and management of player characters using a unique d7-based system designed specifically for a steampunk world of airballoons and zeppelins.

## Data Model

### Character Post Type
Characters are stored as a custom post type with the following structure:

| Field | Description |
|-------|-------------|
| ID | WordPress post ID, unique identifier |
| post_title | Character name |
| post_content | Character description/biography |
| post_author | WordPress user ID (character owner) |
| post_status | Publication status (publish, draft, etc.) |

### Character Meta
Additional character data stored in post meta:

| Meta Key | Type | Description |
|----------|------|-------------|
| _rpg_active | boolean | Whether this character is the user's active character |
| _rpg_attributes | array | d7 system attributes (Fortitude, Precision, Intellect, Charisma) |
| _rpg_skills | array | Character skills with die codes (e.g., "Aether Pistol 4d7+1") |
| _rpg_derived_stats | array | Derived statistics (Movement, Vitality, etc.) |
| _rpg_invention_points | integer | Points for creating gadgets and inventions |
| _rpg_fate_tokens | integer | Tokens for major story/game effects |

## Steampunk d7 System Specifics

### Attributes
Core attributes for our unique d7 system:

- **Fortitude**: Physical strength and endurance (lifting, melee combat)
- **Precision**: Dexterity and hand-eye coordination (shooting, fine manipulation)
- **Intellect**: Intelligence and technical knowledge (invention, mechanisms)
- **Charisma**: Social ability and leadership (negotiation, command)

Each attribute has a die code (e.g., "3d7+2") representing:
- Number of seven-sided dice to roll (3d7)
- Modifier to add to the roll (+2)

The d7 system is uniquely suited to our steampunk world - the number 7 representing the seven major guild houses that established the foundations of scientific advancement.

### Die Code Advancement System
Die codes advance according to these rules:

1. 3 pips (+3) equals one additional die (e.g., "3d7+2" + 1 pip = "3d7+3")
2. When the pips reach +3, it converts to an additional die and resets pips (e.g., "3d7+3" = "4d7+0")
3. Die code decreases work in reverse (e.g., "3d7+0" - 1 pip = "2d7+2")
4. Minimum die code is 1d7 (cannot go below this)

This advancement system ensures smooth progression and is used for character improvement.

### Skills
Skills are specializations of attributes with their own die codes:

```
{
  "Aether Pistol": {
    "attribute": "Precision",
    "value": "4d7+1"
  },
  "Clockwork Mechanics": {
    "attribute": "Intellect",
    "value": "5d7"
  },
  "Zeppelin Navigation": {
    "attribute": "Precision",
    "value": "3d7+2"
  }
}
```

### Inventions
A unique aspect of our system is the invention mechanic, allowing characters to create gadgets:

```
{
  "Steam-Powered Grappling Hook": {
    "inventor": "Character ID",
    "complexity": 15,
    "components": ["brass gears", "miniature boiler", "tensile cable"],
    "effects": "Allows movement between airships or buildings"
  }
}
```

## Component Architecture

### Character Manager
Central class that provides an API for character operations:

```
Character_Manager
├── create_character($user_id, $data)
├── get_character($character_id)
├── get_user_characters($user_id)
├── get_active_character($user_id)
├── set_active_character($user_id, $character_id)
├── update_character($character_id, $data)
├── delete_character($character_id)
└── can_user_access_character($user_id, $character_id)
```

### Character Post Type
Handles registration and configuration of the character custom post type:

```
Character_Post_Type
├── register_post_type()
├── register_meta()
└── add_meta_boxes()
```

### Character Meta Handler
Manages saving and retrieving character metadata:

```
Character_Meta_Handler
├── save_character_meta($post_id, $data)
├── get_character_meta($post_id)
└── validate_character_data($data)
```

### Die Code Utility
Handles parsing and manipulating d7 die codes:

```
Die_Code_Utility
├── parse_die_code($code) // "3d7+2" -> ['dice' => 3, 'modifier' => 2]
├── format_die_code($dice, $modifier) // 3, 2 -> "3d7+2"
├── increase_die_code($code, $amount) // "3d7+2" + 1 pip -> "3d7+3" or "4d7" if over
├── decrease_die_code($code, $amount)
└── simulate_roll($code) // For digital dice rolling
```

## User Flows

### Character Creation
1. User submits character creation form
2. System validates input data
3. Character_Manager creates character post
4. Character metadata is saved
5. If first character, automatically made active
6. "character_created" event is dispatched

### Character Activation
1. User selects a character to activate
2. Character_Manager checks if user owns the character
3. Previous active character is deactivated
4. Selected character is marked as active
5. "character_activated" event is dispatched

### Character Management
1. User views list of their characters
2. System fetches all characters owned by user
3. User can edit, delete, or activate each character
4. Actions trigger appropriate events

## Concurrency Handling

When handling character activation, a transaction-like approach prevents race conditions:

```php
public function set_active_character($user_id, $character_id) {
    // Start transaction-like process with a mutex
    $mutex_key = 'rpg_character_activation_' . $user_id;
    $mutex = get_transient($mutex_key);
    
    if ($mutex) {
        return new WP_Error('activation_in_progress', 'Another character activation is in progress');
    }
    
    // Set mutex with short expiration
    set_transient($mutex_key, true, 30);
    
    try {
        // Get all user's characters
        $user_characters = $this->get_user_characters($user_id);
        
        // Verify character ownership
        $character_found = false;
        foreach ($user_characters as $character) {
            if ($character->ID == $character_id) {
                $character_found = true;
                break;
            }
        }
        
        if (!$character_found) {
            delete_transient($mutex_key);
            return new WP_Error('invalid_character', 'Character does not belong to this user');
        }
        
        // Get previous active character
        $previous_active = $this->get_active_character($user_id);
        $previous_id = $previous_active ? $previous_active->ID : null;
        
        // Deactivate all characters
        foreach ($user_characters as $character) {
            update_post_meta($character->ID, '_rpg_active', false);
        }
        
        // Activate the selected character
        update_post_meta($character_id, '_rpg_active', true);
        
        // Dispatch event
        $event_data = [
            'character_id' => $character_id,
            'user_id' => $user_id,
            'previous_character_id' => $previous_id
        ];
        
        global $rpg_suite;
        $rpg_suite->event_dispatcher->dispatch('character_activated', $event_data);
        
        // Release mutex
        delete_transient($mutex_key);
        
        return true;
    } catch (Exception $e) {
        // Release mutex in case of error
        delete_transient($mutex_key);
        return new WP_Error('activation_failed', $e->getMessage());
    }
}
```

## Character Limit Enforcement

### Default Limit
By default, users can have a maximum of 2 characters.

### Implementation
1. Before creating a character, check current count:
   ```php
   $user_characters = $this->get_user_characters($user_id);
   if (count($user_characters) >= $this->get_character_limit($user_id)) {
       return new WP_Error('character_limit_reached', 'Maximum character limit reached');
   }
   ```

2. Admin can modify limit per user or globally:
   ```php
   public function get_character_limit($user_id) {
       $user_limit = get_user_meta($user_id, '_rpg_character_limit', true);
       if (!empty($user_limit)) {
           return (int) $user_limit;
       }
       
       return (int) get_option('rpg_suite_character_limit', 2);
   }
   ```

## Active Character Handling

### Storage Approach
Active character status is stored at the character level with `_rpg_active` meta flag.

### Implementation
1. When activating a character, first deactivate all other user characters:
   ```php
   $user_characters = $this->get_user_characters($user_id);
   foreach ($user_characters as $character) {
       update_post_meta($character->ID, '_rpg_active', false);
   }
   
   // Set new active character
   update_post_meta($character_id, '_rpg_active', true);
   ```

2. Retrieve active character efficiently:
   ```php
   public function get_active_character($user_id) {
       // Check transient cache first
       $cache_key = 'rpg_active_character_' . $user_id;
       $cached = get_transient($cache_key);
       
       if (false !== $cached) {
           // Verify the cached post still exists
           $post = get_post($cached);
           if ($post && $post->post_type === 'rpg_character') {
               return $post;
           }
       }
       
       // If not cached or invalid, run query
       $args = [
           'post_type' => 'rpg_character',
           'author' => $user_id,
           'posts_per_page' => 1,
           'meta_query' => [
               [
                   'key' => '_rpg_active',
                   'value' => true,
               ]
           ]
       ];
       
       $query = new WP_Query($args);
       
       if ($query->have_posts()) {
           $character = $query->posts[0];
           // Cache for 12 hours
           set_transient($cache_key, $character->ID, 12 * HOUR_IN_SECONDS);
           return $character;
       }
       
       return null;
   }
   ```

## Steampunk Character Classes

Our system features unique character classes based on steampunk archetypes:

1. **Aeronaut**: Airship pilots and navigators skilled in aerial operations
2. **Mechwright**: Engineers who create and maintain complex mechanical devices
3. **Aethermancer**: Scientists who manipulate aether energy for various effects
4. **Diplomat**: Negotiators who navigate the complex politics of the sky cities

Each class provides starting skill bonuses and special abilities.

## Digital d7 Implementation

Since physical 7-sided dice are impractical, our digital implementation leverages this unique approach:

```php
/**
 * Simulate rolling d7 dice
 * 
 * @param string $die_code Die code (e.g. "3d7+2")
 * @return array Roll result with details
 */
public function simulate_roll($die_code) {
    $parsed = $this->parse_die_code($die_code);
    $dice = $parsed['dice'];
    $modifier = $parsed['modifier'];
    
    $rolls = [];
    $total = 0;
    
    for ($i = 0; $i < $dice; $i++) {
        $roll = mt_rand(1, 7);
        $rolls[] = $roll;
        $total += $roll;
    }
    
    $total += $modifier;
    
    return [
        'die_code' => $die_code,
        'individual_rolls' => $rolls,
        'modifier' => $modifier,
        'total' => $total
    ];
}
```

## Events

### Character Created
- Event Name: "character_created"
- Data: 
  - character_id: ID of created character
  - user_id: Owner user ID

### Character Activated 
- Event Name: "character_activated"
- Data:
  - character_id: ID of activated character
  - user_id: Owner user ID
  - previous_character_id: ID of previously active character (if any)

### Character Updated
- Event Name: "character_updated"
- Data:
  - character_id: ID of updated character
  - user_id: Owner user ID
  - updated_fields: Array of updated fields

### Character Deleted
- Event Name: "character_deleted"
- Data:
  - character_id: ID of deleted character
  - user_id: Owner user ID