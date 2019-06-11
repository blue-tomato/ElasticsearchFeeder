<?php

describe('ElasticsearchFeeder Tests', function() {
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

    describe('Initial item count after full sync', function() {
        it('should return the number of items', function() {
            $countObj = $this->ElasticsearchFeeder->curlJsonGet("{$this->baseUrl}/{$this->indexName}/_count", null);
            assert($countObj["count"] === 3, 'expected 3');
        });
    });

    describe('Specific Page should be in the index', function() {
        it('query should return page with correct page-id', function() {
          $result = $this->ElasticsearchFeeder->curlJsonGet("{$this->baseUrl}/{$this->indexName}/_doc/{$this->esId}", null);
          assert($result["found"] == true, 'expected true');
        });
    });

    describe('Unpublish specific Page', function() {
        it('query should return empty result', function() {
          $this->page->addStatus('unpublished');
          $this->page->save();
          sleep(10); // prevent race condition
          $result = $this->ElasticsearchFeeder->curlJsonGet("{$this->baseUrl}/{$this->indexName}/_doc/{$this->esId}", null);
          assert($result["found"] == false, 'expected false');
        });
    });

    describe('Publish specific Page', function() {
        it('query should return page with correct page-id', function() {
          $this->page->addStatus('published');
          $this->page->save();
          sleep(10); // prevent race condition
          $result = $this->ElasticsearchFeeder->curlJsonGet("{$this->baseUrl}/{$this->indexName}/_doc/{$this->esId}", null);
          assert($result["found"] == true, 'expected true');
        });
    });

    describe('Change something in specific page', function() {
        it('query should return the page with the changed value', function() {
          $newTitle = 'Hello From Peridot';
          $this->page->setAndSave('title', $newTitle);
          sleep(10); // prevent race condition
          $result = $this->ElasticsearchFeeder->curlJsonGet("{$this->baseUrl}/{$this->indexName}/_doc/{$this->esId}", null);
          var_dump($result);
          assert($result["_source"]["title"] == $newTitle, "expected: $newTitle");
        });
    });

    describe('Specific Page should have meta value elasticsearch_lastindex', function() {
        it('should return not empty value', function() {
          $metaValue = $this->page->meta('elasticsearch_lastindex');
          assert(isset($metaValue) && !empty($metaValue), "expected not empty value");
        });
    });

    describe('Remove specific Page', function() {
        it('query should return empty result', function() {
          $this->page->delete();
          sleep(10); // prevent race condition
          $result = $this->ElasticsearchFeeder->curlJsonGet("{$this->baseUrl}/{$this->indexName}/_doc/{$this->esId}", null);
          assert($result["found"] == false, 'expected false');
        });
    });

});
