<?php

function expose_endpoints() {
  echo html::tag('link', null, [
    'rel'  => 'authorization_endpoint',
    'href' => c::get('micropub.authorization-endpoint',
      'https://indieauth.com/auth')
  ]);

  echo html::tag('link', null, [
    'rel'  => 'token_endpoint',
    'href' => c::get('micropub.token-endpoint',
      'https://tokens.indieauth.com/token')
  ]);

  echo html::tag('link', null, [
    'rel'  => 'micropub',
    'href' => url::base().'/micropub'
  ]);
}
