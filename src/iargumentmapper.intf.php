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

namespace buffalokiwi\buffalotools\ioc;



/**
 * The argument mapper is an optional component that may be passed to some IIOC implementation, and provides
 * a way to configure certain arguments in a more global way.
 * 
 * For example, say we have some view.  The view requires a configuration object that points to templates in a certain 
 * theme directory.  We want to configure that outside of the composition root code.
 * 
 * The idea is to pass a map of [interface/class name => function() : array] to some argument mapper implementation.
 * When IIOC::autowire() is called, The argument mapper is checked for a matching interface/class name.  If found, the 
 * corresponding closure is called, which would be merged with the arguments passed to the autowire method.  
 * Arguments passed to autowire must overwrite the arguments in the mapper.
 * 
 */
interface IArgumentMapper 
{
  /**
   * Given an interface name and a map of named arguments, if the interface exists in the mapper, then
   * merge that array with the supplied $args array with $args given precedence.  Return that new map.
   * @param string $intf interface/class name 
   * @param array $args arguments 
   * @return array argument map [argument name => value]
   */
  public function map( string $intf, array $args ) : array;
  
  
  /**
   * Retrieve arguments for some interface/class. 
   * @param string $intf Interface/class name
   * @return array argument map.  [argument name => value]
   */
  public function getArguments( string $intf ) : array;
  
  
  /**
   * Test if the argument mapper contains the supplied interface/class.
   * @param string $intf interface/class 
   * @return bool is registered
   */
  public function hasArgument( string $intf ) : bool;  
}
