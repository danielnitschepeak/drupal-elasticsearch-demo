<?php

namespace Drupal\searchjobs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Elasticsearch;
use Drupal\searchjobs\Mapper\SearchResultsMapper;

/**
 * Class SearchController.
 *
 * @package Drupal\searchjobs\Controller
 */
class SearchController extends ControllerBase {

  /**
   * Search.
   *
   * @return string
   *   Return Hello string.
   */
  public function search() {
    $searchResultsMapper = new SearchResultsMapper();
    $searchResultsMapper->setSearchResults($this->getJobs());
    $searchResults = $searchResultsMapper->convert();
    return [
      '#theme' => 'searchResults',
      '#searchResults' => $searchResults,
      '#attached' => [
        'library' => [
          'searchjobs/searchjobs',
        ],
      ],
    ];

  }

  private function getJobs() {
    $clientBuilder = Elasticsearch\ClientBuilder::create();
    $client = $clientBuilder->build();
    $query = [];
    if (!empty($_GET['query'])) {
      $query = [
        'query' => [
          'match' => [
            '_all' => [
              'query' => $_GET['query'],
              'fuzziness' => 4,
            ],
          ],
        ],
        'highlight' => [
          'fields' => [
            '*' => (object) [],
          ],
          'require_field_match' => false,
        ],
      ];
    }

    $jobs = $client->search([
      'index' => 'jobs',
      'type' => 'job',
      'from' => 1,
      'size' => 1000,
      'body' => $query,
    ]);

    return $jobs;

  }

}
