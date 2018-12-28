<?php

/**
 * YF Search driver abstract.
 */
abstract class yf_search_driver
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
    abstract protected function search(array $params = [], &$error_message = '');
}
