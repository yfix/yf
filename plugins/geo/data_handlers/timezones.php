<?php

return (array) from('timezones')->where('active', '1')->order_by('seconds ASC', 'name ASC')->all();
