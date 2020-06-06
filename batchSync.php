<?php namespace ProcessWire;

  set_time_limit(0);

  // include processwire api
  include_once(__DIR__ . "/../../../index.php");

  if($config->elasticsearchFeederDisabled == true) {
    echo "Warning: ElasticsearchFeeder is deactivated throw config.php\n";
    exit();
  }

  // load ElasticsearchFeeder module class
  $ElasticsearchFeeder = $modules->get('ElasticsearchFeeder');

  // send all documents to ElasticSearch
  $ready = $ElasticsearchFeeder->indexAllAllowedPages(false);
  echo $ready;

  // check if available documents in ElasticSearch are public available in ProcessWire
  // of not delete them from ElasticSearch
  foreach($templates as $template) {
    if($ElasticsearchFeeder->checkAllowedTemplate($template)) {

      $baseUrl = $ElasticsearchFeeder->getElasticSearchUrlBase();
      $indexName = $ElasticsearchFeeder->getElasticSearchIndexName($template);
      $query = "q=prefix:{$ElasticsearchFeeder->getIndexPrefix()}";

      $countRequestUrl = "{$baseUrl}/{$indexName}/_count?{$query}";
      $count = $ElasticsearchFeeder->curlJsonGet($countRequestUrl, null)["count"];

      $searchRequestUrl = "{$baseUrl}/{$indexName}/_search?{$query}&size={$count}";
      $result = $ElasticsearchFeeder->curlJsonGet($searchRequestUrl, null);

      echo "Now checking $indexName for invalid documents\n";

      if($result && isset($result["hits"]) && isset($result["hits"]["hits"])) {
        foreach($result["hits"]["hits"] as $pageItem) {
          $pageId = $pageItem["_source"]["page-id"];
          $page = $pages->get($pageId);
          if(!$page || ($page && !$page->isPublic())) {
            // delete from ES
            $ElasticsearchFeeder->curlJsonDeleteByElasticSearchId($pageItem["_id"], $pageItem["_type"], $pageItem["_index"]);
            echo "Page {$pageItem['_id']} deleted from ElasticSearch\n";
          }
        }
      }

    }
  }

  exit();
