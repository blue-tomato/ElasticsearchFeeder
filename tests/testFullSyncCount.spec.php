<?php // testFullSyncCount.spec.php

use Elasticsearch\ClientBuilder;
require '../vendor/autoload.php';

describe('ES Index after first batchSync', function() {
    beforeEach(function() {
      $clientBuilder = ClientBuilder::create();
      $clientBuilder->setHosts(['es']);
      $this->client = $clientBuilder->build();
    });

    describe('count', function() {
        it('should return the number of items', function() {
            $count = $this->client->cat()->count(['index' => 'testindex']);
            assert($count === 3, 'expected 3');
        });
    });
});
