<?php

  //if(getenv('PW_ENV') != 'testing') {
  //  echo 'This script should only run in the CI server';
  //  exit();
  //}

  include_once(__DIR__ . "/../../../../index.php");

  //$esconfig = $modules->getModuleConfigData('ElasticsearchFeeder');

  $testTemplateId = 29;
  $testTemplateName = 'basic-page';

  // Setup Test Module Config
  $modules->saveConfig('ElasticsearchFeeder', [
    'es_debug_mode' => 'off',
    'es_protocol' => 'http',
    'es_host' => 'es:9200',
    'es_schema_path' => 'elasticSearchSchema',
    'es_index_id_prefix' => 'testprefix',
    "es_template_index_$testTemplateId" => "yes",
    "es_template_indexname_$testTemplateId" => "testindex",
    "es_template_type_$testTemplateId" => "testtype"
  ]);
  
  exit();
