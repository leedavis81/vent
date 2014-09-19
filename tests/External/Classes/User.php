<?php
namespace League\VentTest\External\Classes;

use League\Vent\VentTrait;

/**
 * Class User
 * Example User Model used for testing
 * @package League\VentTest\External\Classes
 */
class User
{
    use VentTrait {
        registerEvent as public;
    }

    public $name;
    protected $address;
    private $password;

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}