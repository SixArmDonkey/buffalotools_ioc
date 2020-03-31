<?php
/**
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.txt', which is part of this source code package.
 *
 * Copyright (c) 2012-2020 John Quinn <john@retail-rack.com>
 * 
 * @author John Quinn
 */

namespace buffalokiwi\buffalotools\ioc;

use InvalidArgumentException;

/**
 * Inversion of control / service locator.
 * 
 * This container will maintain a single reference to any registered service.
 * Services are registered by calling addInterface().  The supplied closure contains the call to new class().
 * Services are instantiated on demand, and no instance is created unless newInstance() is called.
 * Calling getIntance() will call newInstance() once and cache the result.
 * Subsequent calls to getIntance() will return the cached instance.
 * 
 * This container is not meant to be passed around to various classes.  
 * All services should be registered in a single location (composition root), and should be automatically injected into 
 * constructors via some router.
 * 
 * When constructing the container, specifying strict mode will test that the instance returned by the closure supplied
 * to addInstance() matches the interface supplied to addInstance() when calling newInstance().
 * 
 * Example:
 * 
 * $ioc = new IOC();
 * 
 * $ioc->addInterface( \namespace\to\SomeAmazingClass::class, function() {
 *   return new SomeAmazingClass();
 * });
 * 
 * $amazing = $ioc->getInstance( \namespace\to\SomeAmazingClass::class );
 * 
 * 
 * Note: Using the ::class suffix is a shortcut to the fully namespaced class name, and will NOT trigger the autoloader.
 * Note #2: DO NOT add "use" statements when registering objects with the container.  This WILL trigger the autoloader.
 * Always use the fully namespaced class or interface name (preferably an interface).
 */
class IOC implements IIOC
{
  /**
   * Map of class => factory 
   * @var array
   */
  private array $data = [];
  
  /**
   * Map of class => instance 
   * @var array 
   */
  private array $instances = [];
  
  /**
   * Strict mode
   * @var bool 
   */
  private bool $strict;
  
  
  /**
   * Create a new IOC instance 
   * @param bool $strict If true, then returned instances will test 
   * instanceof against the supplied interface/class name, and an exception
   * will be thrown if they don't match.
   */
  public function __construct( bool $strict = true )
  {
    $this->strict = $strict;
  }
  
  
  /**
   * Add an interface an a function responsible for creating instances of said 
   * interface.
   * @param string $clazz Interface name.  Interface::class works nice here and 
   * does not trigger the autoloader.
   * @param callable $factory Factory for creating new instances 
   * @throws InvalidArgumentException If class is nullor empty or if clazz has already
   * been registered.
   */
  public function addInterface( string $clazz, Callable $factory ) : void
  {
    $this->testNullOrEmpty( $clazz );
    if ( $factory == null )
      throw new InvalidArgumentException( 'factory must not be null' );
    else if ( isset( $this->data[$clazz] ))
      throw new InvalidArgumentException( $clazz . ' has already been added to this dependency container.' );
    
    $this->data[$clazz] = $factory;
  }
  
  
  
  /**
   * Create a new instance of some interface.
   * @param string $clazz interface name 
   * @return mixed instance to be cast as $clazz 
   */
  public function newInstance( string $clazz )
  {
    $this->testRegistered( $clazz );    
    
    $instance = $this->data[$clazz]();
    if ( $this->strict && !is_a( $instance, $clazz, false ))
    {
      throw new IOCException( 'IoC container contains an incorrect definition for type ' . $clazz . ' got ' . get_class( $instance ));
    }
    
    return $instance;
  }
  
  
  /**
   * Retrieve a shared instance of some interface
   * @param string $clazz interface name 
   * @return mixed instance to be cast as clazz
   */
  public function getInstance( string $clazz )
  {
    $this->testRegistered( $clazz );
    
    if ( !isset( $this->instances[$clazz] ))
      $this->instances[$clazz] = $this->newInstance( $clazz );
    
    return $this->instances[$clazz];
  }


  /**
   * Test to see if some class has been registered with this container 
   * @param string $clazz class name
   * @throws InvalidArgumentException If not registered
   */
  private function testRegistered( string $clazz ) : void
  {
    $this->testNullOrEmpty( $clazz );
    if ( !isset( $this->data[$clazz] ))
      throw new IOCException( $clazz . ' has not been registered with this container' );    
  }
  
  
  /**
   * Tests to see if a string is not null or empty 
   * @param string $clazz class name 
   * @throws InvalidArgumentException if the string is null or empty 
   */
  private function testNullOrEmpty( string $clazz ) : void
  {
    if ( $clazz == null || empty( trim( $clazz )))
      throw new InvalidArgumentException( "clazz must not be null or empty" );    
  }
}