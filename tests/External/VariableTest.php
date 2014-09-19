<?php
namespace League\VentTest\External;

use League\VentTest\VentTestCase;
use League\VentTest\External\Classes\User;

class VariableTest extends VentTestCase
{

    public function testReadEventOnPublicProperty()
    {
        $user = new User();
        $counter = 0;
        $user->registerEvent(
            'read',
            'name',
            function () use (&$counter) {
                $counter++;
            }
        );
        $user->name;
        $this->assertSame(1, $counter);
    }

    public function testMultipleReadEventsOnPublicProperty()
    {
        $user = new User();
        $counter = 0;

        $user->registerEvent(
            'read',
            'name',
            function () use (&$counter) {
                $counter++;
            }
        );
        $user->registerEvent(
            'read',
            'name',
            function () use (&$counter) {
                $counter++;
            }
        );

        $user->name;
        $this->assertSame(2, $counter);
    }

    public function testReadActionWithParameters()
    {
        $user = new User();

        $name = 'bill';
        $user->registerEvent(
            'read',
            'name',
            function ($name) {
                return $name;
            },
            [$name]
        );

        $this->assertEquals($name, $user->name);
    }

    public function testWriteActionWithParameters()
    {
        $user = new User();

        $newName = 'bill';
        $user->registerEvent(
            'write',
            'name',
            function (&$name) {
                $name = 'bob';
            },
            [&$newName]
        );

        $user->name = 'ben';

        $this->assertEquals('bob', $newName);
    }


    public function testReadActionWithReservedParameters()
    {
        $user = new User();
        $user->name = '';
        $user->registerEvent(
            'read',
            'name',
            function ($overloadedName) {
                return $overloadedName . '_SOMEEXTRA';
            },
            ['_OVL_NAME_']
        );

        $this->assertEquals('name_SOMEEXTRA', $user->name);
    }

    public function testWriteActionWithReservedParameters()
    {
        $user = new User();

        $firstName = 'bill';

        $user->registerEvent(
            'write',
            'name',
            function (&$firstName, $overloadedName, $overloadedValue) {
                $firstName = 'bob_' . $overloadedName . '_' . $overloadedValue;
            },
            [&$firstName, '_OVL_NAME_', '_OVL_VALUE_']
        );

        $user->name = 'ben';

        $this->assertEquals('bob_name_ben', $firstName);
    }


    public function testGetEventAlias()
    {
        $user = new User();
        $counter = 0;
        $user->registerEvent(
            'get',
            'name',
            function () use (&$counter) {
                $counter++;
            }
        );
        $user->name;
        $this->assertSame(1, $counter);
    }


    public function testReturnReadEventOnPublicProperty()
    {
        $testName = 'LeeRoyJenkins';

        $user = new User();
        $user->setName($testName);

        $string = 'SomeRandomStringABC123';
        $user->registerEvent(
            'read',
            'name',
            function () use ($string) {
                return $string;
            }
        );

        $response = $user->name;

        $this->assertSame($string, $response);
    }

    public function testWriteEventOnPublicProperty()
    {
        $user = new User();
        $counter = 0;
        $user->registerEvent(
            'write',
            'name',
            function () use (&$counter) {
                $counter++;
            }
        );
        $this->assertSame(0, $counter);
        $user->name = 'LeeRoy';
        $this->assertSame(1, $counter);
    }

    public function testSetEventAlias()
    {
        $user = new User();
        $counter = 0;
        $user->registerEvent(
            'set',
            'name',
            function () use (&$counter) {
                $counter++;
            }
        );
        $this->assertSame(0, $counter);
        $user->name = 'LeeRoy';
        $this->assertSame(1, $counter);
    }

    //Shouldn't be able to access these
    public function testReadEventOnProtectedProperty()
    {
//        $user = new User();
//        $counter = 0;
//        $user->on('read')->of('address')->run(function() use (&$counter){
//            $counter++;
//        });
//        $user->address;
//        $this->assertSame(0, $counter);
    }

    public function testWriteEventOnProtectedProperty()
    {

    }

    public function testReadEventOnPrivateProperty()
    {

    }

    public function testWriteEventOnPrivateProperty()
    {

    }
}