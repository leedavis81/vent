<?php

namespace League\Vent;

use League\Event\AbstractEvent;
use League\Event\CallbackListener as LeagueCallbackListener;

class CallbackListener extends LeagueCallbackListener
{
    /**
     * Parameters to be injected into callable
     * @var array $params
     */
    protected $params;

    /**
     * Whether we should retain the response for future calls
     * @var boolean retainResponse
     */
    protected $retainResponse;

    /**
     * A retained response used for short-circuiting calls
     * @var mixed $retained
     */
    protected $retained;


    /**
     * Create a new callback listener instance.
     * @param callable $callback
     * @param array $params
     * @param bool $retainResponse
     */
    public function __construct(callable $callback, array $params = null, $retainResponse = false)
    {
        $this->callback = $callback;
        $this->setParams((array)$params);
        $this->retainResponse = $retainResponse;
    }


    /**
     * Handle an event.
     *
     * @param AbstractEvent $event
     * @return mixed
     */
    public function handle(AbstractEvent $event)
    {
        if ($this->hasRetainedResponse()) {
            return $this->getRetainedResponse();
        }

        $response = call_user_func_array($this->getCallback(), $this->getParams());
        if ($response !== null) {
            if ($this->retainResponse) {
                $this->setRetainedResponse($response);
            }
        }
        return $response;
    }

    /**
     * Set the parameters to be injected into callable
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Get the parameters
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return boolean
     */
    public function retainResponse()
    {
        return $this->retainResponse;
    }

    /**
     * Set a response that is to be retained
     * @param mixed $retained
     */
    public function setRetainedResponse($retained)
    {
        $this->retained = $retained;
    }

    /**
     * @return mixed
     */
    public function getRetainedResponse()
    {
        return $this->retained;
    }

    /**
     * Has this callback got a retained response
     * @return bool
     */
    public function hasRetainedResponse()
    {
        return isset($this->retained);
    }

}