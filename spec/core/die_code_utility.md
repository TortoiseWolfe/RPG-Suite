# Die Code Utility Specification

## Purpose
The Die Code Utility provides functions for handling the d7 dice system, including parsing and manipulating die codes and simulating dice rolls.

## Requirements
1. Parse die code strings into components (dice count, modifier)
2. Format die code components back into standard notation
3. Validate die code format
4. Handle increasing and decreasing die codes
5. Simulate digital dice rolls
6. Support the d7 dice system

## Class Definition

```php
/**
 * Utility class for handling d7 dice system
 */
class Die_Code_Utility {
    /**
     * Parse a die code string into components
     * 
     * @param string $code Die code (e.g. "3d7+2")
     * @return array Parsed components ['dice' => int, 'modifier' => int]
     */
    public function parse_die_code($code) {
        // Implementation logic
    }
    
    /**
     * Format die code components into standard notation
     * 
     * @param int $dice Number of dice
     * @param int $modifier Modifier value
     * @return string Formatted die code (e.g. "3d7+2")
     */
    public function format_die_code($dice, $modifier = 0) {
        // Implementation logic
    }
    
    /**
     * Validate a die code string
     * 
     * @param string $code Die code to validate
     * @return bool Valid or not
     */
    public function is_valid_die_code($code) {
        // Implementation logic
    }
    
    /**
     * Increase a die code by specified amount of pips
     * 
     * @param string $code Die code to increase
     * @param int $pips Number of pips to increase by
     * @return string New die code
     */
    public function increase_die_code($code, $pips = 1) {
        // Implementation logic
    }
    
    /**
     * Decrease a die code by specified amount of pips
     * 
     * @param string $code Die code to decrease
     * @param int $pips Number of pips to decrease by
     * @return string New die code
     */
    public function decrease_die_code($code, $pips = 1) {
        // Implementation logic
    }
    
    /**
     * Simulate rolling the specified die code
     * 
     * @param string $code Die code to roll
     * @return array Roll result with details
     */
    public function simulate_roll($code) {
        // Implementation logic
    }
}
```

## Implementation Details

### Die Code Parsing

```php
/**
 * Parse a die code string into components
 * 
 * @param string $code Die code (e.g. "3d7+2")
 * @return array Parsed components ['dice' => int, 'modifier' => int]
 */
public function parse_die_code($code) {
    // Default values
    $result = [
        'dice' => 0,
        'modifier' => 0
    ];
    
    // Handle empty or null input
    if (empty($code)) {
        return $result;
    }
    
    // Parse using regex for d7 format
    if (preg_match('/^(\d+)d7([+-]\d+)?$/i', $code, $matches)) {
        $result['dice'] = (int) $matches[1];
        
        // Check for modifier
        if (isset($matches[2])) {
            $result['modifier'] = (int) $matches[2];
        }
    }
    
    return $result;
}
```

### Die Code Formatting

```php
/**
 * Format die code components into standard notation
 * 
 * @param int $dice Number of dice
 * @param int $modifier Modifier value
 * @return string Formatted die code (e.g. "3d7+2")
 */
public function format_die_code($dice, $modifier = 0) {
    $dice = max(0, (int) $dice); // Ensure non-negative
    $modifier = (int) $modifier;
    
    $code = "{$dice}d7";
    
    if ($modifier > 0) {
        $code .= "+{$modifier}";
    } elseif ($modifier < 0) {
        $code .= "{$modifier}"; // Negative sign included automatically
    }
    
    return $code;
}
```

### Die Code Validation

```php
/**
 * Validate a die code string
 * 
 * @param string $code Die code to validate
 * @return bool Valid or not
 */
public function is_valid_die_code($code) {
    return (bool) preg_match('/^(\d+)d7([+-]\d+)?$/i', $code);
}
```

### Increasing Die Codes

```php
/**
 * Increase a die code by specified amount of pips
 * 
 * @param string $code Die code to increase
 * @param int $pips Number of pips to increase by
 * @return string New die code
 */
public function increase_die_code($code, $pips = 1) {
    if (!$this->is_valid_die_code($code)) {
        return $code; // Return unchanged if invalid
    }
    
    $components = $this->parse_die_code($code);
    $dice = $components['dice'];
    $modifier = $components['modifier'];
    
    // Add pips to modifier
    $modifier += $pips;
    
    // Convert +3 modifier to an additional die
    if ($modifier >= 3) {
        $dice += 1;
        $modifier -= 3;
    }
    
    return $this->format_die_code($dice, $modifier);
}
```

### Decreasing Die Codes

```php
/**
 * Decrease a die code by specified amount of pips
 * 
 * @param string $code Die code to decrease
 * @param int $pips Number of pips to decrease by
 * @return string New die code
 */
public function decrease_die_code($code, $pips = 1) {
    if (!$this->is_valid_die_code($code)) {
        return $code; // Return unchanged if invalid
    }
    
    $components = $this->parse_die_code($code);
    $dice = $components['dice'];
    $modifier = $components['modifier'];
    
    // Subtract pips from modifier
    $modifier -= $pips;
    
    // Convert negative modifier to fewer dice
    while ($modifier < 0 && $dice > 0) {
        $dice -= 1;
        $modifier += 3;
    }
    
    // Minimum die code is 1d7
    $dice = max(1, $dice);
    
    return $this->format_die_code($dice, $modifier);
}
```

### Dice Rolling Simulation

```php
/**
 * Simulate rolling the specified die code
 * 
 * @param string $code Die code to roll
 * @return array Roll result with details
 */
public function simulate_roll($code) {
    if (!$this->is_valid_die_code($code)) {
        return [
            'die_code' => $code,
            'individual_rolls' => [],
            'modifier' => 0,
            'total' => 0,
            'error' => 'Invalid die code'
        ];
    }
    
    $parsed = $this->parse_die_code($code);
    $dice = $parsed['dice'];
    $modifier = $parsed['modifier'];
    
    $rolls = [];
    $total = 0;
    
    // Roll each die
    for ($i = 0; $i < $dice; $i++) {
        $roll = mt_rand(1, 7);
        $rolls[] = $roll;
        $total += $roll;
    }
    
    // Add modifier
    $total += $modifier;
    
    return [
        'die_code' => $code,
        'individual_rolls' => $rolls,
        'modifier' => $modifier,
        'total' => $total
    ];
}
```

## Usage Examples

```php
// Create utility
$die_code_utility = new Die_Code_Utility();

// Validate die code
$is_valid = $die_code_utility->is_valid_die_code('3d7+2'); // true
$is_invalid = $die_code_utility->is_valid_die_code('3d6+2'); // false (not d7)

// Parse die code
$components = $die_code_utility->parse_die_code('3d7+2');
// $components = ['dice' => 3, 'modifier' => 2]

// Format die code
$formatted = $die_code_utility->format_die_code(3, 2); // "3d7+2"

// Increase die code
$increased = $die_code_utility->increase_die_code('3d7+2'); // "3d7+3"
$increased_again = $die_code_utility->increase_die_code('3d7+2', 3); // "4d7+2"

// Decrease die code
$decreased = $die_code_utility->decrease_die_code('3d7+1'); // "3d7+0" or "3d7"
$decreased_again = $die_code_utility->decrease_die_code('3d7+0', 3); // "2d7+0" or "2d7"

// Simulate roll
$roll_result = $die_code_utility->simulate_roll('3d7+2');
// $roll_result = [
//     'die_code' => '3d7+2',
//     'individual_rolls' => [4, 2, 5],
//     'modifier' => 2,
//     'total' => 13
// ]
```

## Implementation Notes

1. **d7 Specificity**: This utility is specifically designed for the unique d7 system
2. **Pip Advancement**: Three pips (+3) equals one additional die
3. **Minimum Die Code**: No character attribute should ever fall below 1d7
4. **Random Number Generation**: Uses mt_rand() for better randomness
5. **Error Handling**: Returns meaningful results for invalid inputs
6. **Format Consistency**: Ensures consistent notation for die codes
7. **Digital Only**: Implementation leverages the digital nature of the system, as physical d7 dice would be impractical