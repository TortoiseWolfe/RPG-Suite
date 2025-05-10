# Deactivator Class Specification

## Purpose
The Deactivator class handles plugin deactivation tasks, including cleanup of temporary data, cache flushing, and saving persistent data.

## Requirements
1. Flush plugin caches
2. Clean up temporary data
3. Handle multisite deactivation
4. Save persistent user data
5. Clear scheduled events and hooks
6. Perform database cleanup for temporary tables

## Class Requirements

The Deactivator class should:

1. Be named `RPG_Suite_Deactivator`
2. Be defined in file `class-deactivator.php`
3. Include a main deactivation method that handles single site or multi-site
4. Clean up temporary resources without removing user data
5. Support silent deactivation for updates
6. Clear scheduled tasks

## Method Descriptions

### Main Deactivation Method

The deactivate() method should:
- Accept optional boolean for silent deactivation
- Check if deactivation is network-wide
- For single site: call the single site deactivation method
- For multi-site: iterate through sites and call single site deactivation for each
- Trigger deactivation actions

### Single Site Deactivation

The deactivate_single_site() method should:
- Flush rewrite rules when not silent
- Clear plugin caches
- Deregister admin hooks that might cause errors
- Clear scheduled cron events
- Log deactivation with timestamp and version

### Cache Cleanup

The clear_caches() method should:
- Clear transients related to RPG-Suite
- Flush object cache groups created by the plugin
- Clear any custom cache directories
- Remove temporary files if created

### Scheduled Tasks Cleanup

The clear_scheduled_tasks() method should:
- Remove cron events created by the plugin
- Clear any pending background tasks
- Unschedule automated reports or user notifications
- Stop any running background processes

## Integration with Plugin Main Class

The Deactivator should be:
- Instantiated during plugin deactivation
- Called from a static deactivate hook callback in the main plugin file
- Registered with register_deactivation_hook()

## Multisite Support

For multisite support, the deactivator should:
- Check if is_multisite() and if deactivation is network wide
- Handle per-site deactivation settings
- Support network-wide deactivation

## Important Considerations

1. **Preserve User Data**: Do NOT remove important user data during deactivation
2. **Cache Cleanup Only**: Remove caches but not core data
3. **Event Cleanup**: Unschedule events to prevent errors
4. **Temporary Tables**: Only drop temporary tables, not user data tables

## Implementation Notes

1. **Rewrite Rules**: Always flush rewrite rules on deactivation 
2. **Hook Removal**: Remove any unnecessary hooks to prevent errors
3. **Multisite Support**: Handle network wide deactivation carefully
4. **Performance**: Minimize impact during deactivation
5. **Error Handling**: Log deactivation issues
6. **Clean Exit**: Ensure no cron or background tasks remain
7. **Reversibility**: Make deactivation completely reversible
8. **Class Naming**: Follows the RPG_Suite_ prefix convention for consistency