<?php

return function() {
	db()->query('DELETE FROM '.db('forum_sessions').' WHERE last_update < '.(time() - module('forum')->SETTINGS['SESSION_EXPIRE_TIME']));
	return 'Forum module: Old sessions, validations deleted';
};
