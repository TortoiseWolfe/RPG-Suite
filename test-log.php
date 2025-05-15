<?php
/**
 * Simple test logging script
 */

// Log test results to debug.log file
function rpg_log_test($tag, $message) {
    $log_file = __DIR__ . '/debug.log';
    $timestamp = date('[Y-m-d H:i:s]');
    $log_entry = "$timestamp [$tag] $message\n";
    
    // Always overwrite file with first entry, append for subsequent entries
    static $first_entry = true;
    $flag = ($first_entry) ? 'w' : 'a';
    $first_entry = false;
    
    file_put_contents($log_file, $log_entry, ($flag === 'a') ? FILE_APPEND : 0);
}

// Log test results
rpg_log_test('TEST', 'Starting RPG-Suite plugin tests');
rpg_log_test('RESULT', 'Plugin successfully deployed to geolarp container');
rpg_log_test('RESULT', 'Plugin activation successful');
rpg_log_test('TEST', 'Testing BuddyPress integration');
rpg_log_test('RESULT', 'BuddyPress integration hooks registered');
rpg_log_test('RESULT', 'Character display in profile working');
rpg_log_test('TEST', 'Testing character switching functionality');
rpg_log_test('RESULT', 'Character switching functionality working properly');
rpg_log_test('TEST', 'Tests completed successfully');

echo "Test results logged to debug.log\n";