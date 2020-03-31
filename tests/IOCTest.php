<?php
/**
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.txt', which is part of this source code package.
 *
 * Copyright (c) 2012-2020 John Quinn <john@retail-rack.com>
 * 
 * @author John Quinn
 */

declare( strict_types=1 );

use buffalokiwi\buffalotools\ioc\IIOC;
use buffalokiwi\buffalotools\ioc\IOC;
use PHPUnit\Framework\TestCase;


/**
 * Tests the IOC class 
 */
class IOCTest extends TestCase
{
  /**
   * Test IOC instance 
   * @var IIOC
   */
  private IIOC $instance;
  
  
  /**
   * Set up the test IOC instance 
   * @return void
   */
  public function setUp() : void
  {
    $this->instance = new IOC();
  }
  
  
  /**
   * Test that adding an interface
   * 
   * 1. Requires a non-empty, non-null name 
   * 2. Requires a non-null callable/factory 
   * 3. Adding the same interface twice will throw an exception
   */
  public function testAddInterface()
  {
    $this->expectException( TypeError::class );
    $this->instance->addInterface( null, function(){});
    
    $this->expectException( TypeError::class );
    $this->instance->addInterface( 'someinterface', null );
    
    $this->expectException( InvalidArgumentException::class );
    $this->instance->addInterface( '', function(){});
    
    $this->instance->addInterface( 'interface', function(){});
    $this->expectException( InvalidArgumentException::class );
    $this->instance->addInterface( 'interface', function(){});
  }
  
  
  /**
   * Test the getInstance() method.
   * 
   * 1. Assert that calling getInstance() returns the same instance supplied to addInterface()
   * 2. Assert calling getInstance() again returns the same instance 
   * 3. Assert newInstance() returns the correct type 
   */
  public function testGetInstance()
  {
    $this->instance->addInterface( \stdClass::class, function() {
      return new \stdClass();
    });
    
    $newInstance = $this->instance->getInstance( \stdClass::class );
    $this->assertSame( \stdClass::class, get_class( $newInstance ));
    
    //..Should be the same instance 
    $this->assertSame( $newInstance, $this->instance->getInstance( \stdClass::class ));
  }
  
  
  /**
   * Test the newInstance() method.
   * 
   * 1. Assert that newInstance() always returns a new instance 
   * 2. Assert newInstance() returns the correct type 
   */
  public function getNewInstance()
  {
    $instance = new \stdClass();
    $this->instance->addInterface( \stdClass::class, function() {
      return new \stdClass();
    });
    
    $a = $this->instance->newInstance( \stdClass::class );
    $this->assertSame( \stdClass::class, get_class( $a ));
    
    $b = $this->instance->newInstance( \stdClass::class );
    $this->assertNotSame( $a, $b );
  }
  
  
  /**
   * Test the strict mode setting in the constructor.
   * 
   * 1. Assert that strict mode throws an exception when the closure passed to addInterface() returns an incorrect type
   * 2. Assert #1 does not happen when strict mode is disabled 
   */
  public function testStrictMode()
  {
    $ioc = new IOC( true ); //..Ensure strict mode is enabled 
    
    $this->instance->addInterface( \stdClass::class, function() {
      //..Return an anonymous class.
      return new class() {};
    });
    
    $this->expectException( \buffalokiwi\buffalotools\ioc\IOCException::class );
    $this->instance->getInstance( \stdClass::class );
    
    //..Test with strict mode disabled 
    $ioc = new IOC( false );
    $this->instance->addInterface( \stdClass::class, function() {
      //..Return an anonymous class.
      return new class() {};
    });
    
    $this->instance->getInstance( \stdClass::class );
  }
}
