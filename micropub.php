<?php

load(array(
  'sebsel\\micropub\\endpoint'    => __DIR__ . DS . 'lib' . DS . 'endpoint.php',
  'sebsel\\micropub\\interaction' => __DIR__ . DS . 'lib' . DS . 'interaction.php',
  'sebsel\\micropub\\author'      => __DIR__ . DS . 'lib' . DS . 'author.php',
  'sebsel\\indieauth'             => __DIR__ . DS . 'lib' . DS . 'indieauth.php'
));

require(__DIR__ . DS . 'helpers.php');

new Sebsel\Micropub\Endpoint;