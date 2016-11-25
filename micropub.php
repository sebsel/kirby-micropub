<?php

load(array(
  'indieweb\\micropub\\endpoint'    => __DIR__ . DS . 'lib' . DS . 'endpoint.php',
  'indieweb\\indieauth'             => __DIR__ . DS . 'lib' . DS . 'indieauth.php'
));

require(__DIR__ . DS . 'helpers.php');

new IndieWeb\Micropub\Endpoint;