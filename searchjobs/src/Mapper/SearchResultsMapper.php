<?php

namespace Drupal\searchjobs\Mapper;

class SearchResultsMapper {

  private $searchResults;

  public function setSearchResults(array $searchResults) {
    $this->searchResults = $searchResults;
  }

  public function convert() {
    $searchResults = [];

    $hits = $this->searchResults['hits']['hits'] ?? [];
    foreach ($hits as $hit) {
      $description = $hit['highlight']['description'][0] ?? NULL;
      $searchResults[] = [
        'nid' => $hit['_source']['nid'] ?? NULL,
        'title' => $hit['highlight']['title'][0] ?? $hit['_source']['title'] ?? NULL,
        'employer' => $hit['_source']['employer'] ?? NULL,
        'close_date' => $hit['_source']['closing_date'] ?? NULL,
        'description' => str_replace(['<br />', ' />'], '',  $description),
      ];
    }

    return $searchResults;
  }

}
