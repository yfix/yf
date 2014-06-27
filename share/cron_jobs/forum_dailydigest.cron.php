<?php

return function() {
	$FORUM_OBJ = module('forum');
	_class('forum_tracker', FORUM_MODULES_DIR)->_send_digest('daily', 'topic');
	_class('forum_tracker', FORUM_MODULES_DIR)->_send_digest('daily', 'forum');
	return 'Forum module: Daily Topic & Forum Digest Sent';
};
