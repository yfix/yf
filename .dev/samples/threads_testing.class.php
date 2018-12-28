<?php

class test2
{
    public function show()
    {
        $threads = _class('threads');
        for ($i = 0; $i < 10; $i++) {
            $threads->new_framework_thread($_GET['object'], 'console', ['id' => $i]);
        }
        $results = [];
        while (false !== ($result = $threads->iteration())) {
            if ( ! empty($result)) {
                $results[$result[0]] = $result[1];
            }
        }
        /*
                // Short variant
                for ($i = 0; $i < 10; $i++) {
                    $threads[] = array('id' => $i);
                }
                $results = common()->threaded_exec($_GET['object'], 'console', $threads);
        */
        return print_r($results, 1);
    }

    public function console()
    {
        $GLOBALS['no_graphics'] = true;
        session_write_close();
        if ( ! main()->CONSOLE_MODE) {
            exit('No direct access to method allowed');
        }
        sleep(3);

        $params = common()->get_console_params();
        echo $params['id'];

        exit();
    }
}
