<?php

describe('unpublish a page', function() {
    beforeEach(function() {

      // include PW API
      include(__DIR__ . "/../../../../../index.php");

      // load ElasticsearchFeeder module class
      $this->ElasticsearchFeeder = $modules->get('ElasticsearchFeeder');
      $template = $templates->get('basic-page');

      $this->baseUrl = $this->ElasticsearchFeeder->getElasticSearchUrlBase();
      $this->indexName = $this->ElasticsearchFeeder->getElasticSearchIndexName($template);
      $this->prefix= $this->ElasticsearchFeeder->getIndexPrefix();

      $this->page= $pages->find("template=$template")->first();

      $this->esId = $this->ElasticsearchFeeder->createElasticSearchDocumentHashedId($this->page->id, $this->prefix);

    });

    describe('page should be in the ES index', function() {
        it('query should return page with correct page-id', function() {
          $result = $this->ElasticsearchFeeder->curlJsonGet("{$this->baseUrl}/{$this->indexName}/_doc/{$this->esId}", null);
          assert($result["found"] == true, 'expected true');
        });
    });

    describe('page should not be in the ES index', function() {
        it('query should return empty result', function() {
          $this->page->addStatus('unpublished');
          $this->page->save();
          sleep(10); // prevent race condition
          $result = $this->ElasticsearchFeeder->curlJsonGet("{$this->baseUrl}/{$this->indexName}/_doc/{$this->esId}", null);
          assert($result["found"] == false, 'expected false');
        });
    });
});

# unpublish something --> node test 2
# delete something --> node test 3
# republish something --> node test 4
# save something --> node test 5
