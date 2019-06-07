<?php // testFullSyncCount.spec.php

// include PW API
include_once(__DIR__ . "/../../../../index.php");

describe('ES Index after first batchSync', function() {
    beforeEach(function() {

      // load ElasticsearchFeeder module class
      $this->ElasticsearchFeeder = $modules->get('ElasticsearchFeeder');
      $template = $templates->get('basic-page')

      $baseUrl = $this->ElasticsearchFeeder->getElasticSearchUrlBase();
      $indexName = $this->ElasticsearchFeeder->getElasticSearchIndexName($template);
      $query = "q=prefix:{$ElasticsearchFeeder->getIndexPrefix()}";

      $this->countRequestUrl = "$baseUrl/$indexName/_count?$query";
    });

    describe('count', function() {
        it('should return the number of items', function() {
            $count = $this->ElasticsearchFeeder->curlJsonGet($this->countRequestUrl, null)["count"];
            assert($count === 3, 'expected 3');
        });
    });
});

# node test 1 execut node script check count --> request ES
# unpublish something --> node test 2
# delete something --> node test 3
# republish something --> node test 4
# save something --> node test 5
