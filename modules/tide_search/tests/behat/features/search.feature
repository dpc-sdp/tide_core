Feature: Ensure Search API on Bay Elasticsearch work.

  @api
  Scenario: Assert that Elasticsearch configuration exists in Drupal
    Given I am logged in as a user with the "administrator" role
    When I go to "admin/config/search/elasticsearch-connector"
    Then I save screenshot
    And I should see the text "ElasticSearch on Bay"

    When I go to "admin/config/search/search-api"
    Then I save screenshot

    When I visit "http://elasticsearch:9200/_cat/indices?v"
    Then the response status code should be 200
    And the response should contain "elasticsearch_index_drupal_node"

    When I visit "http://elasticsearch:9200/_cat/aliases?v"
    Then the response status code should be 200
    And the response should contain "search--elasticsearch-index-drupal-node-alias"

    When I send a GET request to "http://elasticsearch:9200/elasticsearch_index_drupal_node/_mapping"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "elasticsearch_index_drupal_node" should exist
    And the JSON node "elasticsearch_index_drupal_node.mappings" should exist
    And the JSON node "elasticsearch_index_drupal_node.mappings.properties" should exist
    And the JSON node "elasticsearch_index_drupal_node.mappings.properties.summary_processed" should exist
    And the JSON node "elasticsearch_index_drupal_node.mappings.properties.summary_processed.fields.prefix.search_analyzer" should be equal to "q_prefix"
    And the JSON node "elasticsearch_index_drupal_node" should exist
    And the JSON node "elasticsearch_index_drupal_node.mappings" should exist
    And the JSON node "elasticsearch_index_drupal_node.mappings.properties" should exist
    And the JSON node "elasticsearch_index_drupal_node.mappings.properties.title" should exist
    And the JSON node "elasticsearch_index_drupal_node.mappings.properties.title.fields.prefix.analyzer" should be equal to "i_prefix"

  @api
  Scenario: Assert that Elasticsearch can index content.
    Given topic terms:
      | name       | parent |
      | Test Topic | 0      |
    And tags terms:
      | name     | parent |
      | Test Tag | 0      |
    And test content:
      | title              | body:value | moderation_state | field_topic | field_tags |
      | TESTTITLEPUBLISHED | TESTBODY   | published        | Test Topic  | Test Tag   |

    # Published Test content should be indexed upon save.
    And I am logged in as a user with the "administrator" role
    When I edit test "TESTTITLEPUBLISHED"
    Then the response status code should be 200
    And I select "Published" from "Change to"
    And I press "Save"
    And I wait for 5 seconds

    # Published Test content should be in search results.
    When I visit "http://elasticsearch:9200/elasticsearch_index_drupal_node/_search?q=title:testtitlepublished"
    Then the response status code should be 200
    And I save screenshot

    When I send a GET request to "http://elasticsearch:9200/elasticsearch_index_drupal_node/_search?q=title:testtitlepublished"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "hits" should exist
    And the JSON node "hits.hits" should exist
    And the JSON node "hits.hits[0]._index" should be equal to "elasticsearch_index_drupal_node"
    And the JSON node "hits.hits[0]._source" should exist
    And the JSON node "hits.hits[0]._source.title[0]" should be equal to "TESTTITLEPUBLISHED"
    And the JSON node "hits.hits[0]._source.body[0]" should be equal to "TESTBODY"
    And the JSON node "hits.hits[0]._source.field_topic_name[0]" should be equal to "Test Topic"
    And the JSON node "hits.hits[0]._source.field_tags_name[0]" should be equal to "Test Tag"

    # Unpublished Test content.
    When I edit test "TESTTITLEPUBLISHED"
    Then the response status code should be 200
    And I select "Archived" from "Change to"
    And I press "Save"
    And I wait for 5 seconds

    # Unpublished Test content should not be in search results.
    When I send a GET request to "http://elasticsearch:9200/elasticsearch_index_drupal_node/_search?q=title:testtitlepublished"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "hits" should exist
    And the JSON node "hits.total" should exist
    And the JSON node "hits.total.value" should exist
    And the JSON node "hits.total.value" should be equal to "0"
