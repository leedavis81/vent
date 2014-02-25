<?php
namespace VentTest\External\Classes;

use Vent\VentTrait;

class User
{
    use VentTrait {registerEvent as public;}

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