<?php
namespace SajedZarinpour\Meloquent\Concerns;

use ReflectionProperty;

trait hasCallableProperty {
    use reportsError;
    /**
     * say you have a property like the following in your class:
     *      public Closure $hello;
     * then you can call it like:
     *      $instance->hello($params)
     */
    public function __call($name, $arguments)
    {
        if(is_callable($this->$name)) {
            return ($this->$name)(...$arguments);
            // not the same as: 
            //   $function = new ReflectionFunction($this->$name);
            //   return $function->invokeArgs($arguments);
            // try with an invokable class as the callable property and you'll see the difference
        } else {
            // Note: value of $name is case sensitive.
            // throw new Exception("Calling inaccessible object method '$name', params: " . implode(', ', $arguments). "\n");
            $this->throws("Calling inaccessible object method '$name', params: " . implode(', ', $arguments). "\n");
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
        } else {
            // Note: value of $name is case sensitive.
            static::throws("Calling inaccessible static method '$name' ". implode(', ', $arguments). "\n");
        }
    }
}