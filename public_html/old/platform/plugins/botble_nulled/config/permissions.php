<?php

return [
    [
        'name' => 'Botble  nulleds',
        'flag' => 'botble_nulled.index',
    ],
    [
        'name' => 'Create',
        'flag' => 'botble_nulled.create',
        'parent_flag' => 'botble_nulled.index',
    ],
    [
        'name' => 'Edit',
        'flag' => 'botble_nulled.edit',
        'parent_flag' => 'botble_nulled.index',
    ],
    [
        'name' => 'Delete',
        'flag' => 'botble_nulled.destroy',
        'parent_flag' => 'botble_nulled.index',
    ],
];
