<?php

return (array)db()->get_all('SELECT * FROM '.db('category_items').' ORDER BY `order` ASC');
