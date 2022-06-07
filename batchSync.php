<?php namespace ProcessWire;

  set_time_limit(0);
  ini_set('max_execution_time', 0);

  // include processwire api
  include_once(__DIR__ . "/../../../index.php");

  // if not executed over cli
  if(!$config->cli) exit();

  if($config->elasticsearchFeederDisabled == true) {
    echo "Warning: ElasticsearchFeeder is deactivated throw config.php\n";
    exit();
  }

  // load ElasticsearchFeeder module class
  $ElasticsearchFeeder = $modules->get('ElasticsearchFeeder');

  // send all documents to ElasticSearch
  $ready = $ElasticsearchFeeder->indexAllAllowedPages(false);
  echo $ready;
  
  exit();
