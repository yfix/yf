<?php

return (array)db()->get_2d('SELECT name, id FROM '.db('dynamic_fields_categories'));
