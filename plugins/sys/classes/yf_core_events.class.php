<?php

/**
 * Core events/observer handler.
 *
 * Examples of usage:
 *	_class('core_events')->listen('class.*',function() { return 'event #0 wildcarded'; });
 *	_class('core_events')->listen('class.action',function($args) { return 'event # 1'.$args; },1);
 *	_class('core_events')->listen('class.action',function() { return 'event # 2'; },2);
 *	_class('core_events')->listen('class.action',function() { return 1; });
 *	_class('core_events')->listen('class.*',function() { return 2; },5);
 *	_class('core_events')->listen('class.action2',function() { return 1; });
 *	_class('core_events')->listen('class2.action',function() { return 1; });
 *	$r = "<pre>".print_r(_class('core_events')->fire('class.action','test'),1)."</pre>";
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_core_events
{
    protected $listeners = [];
    protected $wildcards = [];
    protected $sorted = [];
    protected $firing = [];

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array  $event
     * @param  mixed   $listener
     * @param  int     $priority
     * @param mixed $events
     */
    public function listen($events, $listener, $priority = 0)
    {
        if (DEBUG_MODE) {
            debug('events_' . __FUNCTION__ . '[]', [
                'name' => $events,
                'listener' => is_callable($listener) ? 'Closure' : $listener,
                'priority' => $priority,
                'time_offset' => microtime(true),
                'trace' => trace(),
            ]);
        }
        foreach ((array) $events as $event) {
            if ($this->_str_contains($event, '*')) {
                return $this->_setup_wildcard_listen($event, $listener);
            }
            $this->listeners[$event][$priority][] = $listener;
            unset($this->sorted[$event]);
        }
    }


    /**
     * Register a queued event and payload.
     *
     * @param  string  $event
     * @param  array   $payload
     */
    public function queue($event, $payload = [])
    {
        if (DEBUG_MODE) {
            debug('events_' . __FUNCTION__ . '[]', [
                'name' => $event,
                'payload_len' => count((array) $payload) . ' items',
                'time_offset' => microtime(true),
                'trace' => trace(),
            ]);
        }
        $this->listen($event . '_queue', function () use ($event, $payload) {
            $this->fire($event, $payload);
        });
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param  string  $event
     */
    public function forget($event)
    {
        unset($this->listeners[$event]);
        unset($this->sorted[$event]);
    }

    /**
     * Fire an event and call the listeners.
     *
     * @param  string  $event
     * @param  mixed   $payload
     * @param  bool    $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        if (DEBUG_MODE) {
            debug('events_' . __FUNCTION__ . '[]', [
                'name' => $event,
                'payload_len' => count((array) $payload) . ' items',
                'halt' => $halt,
                'time_offset' => microtime(true),
                'trace' => trace(),
            ]);
        }
        $responses = [];
        // If an array is not given to us as the payload, we will turn it into one so
        // we can easily use call_user_func_array on the listeners, passing in the
        // payload to each of them so that they receive each of these arguments.
        if ( ! is_array($payload)) {
            $payload = [$payload];
        }
        $this->firing[] = $event;
        foreach ($this->get_listeners($event) as $listener) {
            $response = call_user_func_array($listener, $payload);
            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if ($response !== null && $halt) {
                array_pop($this->firing);
                return $response;
            }
            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if ($response === false) {
                break;
            }
            $responses[] = $response;
        }
        array_pop($this->firing);
        return $halt ? null : $responses;
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param  string  $eventName
     * @param mixed $event_name
     * @return bool
     */
    public function has_listeners($event_name)
    {
        return isset($this->listeners[$event_name]);
    }

    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param  string  $event
     * @param  array   $payload
     * @return mixed
     */
    public function until($event, $payload = [])
    {
        return $this->fire($event, $payload, true);
    }

    /**
     * Flush a set of queued events.
     *
     * @param  string  $event
     */
    public function flush($event)
    {
        $this->fire($event . '_queue');
    }

    /**
     * Get the event that is currently firing.
     *
     * @return string
     */
    public function firing()
    {
        return last($this->firing);
    }

    /**
     * Get all of the listeners for a given event name.
     *
     * @param  string  $eventName
     * @param mixed $event_name
     * @return array
     */
    public function get_listeners($event_name)
    {
        $wildcards = $this->_get_wildcard_listeners($event_name);
        if ( ! isset($this->sorted[$event_name])) {
            $this->_sort_listeners($event_name);
        }
        return array_merge($this->sorted[$event_name], $wildcards);
    }

    /**
     * Setup a wildcard listener callback.
     *
     * @param  string  $event
     * @param  mixed   $listener
     */
    protected function _setup_wildcard_listen($event, $listener)
    {
        $this->wildcards[$event][] = $listener;
    }

    /**
     * Get the wildcard listeners for the event.
     *
     * @param  string  $eventName
     * @param mixed $event_name
     * @return array
     */
    protected function _get_wildcard_listeners($event_name)
    {
        $wildcards = [];
        foreach ($this->wildcards as $key => $listeners) {
            if ($this->_str_is($key, $event_name)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }
        return $wildcards;
    }

    /**
     * Sort the listeners for a given event by priority.
     *
     * @param  string  $eventName
     * @return array
     */
    protected function _sort_listeners($eventName)
    {
        $this->sorted[$eventName] = [];
        // If listeners exist for the given event, we will sort them by the priority
        // so that we can call them in the correct order. We will cache off these
        // sorted event listeners so we do not have to re-sort on every events.
        if (isset($this->listeners[$eventName])) {
            krsort($this->listeners[$eventName]);
            $this->sorted[$eventName] = call_user_func_array('array_merge', $this->listeners[$eventName]);
        }
    }

    /**
     * @param mixed $haystack
     * @param mixed $needles
     */
    protected function _str_contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $pattern
     * @param mixed $value
     */
    protected function _str_is($pattern, $value)
    {
        if ($pattern == $value) {
            return true;
        }
        $pattern = preg_quote($pattern, '#');
        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern) . '\z';
        return (bool) preg_match('#^' . $pattern . '#', $value);
    }
}
