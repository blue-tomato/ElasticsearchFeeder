<?php

  //if(getenv('PW_ENV') != 'testing') {
  //  echo 'This script should only run in the CI server';
  //  exit();
  //}

  include_once(__DIR__ . "/../../../../index.php");

  //$esconfig = $modules->getModuleConfigData('ElasticsearchFeeder');

  // Setup Test Module Config
  $modules->saveConfig('ElasticsearchFeeder', [
    'es_debug_mode' => 'off',
    'es_protocol' => 'http',
    'es_host' => 'es:9200',
    'es_schema_path' => 'elasticSearchSchema',
    'es_index_id_prefix' => 'test'
  ]);

  // template ID: 29 basic-page

  // Setup Test Schema for processwire default siteprofile


  exit();
