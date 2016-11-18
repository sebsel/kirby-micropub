<?php

namespace Sebsel\Micropub;

use A;
use Obj;
use Data;
use Field;
use Tpl;
use V;
use Exception;

class Interaction extends Obj {

  public $data   = null;
  public $id     = null;
  public $page   = null;
  public $file   = null;
  public $author = null;
  public $title  = null;
  public $name   = null;
  public $text   = null;
  public $date   = null;
  public $url    = null;

  public function __construct($page, $data) {

    if(!is_array($data) or empty($data)) {
      throw new Exception('No data');
    }

    if(empty($data['url']) or !v::url($data['url'])) {
      throw new Exception('No url found');
    }


    $this->data   = $data;
    $this->page   = $page;
    $this->file   = $file;
    $this->author = new Author($this);
    $this->id     = sha1($file);

    $this->field('title', 'name');
    $this->field('name');
    $this->field('text');
    $this->field('url');
    $this->field('rsvp');

    $this->date = new Field($this->page, 'date', strtotime($data['published']));

  }


  public function field($key, $field = null) {

    if(is_null($field)) $field = $key;

    $value = a::get($this->data, $field);

    if($key == 'url' and !v::url($value)) {
      $value = null;
    }

    $this->$key = new Field($this->page, $key, esc($value));
  }


  public function is($type) {
    return $this->type->value == $type;
  }

  public function date($format = null) {
    if($format) {
      $handler = kirby()->option('date.handler', 'date');
      return $handler($format, $this->date->value);
    } else {
      return $this->published;
    }
  }


  public function __toString() {
    return (string)$this->url();
  }

}

field::$methods['toInteraction'] = field::$methods['interaction'] = function($field) {
  return new Interaction($field->page(), $field->yaml());
};
