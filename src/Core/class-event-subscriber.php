<?php
/**
 * Event Subscriber interface for RPG Suite.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/Core
 */

namespace RPG\Suite\Core;

/**
 * Event Subscriber interface.
 *
 * Classes implementing this interface can subscribe to multiple events.
 */
interface Event_Subscriber {

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 10)
     *  * An array with the method name and priority
     *  * An array of arrays with the method names and priorities
     *
     * For instance:
     *
     *  * ['event_name' => 'method_name']
     *  * ['event_name' => ['method_name', $priority]]
     *  * ['event_name' => [['method_name', $priority], ['method_name2', $priority2]]]
     *
     * @return array The events and methods to call.
     */
    public function get_subscribed_events();
}