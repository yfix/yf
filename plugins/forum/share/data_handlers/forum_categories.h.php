<?php

return (array)db()->get_all('SELECT * FROM '.db('forum_categories').' ORDER BY `order` ASC');
