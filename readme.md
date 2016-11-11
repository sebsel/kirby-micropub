# Kirby Micropub Plugin

This Kirby plugin provides an easy way to add a  [Micropub endpoint](http://indieweb.org/Micropub) to your site. [Download the plugin](https://github.com/sebsel/kirby-micropub/archive/master.zip) from Github and put it into /site/plugins. It will automatically be loaded by Kirby.

## Configuration

By default, it adds new pages with the 'article' template as subpages of 'blog', so it's compatible with the new Starterkit. You can change this behavior by setting the `micropub.page-creator` option as a function that takes $uid, $template and $data as parameters and returns the new page. Here is an example with the default setting:

```
c::set('micropub.page-creator', function($uid, $template, $data) {
  return page('blog')->children()->create($uid, 'article', $data);
});
```

You also need to set your IndieAuth Authorization and Token endpoints. The Authorization Endpoint is consumed by the user and should therefore go as a `<link>`-tag in your header:

```
<link rel="authorization_endpoint" href="https://indieauth.com/auth">
<link rel="token_endpoint" href="https://tokens.indieauth.com/token">
<link rel="micropub" href="https://your.url/micropub">
```

The Endpoint makes a call to the Token Endpoint and should therefore know it's whereabouts. By default it's pointed to Indieauth.com, but you can set it with the `micropub.token-endpoint` option.

```
c::set('micropub.token-endpoint', 'https://tokens.indieauth.com/token')
```

Last but not least: it makes sense to markup your blog with Microformats when using Micropub. More information can be found on [Microformats.org](http://microformats.org). Also, a Starterkit compatible template for 'article' is provided in `site/plugins/micropub/templates/`. When you delete your existing `site/templates/article.php`, it's loaded automatically.

## Author

Sebastiaan Andeweg
https://seblog.nl