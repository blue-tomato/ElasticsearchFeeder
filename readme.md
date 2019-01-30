# README

This ElasticSearch module for [ProcessWire CMS/CMF](http://processwire.com/) will sync your page content to an ElasticSearch index, which provides you a convenient way to search it.

## Table of Contents

- [Features](#features)
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
  - [Module Configuration](#configuration)
  - [Schema](#schema)
- [Support](#support)
- [Contributing](#contributing)
- [License](#license)

## Features

- Batch add / updates pages in ElasticSearch
- Add / update page in ElasticSearch after page save/publish
- Remove page from ElasticSearch after trash, delete or unpublish page

## Prerequisites

Before you'll start using this module, make sure it's compatible with your technical ElasticSearch setup and that it's fulfills your content indexing requires. We've created this module to support a `bonsai.io` (alternatives: [AWS ES](https://aws.amazon.com/de/elasticsearch-service/), [Elastic Cloud](https://www.elastic.co/cloud), etc.) hosted ElasticSearch SaaS instance. The most important part to consider when evaluating the `module <> ES SaaS provider` relation is, whether it's possible to connect to the ES instance via a URL with authentication included. Meaning a URL base pattern like...

`https://{ES_ACCESS_KEY}:{ES_ACCESS_SECRET}@{ES_INSTANCE_URL}.bonsaisearch.net\`

Usually ES SaaS providers inform about this in a setup/configuration section in their backend. In case your own-hosted ES instance is able to provide access like mentioned above, you're also good to go with this module.

## Installation

To install this module...
1. paste the `ElasticsearchFeeder` folder to your `modules` directory in `/site/modules`.
2. ProcessWire will detect the module and list it in the backend's `Modules` > `Site` > `ElasticsearchFeeder` section. Navigate there and install it.

*NOTE: Installing the module will add a `elasticsearch_lastindex` field to your templates when the page gets indexed initially (so on runtime), which will only be visible to superusers.*

## Usage

To use this module you'll have to setup some module configurations and schema instructions.

### Configuration

Configure the module in your ProcessWire module backend (which will be available after the module was installed). The module configuration enables to do the following via a ProcessWire-driven backend form:

- define ES backend protocol (http or https)
- insert `ES_ACCESS_HOST`
- insert `ES_ACCESS_KEY`
- insert `ES_ACCESS_SECRET`
- insert the path to your schema (see [Schema](#schema) to see how those work)
- optionally insert a prefix string that'll be used when hashing your ES ids
- insert template configurations (see [Schema](#schema) to see how those work)
- (re)index all pages matching our module configuration by clicking the "Index All Pages" button. *NOTE: using this option can take quite a long time (primarily depending on how much ProcessWire pages you're going to send to the ElasticSearch index).*

### Schema

Setup a schema or multiple schemas to define which content(s) will be shipped to your ElasticSearch instance. Consider to place your schema files with a `.schema` file ending in the directory path you declared when configuring your module in the ProcessWire backend.

Basically said: for each ElasticSearch document type, there must be a PHP function returning the contents to be indexed in your ElasticSearch instance.
The naming convention of this function has to be the `camelCased` document type name you declare in the ProcessWire backend module configuration. So i.e.: a document type named **news-details-page** in the ProcessWire backend requires schema function named **newsDetailsPage**.

The filename itself has to be the same name as the template name. I.e.: **news-details-page.php** should be **news-details-page.schema**

#### Page Filtering in Schema
If a Schema Function returns `false` as value, the page will not be sent to ElasticSearch. You can use this for filtering you pages and sending only specific pages from this template to ElasticSearch.

#### Schema Function (i.e. news-details-page.schema)

This module passes the following arguments to your schema function.

- @arg1 `$page` the ProcessWire page WireArray
- @arg2 `$ElasticsearchFeeder` this module, primarily to use it's static method **createElasticSearchDocumentHashedId($page->id, $indexPrefix)** to assign ElasticSeach IDs to your document content.
- @arg3 `$indexPrefix` the index prefix you declared when configuring this module in the ProcessWire backend

```php
function newsDetailsPage($page, $ElasticsearchFeeder, $indexPrefix) {

  // don't send page to Elasticsearch in case we don't want to
  if($page->property->value == "xyz") { return false; }

  // now start building the $document array you want to ship to Elasicsearch
  $document = [];
  $document['type'] = 'news-detailspage';
  $document['name'] = $page->title;

  // send $document back to ElasticsearchFeeder module
  return $document;
}
```

#### Schema Function Debugging
This module provides a convenient way to debug your schema and thus see what's being sent to ElasticSearch. You can see this after saving a page in the ProcessWire admin message bar as JSON output.

To enable module debugging:
- go to the module configuration page in `Modules` > `Site` > `ElasticsearchFeeder`
- find the debug config area like shown below

![](docs/images/debugModule.png)

### Batch Sync via CLI or Cron
You can send your pages throw the "Index all Pages" button in the module configuration page in `Modules` > `Site` > `ElasticsearchFeeder`. If you have many pages, this can run very long and it can cause server timeouts.

For this reason you can use the `batchSync.php` script in the module path via command line. You can also set up a repeating cronjob to ensure a full sync every _x_ times.

The `batchSync.php` script send all pages to ElasticSearch and checks if all documents in the index are public pages in your ProcessWire system.

I.e:
```bash
php site/modules/ElasticsearchFeeder/batchSync.php
```

### Request to ElasticSearch throw a proxy server
If you have your Server behind a proxy, you can add to your `config.php` file following properties:

- `$config->httpProxy = "your-http-proxy-server.xyz:8888";`
- `$config->httpsProxy = "your-https-proxy-server.xyz:5394";`

### Deactivate ElasticSearchFeeder throw config.php
If you want to prevent to send pages to ElasticSearch from your development or staging server but don't want to deactivate the module in the database, you can add `$config->elasticsearchFeederDisabled = true` to your `config.php` or `config-dev.php` file. This will prevent of adding the necessary hooks for the indexation.

## ElasticSearch Version and Document-Type

Please consider that ElasticSearch 6.0.0 removed the support for multiple document-types in one and the same index. We support both variants with this module. You can define for each ProcessWire template seperate index and document-type names. I.e. if you use ElasticSearch => 6.0.0, you can use the same name for index and document-type.

https://www.elastic.co/guide/en/elasticsearch/reference/6.x/removal-of-types.html

## Support

Please [open an issue](https://github.com/blue-tomato/ElasticsearchFeeder/issues/new) for support.

## Contributing

Create a branch on your fork, add commits to your fork, and open a pull request from your fork to this repository.

To get better insights and onboard you on module implementation details just open a support issue. We'll get back to you asap.

## Credits

This module is made by people from Blue Tomato. If you want to read more about our work, follow us on https://dev.to/btdev

## License

Find all information about this module's license in the LICENCE.txt file.
