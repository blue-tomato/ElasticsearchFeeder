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

      echo "Now checking $indexName ({$template->name}) for invalid documents\n";

      if($result && isset($result["hits"]) && isset($result["hits"]["hits"])) {
        foreach($result["hits"]["hits"] as $pageItem) {
          $pageId = $sanitizer->selectorValue($pageItem["_source"]["page-id"]);
          $page = $pages->getRaw("id=$pageId");
                    
          if(!$page || ($page && isset($page["status"]) && $page["status"] != Page::statusOn)) {
            // delete from ES
            $ElasticsearchFeeder->curlJsonDeleteByElasticSearchId($pageItem["_id"], $pageItem["_type"], $pageItem["_index"]);
            echo "Page {$pageItem['_id']} deleted from ElasticSearch\n";
          }

          unset($page);
		  $pages->uncacheAll();

        }
      }

      unset($result);
      unset($count);

    }

  }

  exit();
