<?php
/**
 * Tests for the Autoloader class.
 */

use PHPUnit\Framework\TestCase;
use RPG\Suite\Includes\Autoloader;

class TestAutoloader extends TestCase {

    /**
     * Test initialization of the autoloader.
     */
    public function test_init() {
        // Call the init method
        $autoloader = Autoloader::init();
        
        // Test that it returned an instance of Autoloader
        $this->assertInstanceOf(Autoloader::class, $autoloader);
        
        // Call init again and verify it returns the same instance (singleton)
        $second_instance = Autoloader::init();
        $this->assertSame($autoloader, $second_instance);
    }
    
    /**
     * Test the constructor.
     */
    public function test_constructor() {
        // Create a new instance
        $autoloader = new Autoloader();
        
        // Test that it's an instance of Autoloader
        $this->assertInstanceOf(Autoloader::class, $autoloader);
    }
    
    /**
     * Test the convert_class_to_file method.
     */
    public function test_convert_class_to_file() {
        // We need to test a protected method, so we'll use reflection
        $autoloader = new Autoloader();
        $reflection = new ReflectionClass($autoloader);
        $method = $reflection->getMethod('convert_class_to_file');
        $method->setAccessible(true);
        
        // Test converting a simple class name
        $result = $method->invoke($autoloader, 'Test');
        $this->assertEquals('class-test.php', $result);
        
        // Test converting a namespaced class
        $result = $method->invoke($autoloader, 'Test\\Class\\Name');
        $this->assertEquals('Test' . DIRECTORY_SEPARATOR . 'Class' . DIRECTORY_SEPARATOR . 'class-name.php', $result);
        
        // Test a class that already has the 'class-' prefix
        $result = $method->invoke($autoloader, 'Test\\Class\\class-name');
        $this->assertEquals('Test' . DIRECTORY_SEPARATOR . 'Class' . DIRECTORY_SEPARATOR . 'class-name.php', $result);
        
        // Test a class with underscores
        $result = $method->invoke($autoloader, 'Test\\Class\\My_Class_Name');
        $this->assertEquals('Test' . DIRECTORY_SEPARATOR . 'Class' . DIRECTORY_SEPARATOR . 'class-my-class-name.php', $result);
    }
    
    /**
     * Test the autoload method.
     */
    public function test_autoload() {
        // Create a mock for the Autoloader class
        $autoloader = $this->getMockBuilder(Autoloader::class)
            ->setMethods(['convert_class_to_file'])
            ->getMock();
        
        // Set expectations for convert_class_to_file
        $autoloader->expects($this->once())
            ->method('convert_class_to_file')
            ->with($this->equalTo('Includes\\Test'))
            ->willReturn('class-test.php');
        
        // Call autoload method
        $reflection = new ReflectionClass($autoloader);
        $method = $reflection->getMethod('autoload');
        $method->setAccessible(true);
        
        // Test autoloading a class in our namespace
        $method->invoke($autoloader, 'RPG\\Suite\\Includes\\Test');
        
        // No assertion needed as we're testing the method calls
    }
}