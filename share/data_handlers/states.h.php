<?php

return array(' ' => 'Non US') + db()->get_2d('SELECT code, name FROM '.db('states'));
