<?php

namespace IndieWeb\Micropub;

use Indieweb\IndieAuth;
use C;
use Error;
use F;
use Files;
use Media;
use Obj;
use R;
use Remote;
use Response;
use Str;
use Tpl;
use Upload;
use Url;
use V;
use Yaml;

class Endpoint {

  const ERROR_FORBIDDEN          = 0;
  const ERROR_INSUFFICIENT_SCOPE = 1;
  const ERROR_INVALID_REQUEST    = 2;

  public $mediaPath;
  public $mediaUrl;
  public $config;

  public function __construct() {

    $path = c::get('micropub.media-endpoint-path', 'temp/media-endpoint');

    $this->mediaPath = kirby()->roots()->index() . DS . str_replace('/', DS, $path);
    $this->mediaUrl = kirby()->urls()->index() . '/' . $path;

    // Set the config to be returned by GET ?q=config
    $this->config = [
      'media-endpoint' => url::base() . '/micropub-media-endpoint'
    ];

    $endpoint = $this;

    kirby()->routes([
      [
        'pattern' => 'micropub',
        'method'  => 'POST',
        'action'  => function() use($endpoint) {

          try {
            $endpoint->start();
            echo response::success('Yay, new post created', [], 201);

          } catch (Exception $e) {
            echo $endpoint->respondWithError($e);
          }
        }
      ],
      [
        'pattern' => 'micropub',
        'method'  => 'GET',
        'action'  => function() use($endpoint) {

          // Publish information about the endpoint
          if (get('q') == 'config') {
            echo response::json($endpoint->config);
            exit();
          }

          // Only the syndication targets
          if (get('q') == 'syndicate-to') {
            if (isset($endpoint->config['syndicate-to']))
              echo response::json($endpoint->config['syndicate-to']);
            else
              echo response::json([]);
            exit();
          }

          // No? Return to Kirby.
          return site()->visit('micropub');
        }
      ],
      [
        'pattern' => 'micropub-media-endpoint',
        'method'  => 'POST',
        'action'  => function() use($endpoint) {

          try {
            $endpoint->startMedia();
            echo response::success('File uploaded!', [], 201);

          } catch (Exception $e) {
            echo $endpoint->respondWithError($e);
          }
        }
      ],
    ]);
  }

  /**
   * Start the Micropub endpoint and return a HTTP 201 header with the location of the new post
   *
   */
  public function start() {

    $endpoint = $this;

    IndieAuth::requireMe();

    // First check for JSON
    $request = str::parse(r::body());
    if (isset($request['type']) and $request['type'][0] == 'h-entry' and isset($request['properties'])) {
      $data = $request['properties'];
      $template = str::after($request['type'][0], 'h-');
      // $data contains the parsed JSON

    } elseif ($data = r::postData() and isset($data['h']) == 'entry') {
      $template = $data['h'];
      // $data contains the parsed POST-data

    } else {
      throw new Error('We only accept h-entry as json or x-www-form-urlencoded', Endpoint::ERROR_INVALID_REQUEST);
    }

    // Don't store the access token from POST-requests
    unset($data['access_token'], $data['h']);

    if (!isset($data) or !is_array($data) or count($data) < 1)
      throw new Error('No content was found', Endpoint::ERROR_INVALID_REQUEST);

    $data = $endpoint->fillFields($data);

    $data['token'] = yaml::encode(IndieAuth::getToken());

    // Set the slug
    if (isset($data['slug'])) $slug = str::slug($data['slug']);
    elseif (isset($data['name'])) $slug = str::slug($data['name']);
    elseif (isset($data['text'])) $slug = str::slug(str::excerpt($data['text'], 50, true, ''));
    elseif (isset($data['summary'])) $slug = str::slug(str::excerpt($data['summary'], 50, true, ''));
    else $slug = time();

    try {
      $pageCreator = c::get('micropub.page-creator', function($uid, $template, $data) {
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

      $newEntry = call($pageCreator, [$slug, 'entry', $data]);
    } catch (Exception $e) {
      throw new Error('Post could not be created');
    }

    // Handle the multipart files
    if (r::files()) {
      $files = $endpoint->handleReceivedFiles($newEntry);
      if ($newEntry->photo()->isNotEmpty())
        $urls = [$newEntry->photo()];
      else $urls = [];
      foreach ($files as $file) $urls[] = $file->filename();
      $urls = implode(',', $urls);
      $newEntry->update(['photo' => $urls]);
    }

    // Handle the Media-endpoint files
    foreach ($data as $key => $field) {
      if (str::startsWith($field, $endpoint->mediaUrl)) {
        $filename = f::filename($field);
        $newfilename = substr($filename, 41); // Without the sha1-hash and dash
        f::move($endpoint->mediaPath . DS . $filename,
                $newEntry->root() . DS . $newfilename);
        $update[$key] = $newfilename;
      }
    }
    if (isset($update)) $newEntry->update($update);


    return header("Location: " . $newEntry->url(), true, 201);
  }

  /**
   * Starts the Media-Endpoint and returns an HTTP 201 header with the url of the newly uploaded file.
   *
   */
  public function startMedia() {

    $endpoint = $this;

    IndieAuth::requireMe();

    if (url::short(url::base($token->me)) != url::short(url::base()))
      throw new Error('You are not me', Endpoint::ERROR_FORBIDDEN);

    if (r::files()) {
      // Create some 'unguessable' name
      $filename  = sha1(rand()).'-{safeFilename}';

      $root = $endpoint->mediaPath . DS . $filename;
      $url  = $endpoint->mediaUrl . '/' . $filename;

      $upload = new Upload($root, ['input' => 'file']);

    } else throw new Error('No file', Endpoint::ERROR_INVALID_REQUEST);

    // If there is no file, throw error
    if (!$upload->file()) throw new Error('Upload failed');

    // Everything went fine, so return the url
    return header("Location: ".$endpoint->mediaUrl . '/' . $upload->file()->filename(), true, 201);
  }


  /**
   * Gets the image from another server
   *
   * @param str $url The url of the image to fetch
   * @return str relative url to local image
   */
  private function fetchImage($url) {

    $response = remote::get($url);

    if (str::contains($response->headers['Content-Type'], 'png')
       or str::contains($response->headers['Content-Type'], 'jpg')
       or str::contains($response->headers['Content-Type'], 'jpeg')
       or str::contains($response->headers['Content-Type'], 'gif')) {

      // Create the 'unguessable' name
      $filename  = sha1(rand()).'-'.f::safeName($url);

      $root = $this->mediaPath . DS . $filename;
      $url  = $this->mediaUrl . '/' . $filename;
      $file = new Media($root, $url);

      f::write($root, $response->content());

      return $file->url();
    }

    return $url;
  }

  /**
   * Moves files, received by multipart-request, to the page's content folder
   *
   * @param object $page The page where the files pertain to
   * @return array The received files as File objects
   */
  private function handleReceivedFiles($page) {
    $missingFile = false;
    $files = [];
    $index = 0;

    do {
      try {
        $upload = new Upload($page->root() . DS . '{safeFilename}', [
          'input' => 'photo',
          'index' => $index
        ]);

        if (!$upload->file()) $missingFile = true;
        else $files[] = $upload->file();

        $index++;

      } catch(Error $e) {

        switch($e->getCode()) {

          case Upload::ERROR_MISSING_FILE:
            // No more files have been uploaded
            $missingFile = true;
            break;

          default:
            throw new Error($e->getMessage());
        }
      }
    } while(!$missingFile);

    return $files;
  }

  /**
   * Helper function to handle data
   *
   * @param array $data The Microformats data
   * @return array $data All the data, ready to pass in $page->create() or similar functions.
   */
  private function fillFields($data) {

    // Rename 'content' to 'text', as to not upset Kirby.
    if (isset($data['content'])) {
      $data['text'] = $data['content'];
      unset($data['content']);
    }

    // Let's set some things straight, so Kirby can save them.
    foreach ($data as $key => $field) {

      if (is_array($field)) {

        // Check for nestled Microformats object
        if (isset($field[0]['type']) and substr($field[0]['type'][0], 0, 2) == 'h-' and isset($field[0]['properties']))
          $data[$key] = yaml::encode($field);

        // If we get specific HTML, just save it as the field
        elseif (isset($field[0]['html']))
          $data[$key] = $field[0]['html'];

        // Let's assume we can implode cuz yolo
        else
          $data[$key] = implode(',', $field);
      }

      // If it has urls, maybe it has images
      elseif (v::url($field))
        $data[$key] = $this->fetchImage($data[$key]);
    }

    // Add dates and times
    if (isset($data['published'])) {
      $data['published'] = strftime('%F %T', strtotime($data['published']));
    } else {
      $data['published'] = strftime('%F %T');
    }
    $data['updated'] = strftime('%F %T');

    return $data;
  }

  /**
   * Makes an error response based on the error code.
   *
   * @param Exception $e The catched error
   */
  private function respondWithError($e) {
    switch($e->getCode()) {
      case Endpoint::ERROR_FORBIDDEN:
        response::json([
          'error' => 'forbidden',
          'error_description' => $e->getMessage()
        ], 403);
        break;
      case Endpoint::ERROR_INSUFFICIENT_SCOPE:
        response::json([
          'error' => 'insufficient_scope',
          'error_description' => $e->getMessage()
        ], 401);
        break;
      case Endpoint::ERROR_INVALID_REQUEST:
        response::json([
          'error' => 'invalid_request',
          'error_description' => $e->getMessage()
        ], 400);
        break;
      default:
        response::json([
          'error' => 'error',
          'error_description' => $e->getMessage()
        ], 500);
    }
  }

}