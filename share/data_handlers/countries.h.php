<?php

// TODO: maybe remove empty item from here?
$data = array(' ' => ' ') + db()->get_2d('SELECT c, n FROM '.db('countries').' ORDER BY n');
