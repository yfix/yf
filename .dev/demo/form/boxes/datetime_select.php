<?php

return function () {
    return form()
        ->datetime_select('add_date')
        ->datetime_select('add_date__and');
};
