<?php

require dirname(__FILE__).'/db_setup.php';
require dirname(__FILE__).'/db_funcs.php';

define("YF_PATH", dirname(dirname(dirname(dirname(__FILE__))))."/");
require YF_PATH."classes/yf_main.class.php";
new yf_main("user", 1, 0);

######################
echo "db():\n".			print_r( db()->get_one('SELECT COUNT(*) AS num from '.db('user').' ') , 1);
echo "db_t2():\n".		print_r( db_t2()->get_one('SELECT COUNT(*) AS num from '.db_t2('user').' ') , 1);
echo "db_t3():\n".		print_r( db_t3()->get_one('SELECT COUNT(*) AS num from '.db_t3('user').' ') , 1);
echo "db_rr():\n".		print_r( db_rr()->get_one('SELECT COUNT(*) AS num from '.db_rr('user').' ') , 1);
echo "db_cr():\n".		print_r( db_cr()->get_one('SELECT COUNT(*) AS num from '.db_cr('user').' ') , 1);
echo "db_m3():\n".		print_r( db_m3()->get_one('SELECT COUNT(*) AS num from '.db_m3('user').' ') , 1);
echo "db_master():\n".	print_r( db_master()->get_one('SELECT COUNT(*) AS num from '.db_master('user').' ') , 1);
echo "db_slave():\n".	print_r( db_slave()->get_one('SELECT COUNT(*) AS num from '.db_slave('user').' ') , 1);
