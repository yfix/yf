<?php

return function() {
	$FORUM_OBJ = module('forum');
	_class('forum_sync', FORUM_MODULES_DIR)->_sync_board();
	return 'Forum module: Statistics rebuilt';
};
