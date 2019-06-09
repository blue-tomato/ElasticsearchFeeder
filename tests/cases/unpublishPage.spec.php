<?php

describe('unpublish a page', function() {
    beforeEach(function() {

      // include PW API
      include_once(__DIR__ . "/../../../../../index.php");

      // load ElasticsearchFeeder module class
      $this->ElasticsearchFeeder = $modules->get('ElasticsearchFeeder');
      $template = $templates->get('basic-page');

      $baseUrl = $this->ElasticsearchFeeder->getElasticSearchUrlBase();
      $indexName = $this->ElasticsearchFeeder->getElasticSearchIndexName($template);
      $prefix= $this->ElasticsearchFeeder->getIndexPrefix();

      $page= $pages->find("template=$template")->first();

      $page->addStatus(Page::statusUnpublished);
      $page->save();

      $this->esId = $this->ElasticsearchFeeder->createElasticSearchDocumentHashedId($page->id, $prefix);

    });

    describe('page should not be in the ES index', function() {
        it('query should return empty result', function() {
            //$countObj = $this->ElasticsearchFeeder->curlJsonGet($this->countRequestUrl, null);
            //assert($countObj["count"] === 3, 'expected 3');
        });
    });
});

# unpublish something --> node test 2
# delete something --> node test 3
# republish something --> node test 4
# save something --> node test 5

// 1.0.6 --> .schema to schema.php, first test setup
// 1.1.0 --> finished tests, change from lastindex field to page->meta(), more type checks in class, public prefix function (umbau schema! und doku)
