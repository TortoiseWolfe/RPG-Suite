<?php
/**
 * Tests for the Character_Manager class.
 */

use PHPUnit\Framework\TestCase;
use RPG\Suite\Includes\Character_Manager;

class TestCharacterManager extends TestCase {

    /**
     * Test constructor and initialization.
     */
    public function test_constructor() {
        // Create a mock with specific methods we want to verify
        $manager = $this->getMockBuilder(Character_Manager::class)
            ->setMethods(['register_character_metadata', 'set_character_user_relationship', 'cleanup_character_relationship', 'validate_character_limit', 'handle_character_switching', 'add_character_meta_boxes', 'save_character_meta_boxes', 'register_rest_fields'])
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set expectations for the methods
        $manager->expects($this->once())
            ->method('register_character_metadata');
            
        $manager->expects($this->once())
            ->method('set_character_user_relationship');
            
        $manager->expects($this->once())
            ->method('cleanup_character_relationship');
            
        $manager->expects($this->once())
            ->method('validate_character_limit');
            
        $manager->expects($this->once())
            ->method('handle_character_switching');
            
        $manager->expects($this->once())
            ->method('add_character_meta_boxes');
            
        $manager->expects($this->once())
            ->method('save_character_meta_boxes');
            
        $manager->expects($this->once())
            ->method('register_rest_fields');
        
        // Call the constructor manually
        $reflection = new ReflectionClass($manager);
        $constructor = $reflection->getConstructor();
        $constructor->invoke($manager);
    }
    
    /**
     * Test setting active character.
     */
    public function test_set_active_character() {
        // Create the mock
        $manager = $this->getMockBuilder(Character_Manager::class)
            ->setMethods(['get_post_meta', 'update_post_meta', 'get_posts'])
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set expectations for get_post_meta
        $manager->expects($this->once())
            ->method('get_post_meta')
            ->with($this->equalTo(123), $this->equalTo('character_owner'), $this->equalTo(true))
            ->willReturn(456);
        
        // Set expectations for get_posts
        $other_character = new stdClass();
        $other_character->ID = 789;
        
        $manager->expects($this->once())
            ->method('get_posts')
            ->willReturn([$other_character]);
        
        // Set expectations for update_post_meta
        $manager->expects($this->exactly(2))
            ->method('update_post_meta')
            ->withConsecutive(
                [$this->equalTo(789), $this->equalTo('character_is_active'), $this->equalTo(false)],
                [$this->equalTo(123), $this->equalTo('character_is_active'), $this->equalTo(true)]
            );
        
        // Call the method
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('set_active_character');
        $method->setAccessible(true);
        $method->invoke($manager, 123);
    }
    
    /**
     * Test get_active_character with no active character.
     */
    public function test_get_active_character_none() {
        // Create the mock
        $manager = $this->getMockBuilder(Character_Manager::class)
            ->setMethods(['get_current_user_id', 'get_posts'])
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set expectations for get_current_user_id
        $manager->expects($this->once())
            ->method('get_current_user_id')
            ->willReturn(123);
        
        // Set expectations for get_posts (no active character)
        $manager->expects($this->once())
            ->method('get_posts')
            ->willReturn([]);
        
        // Call the method
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('get_active_character');
        $method->setAccessible(true);
        $result = $method->invoke($manager);
        
        // Assert the result is null
        $this->assertNull($result);
    }
    
    /**
     * Test get_active_character with an active character.
     */
    public function test_get_active_character_with_active() {
        // Create the mock
        $manager = $this->getMockBuilder(Character_Manager::class)
            ->setMethods(['get_current_user_id', 'get_posts'])
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set expectations for get_current_user_id
        $manager->expects($this->once())
            ->method('get_current_user_id')
            ->willReturn(123);
        
        // Set expectations for get_posts (with active character)
        $active_character = new stdClass();
        $active_character->ID = 456;
        
        $manager->expects($this->once())
            ->method('get_posts')
            ->willReturn([$active_character]);
        
        // Call the method
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('get_active_character');
        $method->setAccessible(true);
        $result = $method->invoke($manager);
        
        // Assert the result is the active character
        $this->assertEquals($active_character, $result);
    }
    
    /**
     * Test can_create_character for admin/GM.
     */
    public function test_can_create_character_admin() {
        // Create the mock
        $manager = $this->getMockBuilder(Character_Manager::class)
            ->setMethods(['get_current_user_id', 'user_can'])
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set expectations for get_current_user_id
        $manager->expects($this->once())
            ->method('get_current_user_id')
            ->willReturn(123);
        
        // Set expectations for user_can (admin/GM can always create)
        $manager->expects($this->once())
            ->method('user_can')
            ->with($this->equalTo(123), $this->equalTo('gm_rpg'))
            ->willReturn(true);
        
        // Call the method
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('can_create_character');
        $method->setAccessible(true);
        $result = $method->invoke($manager);
        
        // Assert admin can create
        $this->assertTrue($result);
    }
    
    /**
     * Test can_create_character for regular user who hasn't reached limit.
     */
    public function test_can_create_character_under_limit() {
        // Create the mock
        $manager = $this->getMockBuilder(Character_Manager::class)
            ->setMethods(['get_current_user_id', 'user_can', 'get_option', 'get_user_characters'])
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set expectations for get_current_user_id
        $manager->expects($this->once())
            ->method('get_current_user_id')
            ->willReturn(123);
        
        // Set expectations for user_can (not admin/GM)
        $manager->expects($this->once())
            ->method('user_can')
            ->with($this->equalTo(123), $this->equalTo('gm_rpg'))
            ->willReturn(false);
        
        // Set expectations for get_option
        $manager->expects($this->once())
            ->method('get_option')
            ->with($this->equalTo('rpg_suite_character_limit'), $this->equalTo(2))
            ->willReturn(2);
        
        // Set expectations for get_user_characters (only one character so far)
        $manager->expects($this->once())
            ->method('get_user_characters')
            ->with($this->equalTo(123))
            ->willReturn([new stdClass()]);
        
        // Call the method
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('can_create_character');
        $method->setAccessible(true);
        $result = $method->invoke($manager);
        
        // Assert user can still create characters
        $this->assertTrue($result);
    }
    
    /**
     * Test can_create_character for regular user who has reached limit.
     */
    public function test_can_create_character_at_limit() {
        // Create the mock
        $manager = $this->getMockBuilder(Character_Manager::class)
            ->setMethods(['get_current_user_id', 'user_can', 'get_option', 'get_user_characters'])
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set expectations for get_current_user_id
        $manager->expects($this->once())
            ->method('get_current_user_id')
            ->willReturn(123);
        
        // Set expectations for user_can (not admin/GM)
        $manager->expects($this->once())
            ->method('user_can')
            ->with($this->equalTo(123), $this->equalTo('gm_rpg'))
            ->willReturn(false);
        
        // Set expectations for get_option
        $manager->expects($this->once())
            ->method('get_option')
            ->with($this->equalTo('rpg_suite_character_limit'), $this->equalTo(2))
            ->willReturn(2);
        
        // Set expectations for get_user_characters (two characters, at limit)
        $manager->expects($this->once())
            ->method('get_user_characters')
            ->with($this->equalTo(123))
            ->willReturn([new stdClass(), new stdClass()]);
        
        // Call the method
        $reflection = new ReflectionClass($manager);
        $method = $reflection->getMethod('can_create_character');
        $method->setAccessible(true);
        $result = $method->invoke($manager);
        
        // Assert user cannot create more characters
        $this->assertFalse($result);
    }
}