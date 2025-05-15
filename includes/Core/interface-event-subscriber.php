<?php
/**
 * Event Subscriber Interface
 *
 * Interface for classes that want to subscribe to events.
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

/**
 * Event Subscriber Interface
 *
 * Interface for classes that want to subscribe to events.
 */
interface RPG_Suite_Event_Subscriber {

    /**
     * Get subscribed events
     *
     * Returns an array of event names mapped to handler methods.
     * Example:
     * [
     *   'event_name' => 'method_name',
     *   'other_event' => ['method_name', 10],
     *   'another_event' => [
     *     ['method_name_1', 10],
     *     ['method_name_2', 5],
     *   ],
     * ]
     *
     * @return array Event names mapped to handler methods
     */
    public static function get_subscribed_events();
}