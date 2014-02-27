vent
====

PHP variable event system


| Quality / Metrics | Releases | Downloads | Licence |
| ----- | -------- | ------- | ------------- | -------- |
[![Build Status](https://travis-ci.org/leedavis81/vent.png?branch=master)](https://travis-ci.org/leedavis81/vent) [![Coverage Status](https://coveralls.io/repos/leedavis81/vent/badge.png?branch=master)](https://coveralls.io/r/leedavis81/vent?branch=master) | [![Latest Stable Version](https://poser.pugx.org/leedavis81/vent/v/stable.png)](https://packagist.org/packages/leedavis81/vent) [![Latest Unstable Version](https://poser.pugx.org/leedavis81/vent/v/unstable.png)](https://packagist.org/packages/leedavis81/vent) | [![Total Downloads](https://poser.pugx.org/leedavis81/vent/downloads.png)](https://packagist.org/packages/leedavis81/vent) | [![License](https://poser.pugx.org/leedavis81/vent/license.png)](https://packagist.org/packages/leedavis81/vent)

## Installation

Install via [composer](https://getcomposer.org/):
```sh
php composer.phar require leedavis81/vent:dev-master
```

## Usage

Have you ever needed to hook an event anytime a PHP variable is read? Maybe you want to ensure complete immutability even within the scope (private) of your class.
PHP variable events can be easily created by hooking into the read or write of any variable.

```php
<?php
class Foo
{
    use Vent\VentTrait;
   
    private $bar;
   
    public function __construct()
    {
        $this->registerEvent('read', 'bar', function(){
            throw new \Exception('Don\'t touch my bar!');
        });
    }
    
    public function touchBar()
    {
        $this->bar;
    }
}

$foo = new Foo();
$foo->touchBar(); // Fatal error: Uncaught exception 'Exception' with message 'Don't touch my bar!'
```

Or you can register a write event to protect the variable from being overwritten (even from within the scope of your class)

```php
$this->registerEvent('write', 'bar', function(){
  throw new \Exception('Don\'t write to my bar!');
});
        
public function writeToBar()
{
  $this->bar = 'somethingElse';
}        
        
$foo = new Foo();
$foo->writeToBar(); // Fatal error: Uncaught exception 'Exception' with message 'Don't write to my bar!'        
```

You can masquerade any value by returning something from your registered event. Note that if multiple events are registered, execution they will stop once one of them returns a response. They are triggered in the order they're registered (first in, first out).

```php

public $bar = 'Bill';

public function __construct()
{
  $this->registerEvent('read', 'bar', function(){
    return 'Ben';
  });
}
        
        
$foo = new Foo();
echo $foo->bar; // "Ben"
```

To pass in parameters into your method simply provide them when registering the event.

Please note there are two reserved strings that if passed in as a parameter will be replaced
- `_OVL_NAME_`  replaced with the name of the variable you're accessing (set or get)
- `_OVL_VALUE_`  replaced with the value your updating a variable with (set only)

```php

public $bar;
public function __construct()
{
  $this->registerEvent('write', 'bar', function($var1, $name, $value){
    echo 'Why ' . $var1 . ' you\'re trying to overwrite "' . $name . '" to contain "' . $value . '"';
  }, ['bill', '_OVL_NAME_', '_OVL_VALUE_']);
}

$foo = new Foo();
$foo->bar = 'cheese';      // Why bill you're trying to overwrite "bar" to contain "cheese"
```


If you are returning a response on your event, this can be retained to prevent additional execution on further reads.

```php

public $bar = 'Bill';

public function __construct()
{
  $this->registerEvent('read','bar', function(){
    sleep(1);
    return microtime();
  }, null, true);     // pass in "true" here (defaults to false)
}
        
        
$foo = new Foo();
var_dump($foo->bar === $foo->bar);   // true
```

All events must be registered within the context of your class when using `VentTrait`. However, if you'd like to register them from a public or protected scope then simply change the scope of the `registerEvent` method when importing the trait

```php
<?php
class Foo
{
    use Vent\VentTrait {registerEvent as public;}   // allow public event registration
   
    public $bar;
}

$foo = new Foo();

$foo->registerEvent('read', 'bar', function(){
    throw new \Exception('Don\'t touch my bar!');
});

$foo->bar;  // Fatal error: Uncaught exception 'Exception' with message 'Don't touch my bar!'
```

### But you stole my magic
It's true that this little trait applies a `__get` and `__set` method to your class. If your class already has a little magic and these these methods have already been applied then they'll overwrite the trait implementation. To get around this you can simply import them with a different method name, and call them in your own magic methods. For example:
```php 

class Foo()
{
    use Vent\VentTrait {__get as get;}
    
    public function __get($name)
    {
        // You're own magic stuff will go here.
        $this->get($name);   // Fire off the vent magic (if you need it)
    }
}

```



### todos
- Allow event triggering for array offset reads $foo->bar['offset'];
- Expand event scope to include 'delete'
- Add a method to remove variables from proxy (to increase perf)
