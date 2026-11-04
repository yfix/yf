<?php

load('db_installer', '', 'classes/db/');
class yf_db_installer_pgsql extends yf_db_installer
{
    // TODO: PostgreSQL schema management is not implemented yet.

    protected function repair($sql, $db_error, $db)
    {
        trigger_error('DB_INSTALLER: pgsql repair() is not implemented', E_USER_WARNING);
        return false;
    }

    protected function do_create_table($full_table_name, array $sql_php, $db)
    {
        trigger_error('DB_INSTALLER: pgsql do_create_table() is not implemented', E_USER_WARNING);
        return false;
    }

    protected function do_alter_table($table_name, $column_name, array $sql_php, $db)
    {
        trigger_error('DB_INSTALLER: pgsql do_alter_table() is not implemented', E_USER_WARNING);
        return false;
    }
}
