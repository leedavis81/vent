vent
====

PHP variable event system


| Quality / Metrics | Releases | Downloads | Licence |
| ----- | -------- | ------- | ------------- | -------- |
[![Build Status](https://travis-ci.org/leedavis81/vent.png?branch=master)](https://travis-ci.org/leedavis81/vent) [![Coverage Status](https://coveralls.io/repos/leedavis81/vent/badge.png?branch=master)](https://coveralls.io/r/leedavis81/vent?branch=master) | [![Latest Stable Version](https://poser.pugx.org/leedavis81/vent/v/stable.png)](https://packagist.org/packages/leedavis81/vent) [![Latest Unstable Version](https://poser.pugx.org/leedavis81/vent/v/unstable.png)](https://packagist.org/packages/leedavis81/vent) | [![Total Downloads](https://poser.pugx.org/leedavis81/vent/downloads.png)](https://packagist.org/packages/leedavis81/vent) | [![License](https://poser.pugx.org/leedavis81/vent/license.png)](https://packagist.org/packages/leedavis81/vent)

Have you ever needed to hook an event anytime a PHP variable is read? Maybe you want to ensure complete immutability even within the scope (private) of you class.
PHP variable events can be easily created by hooking into the read or write of any variable.

```php
<?php
class Foo
{
    use Vent\VentTrait;
   
    private $bar;
   
    public function __construct()
    {
        $this->on('read')->of('bar')->run(function(){
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
$this->on('write')->of('bar')->run(function(){
  throw new \Exception('Don\'t write to my bar!');
});
        
public function writeToBar()
{
  $this->bar = 'somethingElse';
}        
        
$foo = new Foo();
$foo->writeToBar(); // Fatal error: Uncaught exception 'Exception' with message 'Don't write to my bar!'        
```

You can masquerade any value by returning a something from your registered event. Note that if multiple events are registered, execution they will stop once one of them returns a response. They are triggered in the order they're registered (first in, first out).

```php

public $bar = 'Bill';

public function __construct()
{
  $this->on('read')->of('bar')->run(function(){
    return 'Ben';
  });
}
        
        
$foo = new Foo();
echo $foo->bar; // "Ben"
```

If you are returning a response on your event, this can be retained to prevent additional execution on further reads.

```php

public $bar = 'Bill';

public function __construct()
{
  $this->on('read')->of('bar')->run(function(){
    sleep(1);
    return microtime();
  }, true);     // pass in "true" here (defaults to false)
}
        
        
$foo = new Foo();
var_dump($foo->bar === $foo->bar);   // true
```

todo:
- Allow event triggering for array offset reads $foo->bar['offset'];
- Need to inject variables into the callable event
