<?php

return (array)db()->get_2d('SELECT id, weight FROM '.db('weights'));
