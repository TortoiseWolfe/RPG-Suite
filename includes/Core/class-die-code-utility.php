<?php
/**
 * Die Code Utility
 *
 * Provides functions for handling the d7 dice system, including
 * parsing and manipulating die codes and simulating dice rolls.
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

/**
 * Die Code Utility Class
 *
 * Handles die code parsing, formatting, validation, manipulation and simulation.
 */
class RPG_Suite_Die_Code_Utility {

    /**
     * Parse a die code string into component parts
     *
     * @param string $code Die code string (e.g., "3d7+2")
     * @return array Array with 'dice' count and 'modifier' value
     */
    public function parse_die_code($code) {
        // Default values
        $result = array(
            'dice'     => 1,
            'modifier' => 0,
        );
        
        // Handle empty input
        if (empty($code)) {
            return $result;
        }
        
        // Validate the die code format
        if (!$this->is_valid_die_code($code)) {
            return $result;
        }
        
        // Extract dice count and modifier using regex
        preg_match('/^(\d+)d7([+-]\d+)?$/', $code, $matches);
        
        // Set dice count
        if (isset($matches[1])) {
            $result['dice'] = (int) $matches[1];
        }
        
        // Set modifier if present
        if (isset($matches[2])) {
            $result['modifier'] = (int) $matches[2];
        }
        
        return $result;
    }

    /**
     * Format dice count and modifier into standard notation
     *
     * @param int $dice     Number of dice
     * @param int $modifier Die code modifier
     * @return string Formatted die code
     */
    public function format_die_code($dice, $modifier) {
        // Ensure non-negative dice count with minimum of 1
        $dice = max(1, abs($dice));
        
        // Format the die code
        $code = $dice . 'd7';
        
        // Add modifier if not zero
        if ($modifier > 0) {
            $code .= '+' . $modifier;
        } elseif ($modifier < 0) {
            $code .= $modifier; // Negative sign is already included
        }
        
        return $code;
    }

    /**
     * Validate if a string is a proper d7 die code
     *
     * @param string $code Die code to validate
     * @return bool Whether the die code is valid
     */
    public function is_valid_die_code($code) {
        // Check pattern: XdY+/-Z where Y must be 7
        return (bool) preg_match('/^\d+d7([+-]\d+)?$/', $code);
    }

    /**
     * Increase a die code by adding pips
     *
     * @param string $code Die code to increase
     * @param int    $pips Number of pips to add
     * @return string New formatted die code
     */
    public function increase_die_code($code, $pips) {
        // Parse the die code
        $parsed = $this->parse_die_code($code);
        
        // Add pips to modifier
        $parsed['modifier'] += $pips;
        
        // Convert to additional dice when appropriate
        while ($parsed['modifier'] >= 3) {
            $parsed['dice']++;
            $parsed['modifier'] -= 3;
        }
        
        // Format and return new die code
        return $this->format_die_code($parsed['dice'], $parsed['modifier']);
    }

    /**
     * Decrease a die code by subtracting pips
     *
     * @param string $code Die code to decrease
     * @param int    $pips Number of pips to subtract
     * @return string New formatted die code
     */
    public function decrease_die_code($code, $pips) {
        // Parse the die code
        $parsed = $this->parse_die_code($code);
        
        // Subtract pips from modifier
        $parsed['modifier'] -= $pips;
        
        // Convert to fewer dice when necessary
        while ($parsed['modifier'] < 0 && $parsed['dice'] > 1) {
            $parsed['dice']--;
            $parsed['modifier'] += 3;
        }
        
        // Ensure modifier is not negative if at minimum dice
        if ($parsed['dice'] <= 1 && $parsed['modifier'] < 0) {
            $parsed['modifier'] = 0;
        }
        
        // Format and return new die code
        return $this->format_die_code($parsed['dice'], $parsed['modifier']);
    }

    /**
     * Simulate rolling the specified die code
     *
     * @param string $code Die code to roll
     * @return array Roll results including dice, total and modifier
     */
    public function simulate_roll($code) {
        // Default result structure
        $result = array(
            'dice_results' => array(),
            'modifier'     => 0,
            'total'        => 0,
            'success'      => false,
            'error'        => '',
        );
        
        // Validate the die code
        if (!$this->is_valid_die_code($code)) {
            $result['error'] = 'Invalid die code format';
            return $result;
        }
        
        // Parse the die code
        $parsed = $this->parse_die_code($code);
        
        // Roll each die
        for ($i = 0; $i < $parsed['dice']; $i++) {
            // Using mt_rand for better randomness (1-7 for d7)
            $roll = mt_rand(1, 7);
            $result['dice_results'][] = $roll;
            $result['total'] += $roll;
        }
        
        // Add modifier to total
        $result['modifier'] = $parsed['modifier'];
        $result['total'] += $parsed['modifier'];
        
        // Set success flag
        $result['success'] = true;
        
        return $result;
    }
}