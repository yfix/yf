<?php

class model_test1
{
    public static function __callStatic($name, $args)
    {
        echo 'Hello from ' . __CLASS__ . '::' . __FUNCTION__ . PHP_EOL;
    }
    public function __call($name, $args)
    {
        echo 'Hello from ' . __CLASS__ . '->' . __FUNCTION__ . PHP_EOL;
    }
}

spl_autoload_register(function ($class) {
    if (class_exists($class)) {
        return true;
    }
    $try_model = 'model_' . $class;
    if (class_exists($try_model)) {
        eval('class ' . $class . ' extends ' . $try_model . '{};');
        return true;
    }
});

// Existing class call
$ts = microtime();

model_test1::hello();
$model_test1 = new model_test1();
$model_test1->hello();

echo round(microtime() - $ts, 5) . PHP_EOL;

// Virtual class call
$ts = microtime();

test1::hello();
$test1 = new test1();
$test1->hello();

echo round(microtime() - $ts, 5) . PHP_EOL;
