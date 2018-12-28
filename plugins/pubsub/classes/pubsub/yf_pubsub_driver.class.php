<?php

/**
 * YF PUB/SUB driver abstract.
 */
abstract class yf_pubsub_driver
{
    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }
    abstract protected function connect($params);
    abstract protected function conf($params);
    abstract protected function is_ready();
    abstract protected function pub($channel, $what);
    abstract protected function sub($channels, $callback);
}
