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

## Class Requirements

The Die Code Utility class should:
1. Be named `RPG_Suite_Die_Code_Utility`
2. Be defined in file `class-die-code-utility.php`
3. Provide the following methods:

**parse_die_code($code)**: Parse a die code string (e.g., "3d7+2") into component parts
- Return array with 'dice' count and 'modifier' value
- Handle empty or invalid input gracefully

**format_die_code($dice, $modifier)**: Convert components back to standard notation
- Format as "[dice]d7[+/-modifier]"
- Omit modifier if zero
- Ensure non-negative dice count

**is_valid_die_code($code)**: Validate if a string is a proper d7 die code
- Verify format matches pattern "XdY+/-Z" where Y must be 7
- Return boolean result

**increase_die_code($code, $pips)**: Increase a die code
- Add pips to modifier
- Convert to additional dice when appropriate (+3 pips = +1 die)
- Return new formatted die code

**decrease_die_code($code, $pips)**: Decrease a die code
- Subtract pips from modifier
- Convert to fewer dice when necessary
- Maintain minimum die code of 1d7
- Return new formatted die code

**simulate_roll($code)**: Digitally roll the specified die code
- Return detailed results including individual die results
- Calculate total with modifier
- Include error handling for invalid codes

## Implementation Details

### Die Code Parsing
The parse_die_code function should use regular expressions to identify the number of dice and modifier from a string like "3d7+2". It should handle empty input and various edge cases gracefully.

### Die Code Formatting
The format_die_code function should combine dice count and modifier into a properly formatted string, ensuring the modifier is only shown when non-zero.

### Die Code Validation
The is_valid_die_code function should verify the input string matches the expected pattern for a d7 die code using regular expressions.

### Increasing Die Codes
The increase_die_code function should follow the d7 system rules where three pips equals one additional die. It should handle invalid inputs safely.

### Decreasing Die Codes
The decrease_die_code function should implement the inverse operation, ensuring the character never drops below the minimum of 1d7.

### Dice Rolling Simulation
The simulate_roll function should generate random values for each die, calculate totals with modifiers, and return comprehensive results about the roll.

## Usage Examples

The Die Code Utility should be used for:

1. Validating die codes:
   - "3d7+2" is valid
   - "3d6+2" is invalid (not using d7)

2. Parsing and formatting die codes:
   - Parse "3d7+2" into components: 3 dice with +2 modifier
   - Format components (3, 2) back into "3d7+2"

3. Manipulating die codes:
   - Increase "3d7+2" by 1 pip to get "3d7+3"
   - Increase "3d7+2" by 3 pips to get "4d7+2" (3 pips = 1 die)
   - Decrease "3d7+1" by 1 pip to get "3d7" (omitting +0)
   - Decrease "3d7+0" by 3 pips to get "2d7" (respecting minimums)

4. Simulating dice rolls:
   - Roll "3d7+2" to get individual dice results, modifier, and total
   - Handle error cases for invalid die codes

## Implementation Notes

1. **d7 Specificity**: This utility is specifically designed for the unique d7 system
2. **Pip Advancement**: Three pips (+3) equals one additional die
3. **Minimum Die Code**: No character attribute should ever fall below 1d7
4. **Random Number Generation**: Uses mt_rand() for better randomness
5. **Error Handling**: Returns meaningful results for invalid inputs
6. **Format Consistency**: Ensures consistent notation for die codes
7. **Digital Only**: Implementation leverages the digital nature of the system, as physical d7 dice would be impractical
8. **Class Naming**: Follows the RPG_Suite_ prefix convention for consistency