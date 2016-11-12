# Kirby Micropub Plugin

**This plugin is not finished nor even tested and probably doesn't work**

This Kirby plugin provides an easy way to add a  [Micropub endpoint](http://indieweb.org/Micropub) to your site. [Download the plugin](https://github.com/sebsel/kirby-micropub/archive/master.zip) from Github and put it into /site/plugins. It will automatically be loaded by Kirby.

## Installation

**Warning: not finished yet! Don't use!**

1. [Download the plugin](https://github.com/sebsel/kirby-micropub/archive/master.zip) and put it under `/site/plugins/micropub`. Make sure the folder is called `micropub` and that it has `micropub.php` and all the other files in it.

2. Add the `expose_endpoints()` helper to your `snippets/header.php`, or where-ever you have your `<head>`-tag.

```php
<meta name="description" content="<?= $site->description()->html() ?>">

<?php expose_endpoints() ?>

<?= css('assets/css/index.css') ?>
```

3. Find a [Micropub client](https://indieweb.org/Micropub/Clients) and enter your homepage url. Make sure you have [IndieAuth](https://indieauth.com/setup) set up. Sign in and post!

This plugin should work out of the box with Kirby 2.4's New Starterkit, but there are plenty of options for other sites:

## Configuration

By default, it adds new pages with the 'article' template as subpages of 'blog', so it's compatible with the new Starterkit. You can change this behavior by setting the `micropub.page-creator` option as a function that takes $uid, $template and $data as parameters and returns the new page. Here is an example with the default setting:

```
c::set('micropub.page-creator', function($uid, $template, $data) {
  return page('blog')->children()->create($uid, 'article', $data);
});
```

By default, the Micropub Endpoint uses IndieAuth.com as IndieAuth-provider. You can change this by putting the following in your `config.php`:

```
c::set('micropub.authorization-endpoint', 'https://indieauth.com/auth');
c::set('micropub.token-endpoint', 'https://tokens.indieauth.com/token');
```

Last but not least: it makes sense to markup your blog with Microformats when using Micropub. More information can be found on [Microformats.org](http://microformats.org). Also, a Starterkit compatible template for 'article' is provided in `site/plugins/micropub/templates/`. When you delete your existing `site/templates/article.php`, it's loaded automatically.

## Author

Based on [Bastian Allgeiers webmentions-plugin](https://github.com/bastianallgeier/kirby-webmentions)

Sebastiaan Andeweg
https://seblog.nl