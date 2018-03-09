<?php

return function() {
	// Update days campaigns
	db()->query('UPDATE '.db('adv_orders').' SET amount_left = amount_left - 1 WHERE type="days" AND status=1');
	// Turn off expired campaigns
	db()->query('UPDATE '.db('adv_orders').' SET status=2 WHERE amount_left <= 0 AND status=1');
	return 'Advert module: Turn off expired or old orders, update amount_left for "days" pay_type';
};
