<?php
/**
 * Event Dispatcher for RPG Suite.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/Core
 */

namespace RPG\Suite\Core;

/**
 * Event Dispatcher class.
 *
 * Provides a Symfony-style event dispatcher that wraps WordPress hooks.
 */
class Event_Dispatcher {

    /**
     * Registry of event subscribers.
     *
     * @var array
     */
    private $subscribers = [];

    /**
     * Dispatch an event.
     *
     * @param string $event_name The name of the event.
     * @param mixed  $event_data Optional. The data to pass to the event handlers.
     * @return mixed The filtered event data.
     */
    public function dispatch($event_name, $event_data = null) {
        // Create event object
        $event = new Event($event_name, $event_data);
        
        // Allow subscribers to register for this event
        do_action('rpg_suite_before_' . $event_name, $event);
        
        // Dispatch the event using WordPress hooks
        $filtered_event = apply_filters('rpg_suite_' . $event_name, $event);
        
        // Execute the actual event
        do_action('rpg_suite_' . $event_name, $filtered_event);
        
        // Allow post-event processing
        do_action('rpg_suite_after_' . $event_name, $filtered_event);
        
        return $filtered_event->get_data();
    }

    /**
     * Add an event listener.
     *
     * @param string   $event_name The name of the event.
     * @param callable $callback   The callback function.
     * @param int      $priority   Optional. The priority of the callback. Default 10.
     * @return void
     */
    public function add_listener($event_name, $callback, $priority = 10) {
        add_action('rpg_suite_' . $event_name, function($event) use ($callback) {
            call_user_func($callback, $event);
        }, $priority, 1);
    }

    /**
     * Add a filter to an event.
     *
     * @param string   $event_name The name of the event.
     * @param callable $callback   The callback function.
     * @param int      $priority   Optional. The priority of the callback. Default 10.
     * @return void
     */
    public function add_filter($event_name, $callback, $priority = 10) {
        add_filter('rpg_suite_' . $event_name, function($event) use ($callback) {
            $result = call_user_func($callback, $event->get_data(), $event);
            $event->set_data($result);
            return $event;
        }, $priority, 1);
    }

    /**
     * Register an event subscriber.
     *
     * @param Event_Subscriber $subscriber The subscriber to register.
     * @return void
     */
    public function add_subscriber(Event_Subscriber $subscriber) {
        // Store the subscriber
        $this->subscribers[get_class($subscriber)] = $subscriber;
        
        // Get the events the subscriber wants to listen to
        $events = $subscriber->get_subscribed_events();
        
        // Register the subscriber's callbacks for each event
        foreach ($events as $event_name => $params) {
            if (is_string($params)) {
                // Simple case: event => method
                $this->add_listener($event_name, [$subscriber, $params]);
            } elseif (is_array($params) && isset($params[0])) {
                if (is_array($params[0])) {
                    // Complex case: event => [[method, priority], [method2, priority2], ...]
                    foreach ($params as $listener) {
                        $method = $listener[0];
                        $priority = $listener[1] ?? 10;
                        $this->add_listener($event_name, [$subscriber, $method], $priority);
                    }
                } else {
                    // Normal case: event => [method, priority]
                    $method = $params[0];
                    $priority = $params[1] ?? 10;
                    $this->add_listener($event_name, [$subscriber, $method], $priority);
                }
            }
        }
    }

    /**
     * Get a registered subscriber.
     *
     * @param string $class The class name of the subscriber.
     * @return Event_Subscriber|null The subscriber or null if not found.
     */
    public function get_subscriber($class) {
        return $this->subscribers[$class] ?? null;
    }
}