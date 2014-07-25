<?php

return function() {
	db()->query('DELETE FROM '.db('search_keywords').' WHERE hits <= 2');
	db()->query('OPTIMIZE TABLE '.db('search_keywords').'');
	return 'SE keywords cleaned up';
};
