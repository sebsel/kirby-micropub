# Kirby Micropub Plugin

This Kirby plugin provides an easy way to add a  [Micropub endpoint](http://indieweb.org/Micropub) to your site. [Download the plugin](https://github.com/sebsel/kirby-micropub/archive/master.zip) from Github and put it into /site/plugins. It will automatically be loaded by Kirby.

The plugin is not completely finished. What should work:
- posting with FormEncoded
- posting with JSON
- Multipart photo upload
- Media-Endpoint photo upload

To do:
- Update post via Micropub
- Delete post
- Syndication
- Source Query

## Installation

1. [Download the plugin](https://github.com/sebsel/kirby-micropub/archive/master.zip) and put it under `/site/plugins/micropub`. Make sure the folder is called `micropub` and that it has `micropub.php` and all the other files in it.

2. Add the `expose_endpoints()` helper to your `snippets/header.php`, or where-ever you have your `<head>`-tag.

  ```php
  <meta name="description" content="<?= $site->description()->html() ?>">

  <?php expose_endpoints() ?>

  <?= css('assets/css/index.css') ?>
  ```

3. Find a [Micropub client](https://indieweb.org/Micropub/Clients) and enter your homepage url. Make sure you have [IndieAuth](https://indieauth.com/setup) set up. Sign in and post!

Last but not least: it makes sense to markup your blog with Microformats when using Micropub. More information can be found on [Microformats.org](http://microformats.org).

This plugin should work out of the box with Kirby 2.4's New Starterkit, but there are plenty of options for other sites:

## Configuration

The following options can be set with `c::set()`:

### `micropub.page-creator` - Creates the page

This option takes a function which takes the proposed $uid, $template and $data and returns a Page object of the new page.

The $data variable contains an array with fields ready to save in Kirby. All the external urls and images are downloaded to your own server. The names of the fields come from the Micropub-request, with only 'content' renamed to 'text', because Kirby uses `$page->content()` for itself. But you can rename any of the fields yourself with this function.

If you don't set this option, the plugin tries to be compatible with the new Starterkit. So, it adds new pages with the 'article' template as subpages of 'blog'. It also changes Micropub's 'name' to 'title' and 'published' to 'date'. Here's the default function:

```php
c::set('micropub.page-creator', function($uid, $template, $data) {
  // Rename fields (you can also rename Kirby's fields)
  if (isset($data['name'])) $data['title'] = $data['name'];
  $data['date'] = $data['published'];

  // No double fields
  unset($data['name'], $data['published']);

  // Add new entry to the blog
  $newEntry = page('blog')->children()->create($uid, 'article', $data);

  // Make it visible
  $newEntry->sort(date('Ymd', strtotime($data['date'])));

  // Return the new entry
  return $newEntry;
});
```

Please note that fields like 'photo', 'in-reply-to' and 'like-of' are not displayed in your template by default. You can just call `$page->likeOf()` in your template files.

### `micropub.authorization-endpoint` and `micropub.token-endpoint`

By default, the Micropub Endpoint uses IndieAuth.com as IndieAuth-provider. You can change this by putting the following in your `config.php`:

```php
c::set('micropub.authorization-endpoint', 'https://indieauth.com/auth');
c::set('micropub.token-endpoint', 'https://tokens.indieauth.com/token');
```

### `micropub.media-endpoint-path`

This is the folder where your Media Endpoint stores it's files. It's only temporary, so it should not matter much, but you can set it. By default it's:

```php
c::set('micropub.media-endpoint-path', 'temp/media-endpoint');
```

## Author

Based on [Bastian Allgeiers webmentions-plugin](https://github.com/bastianallgeier/kirby-webmentions)

Sebastiaan Andeweg
https://seblog.nl
