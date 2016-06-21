<?php
return [
  1 =>
  [
    'id' => '1',
    'name' => 'Administrator',
    'active' => '1',
    'go_after_login' => './?object=admin_home',
  ],
  2 =>
  [
    'id' => '2',
    'name' => 'Suppliers',
    'active' => '0',
    'go_after_login' => './?object=manage_shop&action=products',
  ],
  3 =>
  [
    'id' => '3',
    'name' => 'Content-manager',
    'active' => '0',
    'go_after_login' => './?object=manage_shop&action=products',
  ],
  4 =>
  [
    'id' => '4',
    'name' => 'Orders-operator',
    'active' => '0',
    'go_after_login' => './?object=manage_shop&action=orders',
  ],
];
