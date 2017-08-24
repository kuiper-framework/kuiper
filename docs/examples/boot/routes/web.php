<?php

$app->group(['namespace' => 'app\\controllers'], function ($app) {
    $app->get('/hello/{name}', 'IndexController:hello');
});
