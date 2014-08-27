<?php

require __DIR__.'/db_setup.php';
require __DIR__.'/db_funcs.php';

require __DIR__.'/yf_unit_tests_setup.php';

######################
echo 'db():'.PHP_EOL.			print_r( db()->get_one('SELECT COUNT(*) AS num from '.db('user').' ') , 1);
echo 'db_t2():'.PHP_EOL.		print_r( db_t2()->get_one('SELECT COUNT(*) AS num from '.db_t2('user').' ') , 1);
echo 'db_t3():'.PHP_EOL.		print_r( db_t3()->get_one('SELECT COUNT(*) AS num from '.db_t3('user').' ') , 1);
echo 'db_rr():'.PHP_EOL.		print_r( db_rr()->get_one('SELECT COUNT(*) AS num from '.db_rr('user').' ') , 1);
echo 'db_cr():'.PHP_EOL.		print_r( db_cr()->get_one('SELECT COUNT(*) AS num from '.db_cr('user').' ') , 1);
echo 'db_m3():'.PHP_EOL.		print_r( db_m3()->get_one('SELECT COUNT(*) AS num from '.db_m3('user').' ') , 1);
echo 'db_master():'.PHP_EOL.	print_r( db_master()->get_one('SELECT COUNT(*) AS num from '.db_master('user').' ') , 1);
echo 'db_slave():'.PHP_EOL.	print_r( db_slave()->get_one('SELECT COUNT(*) AS num from '.db_slave('user').' ') , 1);
