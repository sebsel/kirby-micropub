<?php

load(array(
  'sebsel\\micropub\\endpoint'    => __DIR__ . DS . 'lib' . DS . 'endpoint.php',
  'sebsel\\indieauth'             => __DIR__ . DS . 'lib' . DS . 'indieauth.php'
));

require(__DIR__ . DS . 'helpers.php');

new Sebsel\Micropub\Endpoint;