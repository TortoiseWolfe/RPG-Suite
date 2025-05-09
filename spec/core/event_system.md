# Event System Specification

## Purpose
The event system provides a decoupled communication mechanism between different components of the RPG-Suite plugin.

## Components

### Event Class
Represents an event that can be dispatched and contains relevant data.

```php
/**
 * Event class for the event system
 */
class Event {
    /**
     * @var string Event name
     */
    private $name;
    
    /**
     * @var array Event data
     */
    private $data;
    
    /**
     * @var bool Whether propagation is stopped
     */
    private $propagation_stopped = false;
    
    /**
     * Constructor
     * 
     * @param string $name Event name
     * @param array $data Event data
     */
    public function __construct($name, array $data = []) {
        $this->name = $name;
        $this->data = $data;
    }
    
    /**
     * Get event name
     * 
     * @return string
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Get event data
     * 
     * @return array
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Get specific data item
     * 
     * @param string $key Data key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
    
    /**
     * Set data item
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return $this
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Stop event propagation
     * 
     * @return $this
     */
    public function stop_propagation() {
        $this->propagation_stopped = true;
        return $this;
    }
    
    /**
     * Check if propagation is stopped
     * 
     * @return bool
     */
    public function is_propagation_stopped() {
        return $this->propagation_stopped;
    }
}
```

### Event Subscriber Interface
Interface for classes that want to subscribe to events.

```php
/**
 * Interface for event subscribers
 */
interface Event_Subscriber {
    /**
     * Get subscribed events
     * 
     * @return array
     */
    public static function get_subscribed_events();
}
```

### Event Dispatcher
Central hub for dispatching events and managing subscribers.

```php
/**
 * Event dispatcher for RPG-Suite
 */
class Event_Dispatcher {
    /**
     * @var array Registered listeners
     */
    private $listeners = [];
    
    /**
     * @var array Registered subscribers
     */
    private $subscribers = [];
    
    /**
     * Dispatch an event
     * 
     * @param string|Event $event Event object or name
     * @param array $data Event data (if $event is a string)
     * @return Event
     */
    public function dispatch($event, array $data = []) {
        // Implementation logic
    }
    
    /**
     * Add an event listener
     * 
     * @param string $event_name Event name
     * @param callable $listener Listener callback
     * @param int $priority Listener priority
     * @return $this
     */
    public function add_listener($event_name, $listener, $priority = 0) {
        // Implementation logic
    }
    
    /**
     * Add an event subscriber
     * 
     * @param Event_Subscriber $subscriber
     * @return $this
     */
    public function add_subscriber(Event_Subscriber $subscriber) {
        // Implementation logic
    }
    
    /**
     * Remove an event listener
     * 
     * @param string $event_name Event name
     * @param callable $listener Listener callback
     * @return $this
     */
    public function remove_listener($event_name, $listener) {
        // Implementation logic
    }
    
    /**
     * Remove an event subscriber
     * 
     * @param Event_Subscriber $subscriber
     * @return $this
     */
    public function remove_subscriber(Event_Subscriber $subscriber) {
        // Implementation logic
    }
    
    /**
     * Get registered listeners
     * 
     * @param string|null $event_name Event name (null for all)
     * @return array
     */
    public function get_listeners($event_name = null) {
        // Implementation logic
    }
    
    /**
     * Check if an event has listeners
     * 
     * @param string $event_name Event name
     * @return bool
     */
    public function has_listeners($event_name) {
        // Implementation logic
    }
}
```

## Usage Example

```php
// Create event dispatcher
$dispatcher = new Event_Dispatcher();

// Add a simple listener
$dispatcher->add_listener('character_activated', function(Event $event) {
    $character_id = $event->get('character_id');
    // Do something with activated character
});

// Dispatch an event
$dispatcher->dispatch('character_activated', [
    'character_id' => 123,
    'user_id' => get_current_user_id()
]);

// Create a subscriber
class Character_Subscriber implements Event_Subscriber {
    public static function get_subscribed_events() {
        return [
            'character_created' => 'on_character_created',
            'character_activated' => ['on_character_activated', 10], // With priority
            'character_deleted' => 'on_character_deleted',
        ];
    }
    
    public function on_character_created(Event $event) {
        // Implementation
    }
    
    public function on_character_activated(Event $event) {
        // Implementation
    }
    
    public function on_character_deleted(Event $event) {
        // Implementation
    }
}

// Register subscriber
$dispatcher->add_subscriber(new Character_Subscriber());
```

## Implementation Notes
1. Event names should follow a clear naming pattern (e.g., 'character_activated')
2. Listeners should be called in priority order (higher priority first)
3. Events can have their propagation stopped
4. Subscribers should register all their listeners in one place
5. The dispatcher should be accessible via the main plugin instance
6. WordPress hooks can be integrated with this system if needed