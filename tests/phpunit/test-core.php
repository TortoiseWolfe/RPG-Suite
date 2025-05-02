<?php
/**
 * Core subsystem tests.
 */

use PHPUnit\Framework\TestCase;
use RPG\Suite\Core\Core;
use RPG\Suite\Core\Event;
use RPG\Suite\Core\Event_Dispatcher;

class TestCore extends TestCase {

    /**
     * Test Core initialization.
     */
    public function test_core_init() {
        $core = new Core();
        $this->assertInstanceOf(Core::class, $core);
    }

    /**
     * Test Event class.
     */
    public function test_event() {
        $event = new Event('test.event', ['test' => 'data']);
        
        $this->assertEquals('test.event', $event->get_name());
        $this->assertEquals(['test' => 'data'], $event->get_data());
        
        $event->set_data(['updated' => 'data']);
        $this->assertEquals(['updated' => 'data'], $event->get_data());
        
        $this->assertFalse($event->is_propagation_stopped());
        $event->stop_propagation();
        $this->assertTrue($event->is_propagation_stopped());
    }

    /**
     * Test Event Dispatcher.
     */
    public function test_event_dispatcher() {
        $dispatcher = new Event_Dispatcher();
        $this->assertInstanceOf(Event_Dispatcher::class, $dispatcher);
        
        // Test adding a listener
        $called = false;
        $dispatcher->add_listener('test.event', function() use (&$called) {
            $called = true;
        });
        
        // Dispatch the event
        $dispatcher->dispatch('test.event');
        
        // The listener should have been called
        $this->assertTrue($called);
    }
}