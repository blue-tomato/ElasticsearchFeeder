<?php // testFullSyncCount.spec.php

describe('ES Index after first batchSync', function() {
    beforeEach(function() {

      // include PW API
      include(__DIR__ . "/../../../../../index.php");

      // load ElasticsearchFeeder module class
      $this->ElasticsearchFeeder = $modules->get('ElasticsearchFeeder');
      $template = $templates->get('basic-page');

      $baseUrl = $this->ElasticsearchFeeder->getElasticSearchUrlBase();
      $indexName = $this->ElasticsearchFeeder->getElasticSearchIndexName($template);

      $this->countRequestUrl = "{$baseUrl}/{$indexName}/_count";
    });

    describe('count', function() {
        it('should return the number of items', function() {
            $countObj = $this->ElasticsearchFeeder->curlJsonGet($this->countRequestUrl, null);
            assert($countObj["count"] === 3, 'expected 3');
        });
    });
});
