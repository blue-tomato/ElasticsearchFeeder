<?php

function basicPage($page, $ElasticsearchFeeder, $indexPrefix) {

  $document = [];
  $document['type'] = 'basic-page';
  $document['title'] = $page->title;

  return $document;
}
