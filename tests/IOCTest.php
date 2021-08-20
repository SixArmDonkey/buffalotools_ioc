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

use buffalokiwi\buffalotools\ioc\DefaultArgumentMapper;
use buffalokiwi\buffalotools\ioc\IIOC;
use buffalokiwi\buffalotools\ioc\IOC;
use buffalokiwi\buffalotools\ioc\IOCException;
use PHPUnit\Framework\TestCase;


class IOCAutowireInterfaceArgument
{
  public string $param = 'interfacearg';
}

class IOCAutowireClassArgument
{
  public string $param = 'classarg';
}

class IOCAutowireClass
{
  public function __construct(
    public IOCAutowireClassArgument $classArg,
    public string $scalarArg,
    public IOCAutowireInterfaceArgument $interfaceArg
  ) {}
}


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
  public function testAddInterface() : void
  {
    try {
      $this->instance->addInterface( null, function(){});
      $this->fail( 'Interface name must not be null/empty.  Ensure this throws TypeError' );
    } catch( TypeError $e ) {
      //..Expected
    }
    
    try {
      $this->instance->addInterface( 'someinterface', null );
      $this->fail( 'Argument #2 of addInterface() must be a Closure.  Ensure this throws TypeError' );
    } catch( TypeError $e ) {
      //..Expected
    }
    
    try {
      $this->instance->addInterface( '', function(){});
      $this->fail( 'Interface name must not be an empty string.  Ensure this throws InvalidArgumentException' );
    } catch( InvalidArgumentException $e ) {
      //..Expected
    }
    
    $this->instance->addInterface( 'interface', function(){});
    
    try {
      $this->instance->addInterface( 'interface', function(){});
      $this->fail( 'The same interface may not be added twice.  Ensure this throws InvalidArgumentException' );
    } catch( InvalidArgumentException $e ) {
      //..Expected
    }
    
    $this->expectNotToPerformAssertions();
  }
  
  
  /**
   * Test the getInstance() method.
   * 
   * 1. Assert that calling getInstance() returns the same instance supplied to addInterface()
   * 2. Assert calling getInstance() again returns the same instance 
   * 3. Assert newInstance() returns the correct type 
   */
  public function testGetInstance() : void
  {
    $this->instance->addInterface( stdClass::class, function() {
      return new stdClass();
    });
    
    $newInstance = $this->instance->getInstance( stdClass::class );
    $this->assertSame( stdClass::class, get_class( $newInstance ));
    
    //..Should be the same instance 
    $this->assertSame( $newInstance, $this->instance->getInstance( stdClass::class ));
  }
  
  
  /**
   * Test the newInstance() method.
   * 
   * 1. Assert that newInstance() always returns a new instance 
   * 2. Assert newInstance() returns the correct type 
   */
  public function testGetNewInstance() : void
  {
    $instance = new stdClass();
    $this->instance->addInterface( stdClass::class, function() {
      return new stdClass();
    });
    
    $a = $this->instance->newInstance( stdClass::class );
    $this->assertSame( stdClass::class, get_class( $a ));
    
    $b = $this->instance->newInstance( stdClass::class );
    $this->assertNotSame( $a, $b );
  }
  
  
  /**
   * Test the strict mode setting in the constructor.
   * 
   * 1. Assert that strict mode throws an exception when the closure passed to addInterface() returns an incorrect type
   * 2. Assert #1 does not happen when strict mode is disabled 
   */
  public function testStrictMode() : void
  {
    $ioc = new IOC( true ); //..Ensure strict mode is enabled 
    
    $ioc->addInterface( stdClass::class, function() {
      //..Return an anonymous class.
      return new class() {};
    });
    
    try {
      $ioc->getInstance( stdClass::class );
      $this->fail( 'When getInstance() returns an object NOT an instance of the supplied interface/class name, IOCException must be thrown' );
    } catch( IOCException $e ) {
      //..Expected
    }
    
    //..Test with strict mode disabled 
    $ioc = new IOC( false );
    $ioc->addInterface( stdClass::class, function() {
      //..Return an anonymous class.
      return new class() {};
    });
    
    $ioc->getInstance( stdClass::class );
    
    $this->expectNotToPerformAssertions();
  }
  
  
  
  /**
   * Test that adding an interface to the container returns the interface as part of this list.
   */
  public function testGetInterfaceList() : void
  {
    $ioc = new IOC( false );
    
    $ioc->addInterface( 'TestInterface', function() {
      return new stdClass();
    });
    
    $this->assertTrue( in_array( 'TestInterface', $ioc->getInstanceList()));
  }
  
  
  public function testAutowire() : void
  {
    $ioc = new IOC();
    $ioc->addInterface( IOCAutowireInterfaceArgument::class, function() {
      return new IOCAutowireInterfaceArgument();
    });
    
    $instance = $ioc->autowire( IOCAutowireClass::class, ['scalarArg' => 'stringValue'] );
    
    $this->assertNotEmpty( $instance );
    $this->assertInstanceOf( IOCAutowireClassArgument::class, $instance->classArg );
    $this->assertInstanceOf( IOCAutowireInterfaceArgument::class, $instance->interfaceArg );
    $this->assertEquals( 'stringValue', $instance->scalarArg );    
  }  
  
  
  /**
   * This is more of an integration test to ensure that IOC correctly calls IArgumentMapper::map() within autowire().
   * @return void
   */
  public function testDefaultArgumentMapper() : void
  {
    $ioc = new IOC( false, new DefaultArgumentMapper([ IOCAutowireClass::class => ['scalarArg' => 'stringValue']] ));
    
    $ioc->addInterface( IOCAutowireInterfaceArgument::class, function() {
      return new IOCAutowireInterfaceArgument();
    });    
        
    $instance = $ioc->autowire( IOCAutowireClass::class );
    $this->assertNotEmpty( $instance );
    $this->assertEquals( 'stringValue', $instance->scalarArg );    
  }
}
