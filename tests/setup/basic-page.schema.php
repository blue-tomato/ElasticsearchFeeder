<?php

function basicPage($page) {

  $document = [];
  $document['type'] = 'basic-page';
  $document['title'] = $page->title;

  return $document;
}
