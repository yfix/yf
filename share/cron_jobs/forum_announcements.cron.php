<?php

return function() {
	$FORUM_OBJ = module('forum');
	_class('forum_announce', FORUM_MODULES_DIR)->_retire_expired();
	return 'Forum module:Announcements updated';
};
