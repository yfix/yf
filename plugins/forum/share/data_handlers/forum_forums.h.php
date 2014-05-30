<?php

return (array)db()->get_all('SELECT * FROM '.db('forum_forums').' ORDER BY category ASC, `order` ASC');
