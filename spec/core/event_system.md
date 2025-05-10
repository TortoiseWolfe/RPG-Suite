# Event System Specification

## Purpose
The event system provides a decoupled communication mechanism between different components of the RPG-Suite plugin.

## Components

### Event Class
Represents an event that can be dispatched and contains relevant data.

The Event class should:

1. Be named `RPG_Suite_Event`
2. Be defined in file `class-event.php`
3. Store the event name and associated data
4. Include a flag for stopping event propagation
5. Provide methods for:
   - Getting the event name
   - Getting all event data or specific data items
   - Setting data items
   - Stopping event propagation
   - Checking if propagation is stopped

This class should support method chaining for fluent interface.

### Event Subscriber Interface
Interface for classes that want to subscribe to events.

The Event Subscriber interface should:

1. Be named `RPG_Suite_Event_Subscriber`
2. Be defined in file `interface-event-subscriber.php`
3. Define a single static method get_subscribed_events()
4. Return an array of event names mapped to handler methods
5. Support priority specification for handlers

### Event Dispatcher
Central hub for dispatching events and managing subscribers.

The Event Dispatcher class should:

1. Be named `RPG_Suite_Event_Dispatcher`
2. Be defined in file `class-event-dispatcher.php`
3. Maintain internal arrays of registered listeners and subscribers
4. Provide methods for:
   - dispatch() - Dispatching events by name or Event object
   - add_listener() - Adding individual event listeners with priority
   - add_subscriber() - Adding subscriber objects
   - remove_listener() - Removing individual listeners
   - remove_subscriber() - Removing subscriber objects
   - get_listeners() - Getting all listeners for an event
   - has_listeners() - Checking if an event has listeners
5. Support method chaining for fluent interface
6. Execute listeners in priority order
7. Respect event propagation stopped flag

## Usage Example

The event system should be used as follows:

1. Creating and Using the Event Dispatcher:
   - Create an event dispatcher instance
   - Add individual listeners for specific events
   - Dispatch events with relevant data
   - Access event data in listener callbacks

2. Implementing and Registering Subscribers:
   - Create a class that implements RPG_Suite_Event_Subscriber
   - Define event-to-method mappings with priorities
   - Implement handler methods for each event
   - Register the subscriber with the dispatcher

3. Common Event Patterns:
   - Character lifecycle events (created, updated, deleted, activated)
   - Inventory events (items added, removed, used)
   - Game system events (dice rolled, skill check performed)
   - UI events (character sheet displayed, tab changed)

## Implementation Notes
1. Event names should follow a clear naming pattern (e.g., 'character_activated')
2. Listeners should be called in priority order (higher priority first)
3. Events can have their propagation stopped
4. Subscribers should register all their listeners in one place
5. The dispatcher should be accessible via the main plugin instance
6. WordPress hooks can be integrated with this system if needed
7. All class names follow the RPG_Suite_ prefix convention for consistency