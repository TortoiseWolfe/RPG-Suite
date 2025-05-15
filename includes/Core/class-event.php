<?php
/**
 * Event Class
 *
 * Represents an event that can be dispatched and contains relevant data.
 *
 * @package    RPG_Suite
 * @subpackage Core
 * @since      0.1.0
 */

/**
 * Event Class
 *
 * Represents an event that can be dispatched and contains relevant data.
 */
class RPG_Suite_Event {

    /**
     * The event name
     *
     * @var string
     */
    private $name;

    /**
     * The event data
     *
     * @var array
     */
    private $data;

    /**
     * Flag for stopping event propagation
     *
     * @var bool
     */
    private $propagation_stopped = false;

    /**
     * Constructor
     *
     * @param string $name The event name
     * @param array  $data The event data
     */
    public function __construct($name, array $data = array()) {
        $this->name = $name;
        $this->data = $data;
    }

    /**
     * Get the event name
     *
     * @return string The event name
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get all event data
     *
     * @return array The event data
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Get a specific data item
     *
     * @param string $key     The data key
     * @param mixed  $default Default value if key doesn't exist
     * @return mixed The data value or default
     */
    public function get($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * Set a data item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     * @return RPG_Suite_Event This event instance for chaining
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Stop event propagation
     *
     * @return RPG_Suite_Event This event instance for chaining
     */
    public function stop_propagation() {
        $this->propagation_stopped = true;
        return $this;
    }

    /**
     * Check if propagation is stopped
     *
     * @return bool Whether propagation is stopped
     */
    public function is_propagation_stopped() {
        return $this->propagation_stopped;
    }
}