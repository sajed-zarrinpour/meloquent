<?php
namespace SajedZarinpour\Meloquent\Concerns;

use ReflectionObject;
use ReflectionProperty;
use SajedZarinpour\Meloquent\Exceptions\InAccessibleFunctionCallException;

trait HasCallableProperty {
    use reportsError;
    /**
     * say you have a property like the following in your class:
     *      public Closure $hello;
     * then you can call it like:
     *      $instance->hello($params)
     */
    public function __call($name, $arguments)
    {

        if(
            (new ReflectionObject($this))->hasProperty($name) 
            && is_callable($this->$name)
        ) {
            return ($this->$name)(...$arguments);
            // not the same as: 
            //   $function = new ReflectionFunction($this->$name);
            //   return $function->invokeArgs($arguments);
            // try with an invokable class as the callable property and you'll see the difference
        } else {
            throw new InAccessibleFunctionCallException($name, $arguments);
        }
    }

    /**
     * say you have a property like the following in your class:
     *      public static Closure $hello;
     * then say you set this like:
     *      $instance::$hello = fn() => true;
     * then you can call it like:
     *      $instance::hello()
     */
    public static function __callStatic($name, $arguments)
    {
        $property = (new ReflectionProperty(static::class, $name))->getValue();
        if(is_callable($property)) {
            return ($property)(...$arguments);
        }
    }
}