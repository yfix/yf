<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('ensure that html5 bootstrap3 works');
$I->amOnPage('/test/html5fw_bs3');
$I->see('text');
