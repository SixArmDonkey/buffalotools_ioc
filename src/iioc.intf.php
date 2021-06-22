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


/**
 * Simple Inversion of control container 
 */
interface IIOC
{
  /**
   * Add an interface an a function responsible for creating instances of said 
   * interface.
   * @param string $clazz Interface name.  Interface::class works nice here and 
   * does not trigger the autoloader.
   * @param callable $factory Factory for creating new instances 
   * @throws InvalidArgumentException If class is nullor empty or if clazz has already
   * been registered.
   */
  public function addInterface( string $clazz, Callable $factory );
  
  
  /**
   * Create a new instance of some interface.
   * @param string $clazz interface name 
   * @return mixed instance to be cast as $clazz 
   */
  public function newInstance( string $clazz );
  
  
  /**
   * Retrieve a shared instance of some interface
   * @param string $clazz interface name 
   * @return mixed instance to be cast as clazz
   */
  public function getInstance( string $clazz ); 
  
  
  /**
   * Test if the specified interface has been previously registered with the container
   * @param string $clazz Class/interface name 
   * @return bool exists
   */
  public function hasInterface( string $clazz ) : bool;
  
  
  /**
   * Retrieve a list of class/interface names contained within this container.
   * @return array keys
   */
  public function getInstanceList() : array;  
  
  
  /**
   * Attempt to create an instance of the supplied class.
   * @param string $clazz Class name
   * @param array $args map of constructor argument name to value.  This map is checked prior to attempting to autoload
   * some object.  [varname => value].
   * @return object
   * @throws AutowireException
   */
  public function autowire( string $clazz, array $args = [] ) : object;  
}
