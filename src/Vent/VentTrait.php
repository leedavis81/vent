<?php
namespace Vent;

trait VentTrait
{
    /**
     * A proxy of variables
     * @var array $variables
     */
    private $_ventVariables;

    /**
     * Registered events ['read' => ..., 'write' => ...]
     * @var array $readEvents
     */
    private $_ventEvents = [];

    /**
     * Register an event to be triggered
     * @param $event - the name of the event - can be 'read', 'write' or an array
     * @param $variables - the name(s) of the variables(s) to trigger the event on
     * @param callable $action - The action to be triggered
     * @param array|null $params - an array of parameters to be passed into the action
     * @param bool $retainResponse
     */
    private function registerEvent($event, $variables, \Closure $action, $params = null, $retainResponse = false)
    {
        // this should really be move into some form of init(), should we steal the __construct?
        if (!isset($this->_ventEvents['read']['_readEvents']))
        {
            $function = function(){
                throw new \Exception('This property is reserved for Vent and cannot be used');
            };

            $this->_ventEvents['read']['_ventVariables'][] = ['callable' => $function, 'retain' => false];
            $this->_ventEvents['read']['_ventEvents'][] = ['callable' => $function, 'retain' => false];
            $this->_ventEvents['write']['_ventVariables'][] = ['callable' => $function];
            $this->_ventEvents['write']['_ventEvents'][] = ['callable' => $function];
        }

        foreach (array_filter((array) $event, function($item) {
            return in_array($item, ['read', 'write', 'get', 'set']);
        }) as $event)
        {
            foreach (array_unique((array) $variables) as $var)
            {
                // Don't check and fail on property_exists, they may be overloading
                // this should only occur once per variable, to prevent re-reads occurring ($this->$var will trigger __get)
                if (!isset($this->_ventVariables[$var]))
                {
                    // The property could exist but isn't set yet, force it as null to avoid additional overload look ups for multiple events
                    $this->_ventVariables[$var] = ($this->$var !== null) ? $this->$var : new Null();

                    // Only attempt unset once we have it copied
                    unset($this->$var);
                }

                if ($event === 'read' || $event === 'get')
                {
                    $this->_ventEvents['read'][$var][] = ['callable' => $action, 'params' => (array) $params, 'retain' => $retainResponse];
                } elseif ($event === 'write' || $event === 'set')
                {
                    $this->_ventEvents['write'][$var][] = ['callable' => $action, 'params' => (array) $params];
                }
            }
        }
    }

    /**
     * Handle a write request
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_ventEvents['write']))
        {
            $eventSize = sizeof($this->_ventEvents['write'][$name]);
            for ($x = 0; $x < $eventSize; $x++)
            {
                // replace reserved keyword
                $paramSize = sizeof($this->_ventEvents['write'][$name][$x]['params']);
                for($y = 0; $y < $paramSize; $y++)
                {
                    if ($this->_ventEvents['write'][$name][$x]['params'][$y] == '_OVL_NAME_')
                    {
                        $this->_ventEvents['write'][$name][$x]['params'][$y] = $name;
                    } elseif ($this->_ventEvents['write'][$name][$x]['params'][$y] == '_OVL_VALUE_')
                    {
                        $this->_ventEvents['write'][$name][$x]['params'][$y] = $value;
                    }
                }

                // execute the action
                call_user_func_array(
                    $this->_ventEvents['write'][$name][$x]['callable'],
                    $this->_ventEvents['write'][$name][$x]['params']
                );
            }
        }

        if ($this->_ventVariables[$name])
        {
            $this->_ventVariables[$name] = $value;
        }

        return $this->_ventVariables[$name];
    }


    /**
     * Handle read request
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function &__get($name)
    {
        if (array_key_exists($name, $this->_ventEvents['read']))
        {
            // Everything else can check the read events
            $eventSize = sizeof($this->_ventEvents['read'][$name]);
            for ($x = 0; $x < $eventSize; $x++)
            {
                // Short circuit for retained read responses
                if ($this->_ventEvents['read'][$name][$x]['retain'] && isset($this->_ventEvents['read'][$name][$x]['response']))
                {
                    return $this->_ventEvents['read'][$name][$x]['response'];
                }

                // replace reserved keyword
                $paramSize = sizeof($this->_ventEvents['read'][$name][$x]['params']);
                for($y = 0; $y < $paramSize; $y++)
                {
                    if ($this->_ventEvents['read'][$name][$x]['params'][$y] == '_OVL_NAME_')
                    {
                        $this->_ventEvents['read'][$name][$x]['params'][$y] = $name;
                    }
                }

                // execute the action
                $response = call_user_func_array(
                    $this->_ventEvents['read'][$name][$x]['callable'],
                    $this->_ventEvents['read'][$name][$x]['params']
                );

                if ($this->_ventEvents['read'][$name][$x]['retain'] && is_null($response))
                {
                    throw new \Exception('Event registered on read of variable "' . $name . '" does not return a retainable response - cannot be null');
                }

                // If we have a response, return it
                if (isset($response))
                {
                    if ($this->_ventEvents['read'][$name][$x]['retain'])
                    {
                        $this->_ventEvents['read'][$name][$x]['response'] = $response;
                        return $this->_ventEvents['read'][$name][$x]['response'];
                    }
                    return $response;
                }
            }
        }

        // If the variable is unset, or a Null instance return a local object reference;
        if (!isset($this->_ventVariables[$name]) || $this->_ventVariables[$name] instanceof Null)
        {
            return $this->$name;
        }

        return $this->_ventVariables[$name];
    }
}