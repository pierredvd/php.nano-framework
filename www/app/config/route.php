<?php
    return array(
        '/'                                    => 'index/index',
        '/users/([0-9]+)'                      => 'index/user',
        '/403'                                 => 'index/e403',
        '/404'                                 => 'index/e404',
        '__403__'                              => 'index/e403',
        '__404__'                              => 'index/e404'
    );