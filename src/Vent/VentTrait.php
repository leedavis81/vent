<?php
namespace Vent;

trait VentTrait
{
    /**
     * A proxy of variables
     * @var array $_ventVariables
     */
    private $_ventVariables;

    /**
     * Registered events ['read' => ..., 'write' => ...]
     * @var array $_ventEvents
     */
    private $_ventEvents;

    /**
     * Reserved functions, registered as closures to avoid class littering
     * @var array $_ventFunctions
     */
    private $_ventFunctions;

    /**
     * Register an event to be triggered
     * @param string $event - the name of the event - can be 'read', 'write' or an array
     * @param string|array $variables - the name(s) of the variables(s) to trigger the event on
     * @param \Closure $action - The action to be triggered
     * @param array|null $params - an array of parameters to be passed into the action
     * @param bool $retainResponse
     */
    private function registerEvent($event, $variables, \Closure $action, $params = null, $retainResponse = false)
    {
        // this should really be move into some form of init(), can we steal the __construct?
        if (!isset($this->_ventEvents['read']['_readEvents']))
        {
            $function = function(){
                throw new \Exception('This property is reserved for Vent and cannot be used');
            };

            foreach (['read', 'write', 'delete'] as $eventName)
            {
                $this->_ventEvents[$eventName]['_ventVariables'][] = ['callable' => $function];
                $this->_ventEvents[$eventName]['_ventEvents'][] = ['callable' => $function];
                $this->_ventEvents[$eventName]['_ventFunctions'][] = ['callable' => $function];
            }

            $this->_ventFunctions['replaceReservedParams'] = function($params, $name, $value = null){
                $paramSize = sizeof($params);
                for($x = 0; $x < $paramSize; $x++)
                {
                    if ($params[$x] === '_CUR_VALUE_')
                    {
                        $params[$x] = (isset($this->_ventVariables[$name])) ? $this->_ventVariables[$name] : null;
                    } elseif ($params[$x] === '_OVL_NAME_')
                    {
                        $params[$x] = $name;
                    } elseif ($params[$x] === '_OVL_VALUE_' && $value !== null)
                    {
                        $params[$x] = $value;
                    }
                }
                return $params;
            };
        }

        foreach (array_filter((array) $event, function($item) {
            return in_array($item, ['read', 'write', 'get', 'set', 'delete', 'unset']);
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
                    $this->_ventEvents['write'][$var][] = ['callable' => $action, 'params' => (array) $params, 'retain' => $retainResponse];
                } elseif ($event === 'delete' || $event === 'unset')
                {
                    $this->_ventEvents['delete'][$var][] = ['callable' => $action, 'params' => (array) $params];
                }
            }
        }
    }

    /**
     * Handle a write request
     * @param $name
     * @param $value
     * @throws \Exception
     * @return mixed
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_ventEvents['write']))
        {
            $eventSize = sizeof($this->_ventEvents['write'][$name]);
            for ($x = 0; $x < $eventSize; $x++)
            {
                // Short circuit for retained read responses
                if ($this->_ventEvents['write'][$name][$x]['retain'] && isset($this->_ventEvents['write'][$name][$x]['response']))
                {
                    return $this->_ventEvents['write'][$name][$x]['response'];
                }

                // replace reserved keywords
                $this->_ventEvents['write'][$name][$x]['params'] = call_user_func(
                    $this->_ventFunctions['replaceReservedParams'],
                    $this->_ventEvents['write'][$name][$x]['params'],
                    $name,
                    $value
                );

                // execute the action
                $response = call_user_func_array(
                    $this->_ventEvents['write'][$name][$x]['callable'],
                    $this->_ventEvents['write'][$name][$x]['params']
                );

                if ($this->_ventEvents['write'][$name][$x]['retain'] && is_null($response))
                {
                    throw new \Exception('Event registered on write of variable "' . $name . '" does not return a retainable response - cannot be null');
                }

                // If we have a response, set and return it
                if (isset($response))
                {
                    if ($this->_ventEvents['write'][$name][$x]['retain'])
                    {
                        $this->_ventEvents['write'][$name][$x]['response'] = $response;
                    }
                    return $this->_ventVariables[$name] = $response;
                }

            }
        }

        return $this->_ventVariables[$name] = $value;
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
                $this->_ventEvents['read'][$name][$x]['params'] = call_user_func(
                    $this->_ventFunctions['replaceReservedParams'],
                    $this->_ventEvents['read'][$name][$x]['params'],
                    $name
                );

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


    public function __unset($name)
    {
        if (array_key_exists($name, $this->_ventEvents['delete']))
        {
            $eventSize = sizeof($this->_ventEvents['delete'][$name]);
            for ($x = 0; $x < $eventSize; $x++)
            {
                // replace reserved keyword
                $this->_ventEvents['delete'][$name][$x]['params'] = call_user_func(
                    $this->_ventFunctions['replaceReservedParams'],
                    $this->_ventEvents['delete'][$name][$x]['params'],
                    $name
                );

                // execute the action
                call_user_func_array(
                    $this->_ventEvents['delete'][$name][$x]['callable'],
                    $this->_ventEvents['delete'][$name][$x]['params']
                );
            }
        }

        unset($this->_ventVariables[$name]);
    }
}
