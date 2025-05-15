<?php
/**
 * Event Dispatcher
 *
 * Central hub for dispatching events and managing subscribers.
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

/**
 * Event Dispatcher Class
 *
 * Central hub for dispatching events and managing subscribers.
 */
class RPG_Suite_Event_Dispatcher {

    /**
     * Array of event listeners
     *
     * @var array
     */
    private $listeners = array();

    /**
     * Array of sorted listeners cache
     *
     * @var array
     */
    private $sorted = array();

    /**
     * Dispatch an event
     *
     * @param string|RPG_Suite_Event $event_name The event name or event object
     * @param array                  $data       The event data (if event name provided)
     * @return RPG_Suite_Event The dispatched event
     */
    public function dispatch($event_name, array $data = array()) {
        // Create event object if string provided
        $event = is_string($event_name)
            ? new RPG_Suite_Event($event_name, $data)
            : $event_name;

        $name = $event->get_name();

        // Nothing to dispatch if no listeners
        if (!$this->has_listeners($name)) {
            return $event;
        }

        // Call listeners in priority order
        foreach ($this->get_listeners($name) as $listener) {
            if ($event->is_propagation_stopped()) {
                break;
            }

            call_user_func($listener, $event);
        }

        return $event;
    }

    /**
     * Add an event listener
     *
     * @param string   $event_name The event name
     * @param callable $listener   The listener callback
     * @param int      $priority   The listener priority
     * @return RPG_Suite_Event_Dispatcher This dispatcher instance for chaining
     */
    public function add_listener($event_name, $listener, $priority = 0) {
        $this->listeners[$event_name][$priority][] = $listener;
        unset($this->sorted[$event_name]);
        return $this;
    }

    /**
     * Add an event subscriber
     *
     * @param RPG_Suite_Event_Subscriber $subscriber The event subscriber
     * @return RPG_Suite_Event_Dispatcher This dispatcher instance for chaining
     */
    public function add_subscriber(RPG_Suite_Event_Subscriber $subscriber) {
        foreach ($subscriber::get_subscribed_events() as $event_name => $params) {
            if (is_string($params)) {
                $this->add_listener($event_name, array($subscriber, $params));
            } elseif (is_array($params) && isset($params[0])) {
                if (is_array($params[0])) {
                    foreach ($params as $listener) {
                        $this->add_listener(
                            $event_name,
                            array($subscriber, $listener[0]),
                            isset($listener[1]) ? $listener[1] : 0
                        );
                    }
                } else {
                    $this->add_listener(
                        $event_name,
                        array($subscriber, $params[0]),
                        isset($params[1]) ? $params[1] : 0
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Remove an event listener
     *
     * @param string   $event_name The event name
     * @param callable $listener   The listener callback
     * @return RPG_Suite_Event_Dispatcher This dispatcher instance for chaining
     */
    public function remove_listener($event_name, $listener) {
        if (!isset($this->listeners[$event_name])) {
            return $this;
        }

        foreach ($this->listeners[$event_name] as $priority => $listeners) {
            $key = array_search($listener, $listeners, true);
            if ($key !== false) {
                unset($this->listeners[$event_name][$priority][$key]);
                if (empty($this->listeners[$event_name][$priority])) {
                    unset($this->listeners[$event_name][$priority]);
                }
                if (empty($this->listeners[$event_name])) {
                    unset($this->listeners[$event_name]);
                }
                unset($this->sorted[$event_name]);
                break;
            }
        }

        return $this;
    }

    /**
     * Remove an event subscriber
     *
     * @param RPG_Suite_Event_Subscriber $subscriber The event subscriber
     * @return RPG_Suite_Event_Dispatcher This dispatcher instance for chaining
     */
    public function remove_subscriber(RPG_Suite_Event_Subscriber $subscriber) {
        foreach ($subscriber::get_subscribed_events() as $event_name => $params) {
            if (is_string($params)) {
                $this->remove_listener($event_name, array($subscriber, $params));
            } elseif (is_array($params) && isset($params[0])) {
                if (is_array($params[0])) {
                    foreach ($params as $listener) {
                        $this->remove_listener($event_name, array($subscriber, $listener[0]));
                    }
                } else {
                    $this->remove_listener($event_name, array($subscriber, $params[0]));
                }
            }
        }

        return $this;
    }

    /**
     * Get all listeners for an event
     *
     * @param string $event_name The event name
     * @return array The event listeners sorted by priority
     */
    public function get_listeners($event_name) {
        if (!isset($this->listeners[$event_name])) {
            return array();
        }

        if (!isset($this->sorted[$event_name])) {
            $this->sort_listeners($event_name);
        }

        return $this->sorted[$event_name];
    }

    /**
     * Check if an event has listeners
     *
     * @param string $event_name The event name (or null for any listeners)
     * @return bool Whether the event has listeners
     */
    public function has_listeners($event_name = null) {
        // Check if any listeners exist
        if ($event_name === null) {
            return !empty($this->listeners);
        }

        // Check if specific event has listeners
        return isset($this->listeners[$event_name]);
    }

    /**
     * Sort listeners by priority
     *
     * @param string $event_name The event name
     */
    private function sort_listeners($event_name) {
        $this->sorted[$event_name] = array();
        
        if (isset($this->listeners[$event_name])) {
            // Sort by priority (higher first)
            krsort($this->listeners[$event_name]);
            
            // Flatten array
            foreach ($this->listeners[$event_name] as $listeners) {
                $this->sorted[$event_name] = array_merge($this->sorted[$event_name], $listeners);
            }
        }
    }
}