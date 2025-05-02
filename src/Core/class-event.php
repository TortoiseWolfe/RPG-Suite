<?php
/**
 * Event class for RPG Suite.
 *
 * @package RPG_Suite
 * @subpackage RPG_Suite/Core
 */

namespace RPG\Suite\Core;

/**
 * Event class.
 *
 * Represents an event in the system.
 */
class Event {

    /**
     * The name of the event.
     *
     * @var string
     */
    private $name;

    /**
     * The event data.
     *
     * @var mixed
     */
    private $data;

    /**
     * Whether the event propagation is stopped.
     *
     * @var bool
     */
    private $propagation_stopped = false;

    /**
     * Constructor.
     *
     * @param string $name The name of the event.
     * @param mixed  $data Optional. The event data.
     */
    public function __construct($name, $data = null) {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Get the event name.
     *
     * @return string The event name.
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get the event data.
     *
     * @return mixed The event data.
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Set the event data.
     *
     * @param mixed $data The event data.
     * @return $this
     */
    public function set_data($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * Stop event propagation.
     *
     * @return $this
     */
    public function stop_propagation() {
        $this->propagation_stopped = true;
        return $this;
    }

    /**
     * Check if event propagation is stopped.
     *
     * @return bool True if propagation is stopped, false otherwise.
     */
    public function is_propagation_stopped() {
        return $this->propagation_stopped;
    }
}