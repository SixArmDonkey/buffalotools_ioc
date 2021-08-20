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

use Closure;
use Exception;
use InvalidArgumentException;



class DefaultArgumentMapper implements IArgumentMapper
{
  /**
   * A map of argument name => closure|array 
   * @var array
   */
  private array $map;
  
  
  /**
   * @param array $map A map of argument name => closure|array 
   */
  public function __construct( array $map )
  {
    $i = 0;
    
    foreach( $map as $name => $v )
    {
      if ( !is_string( $name ))
        throw new InvalidArgumentException( 'Key for element at position ' . $i . ' must be a string' );      
      else if ( !is_array( $v ) && !( $v instanceof Closure ))
        throw new InvalidArgumentException( $name . ' value must be an array or Closure' );
      
      $this->map[$name] = $v;
      $i++;
    }
  }
  
  
  /**
   * Test if the argument mapper contains the supplied interface/class.
   * @param string $intf interface/class 
   * @return bool is registered
   */
  public function hasArgument( string $intf ) : bool
  {
    return !empty( $intf ) && isset( $this->map[$intf] );      
  }
  
  
  /**
   * Retrieve arguments for some interface/class. 
   * @param string $intf Interface/class name
   * @return array argument map.  [argument name => value]
   */
  public function getArguments( string $intf ) : array
  {
    if ( !$this->hasArgument( $intf ))
    {
      throw new InvalidArgumentException( $intf . ' is not registered with this argument mapper' );
    }
    
    $out = $this->map[$intf];
    
    if ( is_array( $out ))
      return $out;
    
    if ( $out instanceof Closure )
    {
      $out = $out();
      if ( is_array( $out ))
        return $out;
    }
    
    throw new Exception( 'Stored value for ' . $intf 
      . ' is a closure and the result of that closure must be an array.  Got ' 
      . (( is_object( $out )) ? get_class( $out ) : gettype( $out )) . '.' 
    );
  }
  
  
  /**
   * Given an interface name and a map of named arguments, if the interface exists in the mapper, then
   * merge that array with the supplied $args array with $args given precedence.  Return that new map.
   * @param string $intf interface/class name 
   * @param array $args arguments 
   * @return array argument map [argument name => value]
   */
  public function map( string $intf, array $args ) : array
  {    
    if ( !$this->hasArgument( $intf ))
    {
      return $args;   
    }
    
    foreach( $this->getArguments( $intf ) as $name => $value )
    {
      if ( !isset( $args[$name] ))
        $args[$name] = $value;
    }
    
    return $args;
  }
}
