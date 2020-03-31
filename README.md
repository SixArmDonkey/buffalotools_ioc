# BuffaloKiwi IOC / Service Locator 

A fast, simple and straightforward inversion of control / service locator library for PHP 7.4.  

MIT License

---

## Installation

```
composer require buffalokiwi/buffalotools_ioc
```
  
---

This container will maintain a single reference to any registered service.  

The basics  

1. Services are registered by calling addInterface().  The supplied closure contains the call to new class().
2. Services are instantiated on demand, and no instance is created unless newInstance() is called.
3. Calling getIntance() will call newInstance() once and cache the result.
4. Subsequent calls to getIntance() will return the cached instance.  
5. This container is not meant to be passed around to various classes.  
6. All services should be registered in a single location (composition root), and should be automatically injected into constructors via some router.  
7. When constructing the container, specifying strict mode will test that the instance returned by the closure supplied to addInstance() matches the interface supplied to addInstance() when calling newInstance().
  
  

Example:

```php
$ioc = new IOC();

$ioc->addInterface( \namespace\to\SomeAmazingClass::class, function() {
  return new SomeAmazingClass();
});

$amazing = $ioc->getInstance( \namespace\to\SomeAmazingClass::class );
```

**Note**: Using the ::class suffix is a shortcut to the fully namespaced class name, and will NOT trigger the autoloader.  

**Note 2**: DO NOT add "use" statements when registering objects with the container.  This WILL trigger the autoloader.
  
**Always use the fully namespaced class or interface name (preferably an interface).**  
