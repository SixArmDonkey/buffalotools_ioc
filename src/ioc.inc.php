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
   * Attempt to create an instance of the supplied class.
   * @param string $clazz Class name
   * @param array $args map of constructor argument name to value.  This map is checked prior to attempting to autoload
   * some object.  [varname => value].
   * @return object
   * @throws AutowireException
   */
  public function autowire( string $clazz, array $args = [] ) : object
  {
    if ( $this->hasInterface( $clazz ))
      return $this->getInstance( $clazz );
    else if ( !class_exists( $clazz, true ))
      throw new AutowireException( $clazz . ' cannot be found' );   

    $c = new \ReflectionClass( $clazz );
    $params = $c->getConstructor()?->getParameters();

    if ( !is_iterable( $params ))
    {
      //..Might be a class
      return new $clazz( ...$args );
    }
    
    $cArgs = [];
    
    
    foreach( $params as $param )
    { 
      if ( $param->isVariadic())
        throw new AutowireException( 'Variadic arguments may not be autowired' );
      
      $rt = $param->getType()?->getName();
      /* @var $param \ReflectionParameter */
      
      if ( !$rt )
        throw new AutowireException( 'All constructor arguments for class ' . $clazz . ' must have a declared type.' );
      
      $name = $param->getName();
      
      if ( isset( $args[$name] ) && !is_array( $args[$name] ))
        $cArgs[] = $args[$name];
      else if ( isset( $args[$name] ) && is_array( $args[$name] ) && $rt == 'array' )
        $cArgs[] = $args[$name];
      else if ( $this->hasInterface( $rt ))
        $cArgs[] = $this->getInstance( $rt );
      else if ( class_exists( $rt ))
        $cArgs[] = $this->autowire( $rt, $args[$name] ?? [] );
      else 
      {
        throw new AutowireException( 'Cannot determine value for ' . $clazz .  ' constructor argument "' . $name . '" of type "' . $rt . '".  Try declaring this argument in the args array argument.' );
      }
    }

    return new $clazz( ...$cArgs );    
  }
  
  
  /**
   * Retrieve a list of class/interface names contained within this container.
   * @return array keys
   */
  public function getInstanceList() : array
  {
    return array_keys( $this->data );
  }  
  
  
  /**
   * Add an interface an a function responsible for creating instances of said 
   * interface.
   * @param string $clazz Interface name.  Interface::class works nice here and 
   * does not trigger the autoloader.
   * @param callable $factory Factory for creating new instances 
   * @param bool $overwrite If an interface has already been registered, setting this to true will overwrite the previous
   * entry and will NOT throw an exception.  False (default) simply throws an exception.
   * @throws InvalidArgumentException If class is null or empty or if clazz has already
   * been registered.
   */
  public function addInterface( string $clazz, Callable $factory, bool $overwrite = false ) : void
  {
    $this->testNullOrEmpty( $clazz );
    if ( $factory == null )
      throw new InvalidArgumentException( 'factory must not be null' );
    else if ( !$overwrite && isset( $this->data[$clazz] ))
      throw new InvalidArgumentException( $clazz . ' has already been added to this dependency container.' );
    
    $this->data[$clazz] = $factory;
  }
  
  
  public function addAutoInterface( string $interface, string $clazz, array $args, bool $overwrite = false ) : void
  {
    $self = $this;
    $this->addInterface( $interface, function() use($clazz,$self,&$args) {
      return $self->autowire( $clazz, $args );
    }, $overwrite );
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
   * Test if the specified interface has been previously registered with the container
   * @param string $clazz Class/interface name 
   * @return bool exists
   */
  public function hasInterface( string $clazz ) : bool
  {
    return isset( $this->data[$clazz] );
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
    { 
      throw new IOCException( $clazz . ' has not been registered with this container.' );    
    }
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
