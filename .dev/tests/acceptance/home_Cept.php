<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('ensure that frontpage works');
$I->amOnPage('/');
$I->see('Home');

$I->wantTo('ensure that html5 bootstrap2 works');
$I->amOnPage('/test/html5fw_bs2');
$I->see('text');

$I->wantTo('ensure that html5 bootstrap3 works');
$I->amOnPage('/test/html5fw_bs3');
$I->see('text');
