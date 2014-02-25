<?php

namespace VentTest;

/**
 * Tests that don't concern scope live here
 * Class VariableTest
 * @package VentTest
 */
class VariableTest extends VentTestCase
{
    /**
     * @expectedException \Exception
     */
    public function testExceptionWhenOfBeforeOn()
    {
        $user = new External\Classes\User();
        $user->of('name');
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionWhenRunBeforeOf()
    {
        $user = new External\Classes\User();
        $user->run(function(){});
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionWhenRetainingWriteEvent()
    {
        $user = new External\Classes\User();
        $user->on('write')->of('name')->run(function(){}, true);
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionWhenRetainingReadWithNullResponse()
    {
        $user = new External\Classes\User();
        $user->on('read')->of('name')->run(function(){}, true);
        $user->name;
    }

    public function testRetainingResponse()
    {
        $counter = 0;
        $readAttempts = 5;
        $runOnce = function() use (&$counter){
            $counter++;
            if ($counter > 1)
            {
                throw new \Exception('This callable should have only ever been run once!');
            }
            return 'madeUpRetainableResponse';
        };

        $user = new External\Classes\User();
        $user->on('read')->of('name')->run($runOnce, true);
        for ($x = 0; $x < $readAttempts; $x++)
        {
            $user->name;
        }
        $this->assertEquals(1, $counter);
    }

    public function testNonRetainingResponse()
    {
        $counter = 0;
        $readAttempts = 5;
        $runMany = function() use (&$counter){
            $counter++;
            return 'madeUpResponse';
        };

        $user = new External\Classes\User();
        $user->on('read')->of('name')->run($runMany, false);
        for ($x = 0; $x < $readAttempts; $x++)
        {
            $user->name;
        }

        $this->assertEquals($readAttempts, $counter);
    }


    public function testObjectReadEvent()
    {
        $user = new External\Classes\User();
        $user->name = new \StdClass();
        $firstName = 'LeeRoy';
        $user->name->first = $firstName;

        $counter = 0;
        $user->on('read')->of('name')->run(function() use (&$counter){
            $counter++;
        });

        $this->assertSame($firstName, $user->name->first);
        $this->assertEquals(1, $counter);
    }

    /**
     * Note that any writes to a property of an object WONT trigger the event (that'll need to registered in the scope of that object)
     * So $user->name->random = 'something' (name being an object) will NOT trigger an event
     */
    public function testObjectWriteEvent()
    {
        $user = new External\Classes\User();

        $counter = 0;
        $user->on('write')->of('name')->run(function() use (&$counter){
            $counter++;
        });

        $user->name = new \StdClass();
        $this->assertEquals(1, $counter);

        // Random read
        $user->name;

        $this->assertEquals(1, $counter);

        $user->name = 'backToAString';
        $this->assertEquals(2, $counter);
    }

    public function testArrayReadEvent()
    {
        $user = new External\Classes\User();
        $counter = 0;

        $firstName = 'LeeRoy';
        $user->name = ['firstName' => $firstName];
        $user->on('read')->of('name')->run(function() use (&$counter){
            $counter++;
        });

        $this->assertSame($firstName, $user->name['firstName']);
        $this->assertEquals(1, $counter);

        $user->name['firstName'];
        $this->assertEquals(2, $counter);
    }

    public function testArrayWriteEvent()
    {
        $user = new External\Classes\User();

        $counter = 0;
        $user->on('write')->of('name')->run(function() use (&$counter){
            $counter++;
        });

        $firstName = 'LeeRoy';
        $user->name = ['firstName' => $firstName];
        $this->assertEquals(1, $counter);
    }

//    public function testArrayWithOffsetWriteEvent()
//    {
//        $this->markTestSkipped('Need to implement ArrayAccess OffSetGet/Set to hook into these');
//        $user = new External\Classes\User();
//
//        $counter = 0;
//        $user->on('write')->of('name')->run(function() use (&$counter){
//            $counter++;
//        });
//
//        $firstName = 'LeeRoy';
//        $user->name['firstName'] = $firstName;
//        $this->assertEquals(1, $counter);
//    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionThrownWhenReadingReservedEventProperty()
    {
        $user = new External\Classes\User();
        $user->_ventEvents;
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionThrownWhenWritingReservedEventProperty()
    {
        $user = new External\Classes\User();
        $user->_ventEvents = '123';
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionThrownWhenReadingReservedVariableProperty()
    {
        $user = new External\Classes\User();
        $user->_ventVariables;
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionThrownWhenWritingReservedVariableProperty()
    {
        $user = new External\Classes\User();
        $user->_ventVariables = '123';
    }


    /**
     * @expectedException \Exception
     */
    public function testExceptionThrownWhenReadingReservedRegisteredProperty()
    {
        $user = new External\Classes\User();
        $user->_ventRegistered;
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionThrownWhenWritingReservedRegisteredProperty()
    {
        $user = new External\Classes\User();
        $user->_ventRegistered = '123';
    }

}