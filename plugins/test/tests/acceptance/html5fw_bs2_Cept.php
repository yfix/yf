<?php

$I = new AcceptanceTester($scenario);

$I->wantTo('ensure that html5 bootstrap2 works');
$I->amOnPage('/test/html5fw_bs2');
$I->see('text');
