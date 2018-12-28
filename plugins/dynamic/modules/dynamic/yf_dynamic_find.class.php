<?php


class yf_dynamic_find
{
    public function __construct()
    {
        $this->_parent = module('dynamic');
    }

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $arguments
     */
    public function __call($name, $arguments)
    {
        trigger_error(__CLASS__ . ': No method ' . $name, E_USER_WARNING);
        return false;
    }

    /**
     * find users over nick or email.
     */
    public function find_users()
    {
        no_graphics(true);
        if ( ! $_POST || ! main()->USER_ID || IS_ADMIN != 1) {
            echo '';
        }
        // Continue execution
        $Q = db()->query(
            'SELECT id, nick 
			FROM ' . db('user') . ' 
			WHERE ' . _es($_POST['search_field']) . " LIKE '" . _es($_POST['param']) . "%' 
			LIMIT " . (int) ($this->_parent->USER_RESULTS_LIMIT)
        );
        while ($A = db()->fetch_assoc($Q)) {
            $finded_users[$A['id']] = $A['nick'];
        }
        echo $finded_users ? json_encode($finded_users) : '*';
    }

    /**
     * find users over nick or email.
     */
    public function find_ids()
    {
        no_graphics(true);
        if ( ! $_POST || ! main()->USER_ID || IS_ADMIN != 1/* || !strlen($_POST['param'])*/) {
            echo '';
            exit;
        }
        // Continue execution
        if ($_POST['search_table'] == 'user') {
            // Find account ids of this user
            $Q = db()->query(
                "SELECT a.id
						, a.account_name
						, a.user_id
						, u.nick
						, u.id AS 'uid' 
				FROM " . db('host_accounts') . ' AS a, ' . db('user') . ' AS u 
				WHERE a.user_id=u.id 
					AND u.id IN( 
						SELECT id 
						FROM ' . db('user') . ' 
						WHERE ' . _es($_POST['search_field']) . " LIKE '" . _es($_POST['param']) . "%'
					) 
				LIMIT " . (int) ($this->_parent->USER_RESULTS_LIMIT)
            );
            while ($A = db()->fetch_assoc($Q)) {
                $finded_ids[$A['nick']][$A['id']] = $A['account_name'];
            }
        } elseif ($_POST['search_table'] == 'host_accounts') {
            $Q = db()->query(
                "SELECT a.id
						, a.account_name
						, a.user_id
						, u.nick
						, u.id AS 'uid' 
				FROM " . db('host_accounts') . ' AS a
					, ' . db('user') . ' AS u 
				WHERE a.' . _es($_POST['search_field']) . " LIKE '" . _es($_POST['param']) . "%' 
					AND a.user_id=u.id 
				LIMIT " . (int) ($this->_parent->USER_RESULTS_LIMIT)
            );
            while ($A = db()->fetch_assoc($Q)) {
                $finded_ids[$A['nick']][$A['id']] = $A['account_name'];
            }
        }
        echo $finded_ids ? json_encode($finded_ids) : '*';
    }
}
