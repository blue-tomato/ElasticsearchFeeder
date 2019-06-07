<?php // testFullSyncCount.spec.php

use Elasticsearch\ClientBuilder;
require '../vendor/autoload.php';

$clientBuilder = ClientBuilder::create();
$clientBuilder->setHosts(['es']);
$client = $clientBuilder->build();
echo $client->cat()->count(['index' => 'testindex']);

exit();

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

# node test 1 execut node script check count --> request ES
# unpublish something --> node test 2
# delete something --> node test 3
# republish something --> node test 4
# save something --> node test 5
