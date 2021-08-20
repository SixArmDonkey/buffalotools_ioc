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
use PHPUnit\Framework\TestCase;


/**
 * Tests the DefaultArgumentMapper class
 */
class DefaultArgumentMapperTest extends TestCase
{
  
  
  public function setUp() : void
  {
   
  }  



  /**
   * Test that the map passed to the constructor must contain string keys and that 
   * the array elements are either arrays or instances of closure.
   * @return void
   */
  public function testConstructor() : void
  {
    //..Test that an empty array throws nothing
    new DefaultArgumentMapper( [] );
    
    //..Test that string keys and either an array or closure throws nothing, and that multiple entries are accepted
    new DefaultArgumentMapper( ['array' => [], 'closure' => function() {}] );
    
    //..Test that numeric keys throw an exception 
    try {
      new DefaultArgumentMapper( [true] );
      $this->fail( 'When $map contains numeric keys, an exception must be thrown' );
    } catch( \Exception $e ) {
      //..Expected 
    }
    
    //..Test that values that are not an array or closure throws an exception 
    //..Using expectException since this is the last test, and so the risky test message is removed.  
    $this->expectException( InvalidArgumentException::class );
    new DefaultArgumentMapper( ['test' => 123] );
  }
  
  
  /**
   * Tests hasArgument().
   * 
   * Ensure that passing an argument to hasArgument that has been passed to the constructor returns true.
   * 
   * @return void
   */
  public function testHasArgument() : void
  {
    $testClass = 'arg';
    $instance = new DefaultArgumentMapper( [$testClass => []] );
    
    $this->assertTrue( $instance->hasArgument( $testClass ));
    $this->assertFalse( $instance->hasArgument( '__INVALID__' ));
  }
  
  
  /**
   * Ensure that getArguments() correctly returns the arguments supplied to the constructor when the value is an array
   * or a closure.
   * 
   * Test that multiple class/instance names passed to the constructor correctly return
   * 
   * 
   * @return void
   */
  public function testGetArguments() : void
  {
    $testArrayClass = 'array';
    $testClosureClass = 'closure';
    $argArray = ['arg' => true, 'arg2' => false];    
    
    
    $instance = new DefaultArgumentMapper([
       $testArrayClass => $argArray,
       $testClosureClass => fn() => $argArray
    ]);
    
    $this->assertSame( $argArray, $instance->getArguments( $testArrayClass ));
    $this->assertSame( $argArray, $instance->getArguments( $testClosureClass ));
  }
  
  
  /**
   * Test that calling map with an invalid class/interface throws an exception
   * Test that calling map with empty $args and without the supplied interface/class name existing the mapper returns an empty array.
   * Test that calling map with $args and without the the supplied interface/class name existing the mapper returns $args.
   * Test that calling map with empty $args and with an existing and filled array in the mapper returns the mapper array.
   * Test that calling map with $args and existing mapper values and without key collisions between $args and the mapper array returns all of the keys/values from both arrays (merge).
   * Test that calling map with $args and existing mapper values and with key collisions between $args and the mapper array returns all of the keys/values from both arrays (merge) 
   * with precedence given to $args.
   * 
   * @return void
   */
  public function testMap() : void
  {
    
    $testClass = 'class';
    $dummy = 'dummy';
    $testArgs = ['arg' => true];
    $testArgs2 = ['arg2' => false];
    
    $mergedArgs = $testArgs + $testArgs2;
    
    $instance = new DefaultArgumentMapper( [$testClass => $testArgs, $dummy => []] );
    $this->assertEquals( $mergedArgs, $instance->map( $testClass, $testArgs2 ));
    
    try {
      $instance->map( '__INVALID__', [] );
      $this->fail( 'Supplying a class/interafce to map() that has not been registered with argument mapper must throw an exception' );
    } catch( \Exception $e ) {
      //..expected
    }
    
    $this->assertEquals( [], $instance->map( $dummy, [] ));
    $this->assertSame( $testArgs, $instance->map( $dummy, $testArgs ));
    $this->assertSame( $testArgs, $instance->map( $testClass, [] ));
    
    
    $this->assertSame( ['arg' => false], $instance->map( $testClass, ['arg' => false] ));
  }
}
