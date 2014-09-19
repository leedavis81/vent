<?php
namespace League\Vent;

use League\Event\Emitter as EventEmitter;
use League\Event\Event;

trait VentTrait
{
    /**
     * A proxy of variables
     * @var array $_ventVariables
     */
    private $_ventVariables;

    /**
     * Event Emitter
     * @var EventEmitter $_ventEventEmitter
     */
    private $_ventEventEmitter;

    /**
     * Reserved functions, registered as closures to avoid class littering
     * @var array $_ventFunctions
     */
    private $_ventFunctions;

    /**
     * Register an event to be triggered
     * @param string $event - the name of the event - can be 'read', 'write' or an array
     * @param string|array $variables - the name(s) of the variables(s) to trigger the event on
     * @param \Closure $callable - The callable to be triggered
     * @param array|null $params - an array of parameters to be passed into the action
     * @param bool $retainResponse
     */
    private function registerEvent($event, $variables, \Closure $callable, array $params = null, $retainResponse = false)
    {
        // this should really be move into some form of init(), can we steal the __construct?
        if (!$this->_ventEventEmitter instanceof EventEmitter)
        {
            $this->_ventEventEmitter = new EventEmitter();

            // All functions are registered to this array save littering object internals
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

                $callbackListener = new CallbackListener($callable, $params, $retainResponse);
                if ($event === 'read' || $event === 'get')
                {
                    $this->_ventEventEmitter->addListener('read.' . $var, $callbackListener);
                } elseif ($event === 'write' || $event === 'set')
                {
                    $this->_ventEventEmitter->addListener('write.' . $var, $callbackListener);
                } elseif ($event === 'delete' || $event === 'unset')
                {
                    $this->_ventEventEmitter->addListener('delete.' . $var, $callbackListener);
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
        $event = new Event('write.'.$name);
        if (isset($this->_ventEventEmitter) && $this->_ventEventEmitter->hasListeners($event->getName()))
        {
            foreach ($this->_ventEventEmitter->getListeners($event->getName()) as $listener)
            {
                /** @var CallbackListener $listener */
                // replace reserved keywords
                $listener->setParams(call_user_func(
                    $this->_ventFunctions['replaceReservedParams'],
                    $listener->getParams(),
                    $name,
                    $value
                ));

                if (($response = $listener->handle($event)) !== null)
                {
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
        $event = new Event('read.'.$name);
        if (isset($this->_ventEventEmitter) && $this->_ventEventEmitter->hasListeners($event->getName()))
        {
            foreach ($this->_ventEventEmitter->getListeners($event->getName()) as $listener)
            {
                /**
                 * @var CallbackListener $listener
                 */
                // replace reserved keywords
                $listener->setParams(call_user_func(
                    $this->_ventFunctions['replaceReservedParams'],
                    $listener->getParams(),
                    $name
                ));

                if (($response = $listener->handle($event)) !== null)
                {
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


    /**
     * Trigger delete events and unset a stored parameter
     * @param $name
     * @return mixed
     */
    public function __unset($name)
    {
        $event = new Event('delete.'.$name);
        if (isset($this->_ventEventEmitter) && $this->_ventEventEmitter->hasListeners($event->getName()))
        {
            foreach ($this->_ventEventEmitter->getListeners($event->getName()) as $listener)
            {
                /**
                 * @var CallbackListener $listener
                 */
                // replace reserved keywords
                $listener->setParams(call_user_func(
                    $this->_ventFunctions['replaceReservedParams'],
                    $listener->getParams(),
                    $name
                ));

                if (($response = $listener->handle($event)) !== null)
                {
                    return $response;
                }
            }
        }
        unset($this->_ventVariables[$name]);
        return null;
    }
}