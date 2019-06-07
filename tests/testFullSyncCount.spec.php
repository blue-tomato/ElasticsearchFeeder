<?php // testFullSyncCount.spec.php

describe('ES Index after first batchSync', function() {
    beforeEach(function() {

      // include PW API
      include_once(__DIR__ . "/../../../../index.php");

      // load ElasticsearchFeeder module class
      $this->ElasticsearchFeeder = $modules->get('ElasticsearchFeeder');
      $template = $templates->get('basic-page');

      $baseUrl = $this->ElasticsearchFeeder->getElasticSearchUrlBase();
      $indexName = $this->ElasticsearchFeeder->getElasticSearchIndexName($template);
      $query = "q=prefix:{$this->ElasticsearchFeeder->getIndexPrefix()}";

      $this->countRequestUrl = "{$baseUrl}/{$indexName}/_count?{$query}";
    });

    describe('count', function() {
        it('should return the number of items', function() {
            $countObj = $this->ElasticsearchFeeder->curlJsonGet($this->countRequestUrl, null);

            var_dump($countObj);

            assert($countObj["count"] === 3, 'expected 3');
        });
    });
});

# node test 1 execut node script check count --> request ES
# unpublish something --> node test 2
# delete something --> node test 3
# republish something --> node test 4
# save something --> node test 5
