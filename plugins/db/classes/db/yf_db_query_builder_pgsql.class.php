<?php

/**
 * Query builder (Active Record) for pgsql.
 */
load('db_query_builder_driver', '', 'classes/db/');
class yf_db_query_builder_pgsql extends yf_db_query_builder_driver
{
    // TODO

    /**
     * @param mixed $table
     */
    public function get_key_name($table = '')
    {
        return 'id';
    }
}
