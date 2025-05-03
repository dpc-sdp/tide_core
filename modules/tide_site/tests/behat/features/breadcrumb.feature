@jsonapi
Feature: Breadcrumb tests

  @api @javascript
  Scenario: Check breadcrumb field is updated correctly.
    Given sites terms:
      | name        | parent | tid    |
      | Test Site 1 | 0      | 999991 |
    Given test content:
      | title                 | moderation_state | field_node_site | field_node_primary_site | body | nid    | uuid                                |
      | [TEST] Test content   | published        | Test Site 1     | Test Site 1             | Test | 999999 | 99999999-aaaa-bbbb-ccc-000000000000 |
      | [TEST] Test content 1 | published        | Test Site 1     | Test Site 1             | Test | 999911 | 99999999-aaaa-bbbb-ccc-000000000001 |
      | [TEST] Test content 2 | published        | Test Site 1     | Test Site 1             | Test | 999912 | 99999999-aaaa-bbbb-ccc-000000000002 |
      | [TEST] Test content 3 | published        | Test Site 1     | Test Site 1             | Test | 999913 | 99999999-aaaa-bbbb-ccc-000000000003 |
      | [TEST] Test content 4 | published        | Test Site 1     | Test Site 1             | Test | 999914 | 99999999-aaaa-bbbb-ccc-000000000004 |
      | [TEST] Test content 5 | published        | Test Site 1     | Test Site 1             | Test | 999915 | 99999999-aaaa-bbbb-ccc-000000000005 |
      | [TEST] Test content 6 | published        | Test Site 1     | Test Site 1             | Test | 999916 | 99999999-aaaa-bbbb-ccc-000000000006 |
    Given I am logged in as a user with the "administrator" role
    When I edit test "[TEST] Test content 2"
    Then I fill in "Breadcrumb Parent" with "[TEST] Test content 1"
    Then I press the "Save" button
    Then I edit test "[TEST] Test content 2"
    When I send a GET request to "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000002?site=999991"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "data.attributes.breadcrumb_field" should exist
    And the JSON node "data.attributes.breadcrumb_field[0].url" should be equal to "/test-test-content-1"
    And the JSON node "data.attributes.breadcrumb_field[0].name" should be equal to "[TEST] Test content 1"
    And the JSON node "data.attributes.breadcrumb_field[1].url" should be equal to "/test-test-content-2"
    And the JSON node "data.attributes.breadcrumb_field[1].name" should be equal to "[TEST] Test content 2"
    When I edit test "[TEST] Test content 1"
    Then I fill in "Breadcrumb Parent" with "[TEST] Test content"
    Then I press the "Save" button
    When I send a GET request to "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000002?site=999991"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "data.attributes.breadcrumb_field" should exist
    And the JSON node "data.attributes.breadcrumb_field[0].url" should be equal to "/test-test-content"
    And the JSON node "data.attributes.breadcrumb_field[0].name" should be equal to "[TEST] Test content"
    And the JSON node "data.attributes.breadcrumb_field[1].url" should be equal to "/test-test-content-1"
    And the JSON node "data.attributes.breadcrumb_field[1].name" should be equal to "[TEST] Test content 1"
    And the JSON node "data.attributes.breadcrumb_field[2].url" should be equal to "/test-test-content-2"
    And the JSON node "data.attributes.breadcrumb_field[2].name" should be equal to "[TEST] Test content 2"

    When I edit test "[TEST] Test content 4"
    Then I fill in "Breadcrumb Parent" with "[TEST] Test content 2"
    Then I press the "Save" button

    When I edit test "[TEST] Test content 5"
    Then I fill in "Breadcrumb Parent" with "[TEST] Test content 4"
    Then I press the "Save" button
    When I send a GET request to "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000005?site=999991"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "data.attributes.breadcrumb_field" should exist
    And the JSON node "data.attributes.breadcrumb_field[0].url" should be equal to "/test-test-content"
    And the JSON node "data.attributes.breadcrumb_field[0].name" should be equal to "[TEST] Test content"
    And the JSON node "data.attributes.breadcrumb_field[1].url" should be equal to "/test-test-content-1"
    And the JSON node "data.attributes.breadcrumb_field[1].name" should be equal to "[TEST] Test content 1"
    And the JSON node "data.attributes.breadcrumb_field[2].url" should be equal to "/test-test-content-2"
    And the JSON node "data.attributes.breadcrumb_field[2].name" should be equal to "[TEST] Test content 2"
    And the JSON node "data.attributes.breadcrumb_field[3].url" should be equal to "/test-test-content-4"
    And the JSON node "data.attributes.breadcrumb_field[3].name" should be equal to "[TEST] Test content 4"
    And the JSON node "data.attributes.breadcrumb_field[4].url" should be equal to "/test-test-content-5"
    And the JSON node "data.attributes.breadcrumb_field[4].name" should be equal to "[TEST] Test content 5"

    When I edit test "[TEST] Test content 6"
    Then I fill in "Breadcrumb Parent" with "[TEST] Test content 2"
    Then I press the "Save" button
    When I send a GET request to "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000006?site=999991"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "data.attributes.breadcrumb_field" should exist
    And the JSON node "data.attributes.breadcrumb_field[0].url" should be equal to "/test-test-content"
    And the JSON node "data.attributes.breadcrumb_field[0].name" should be equal to "[TEST] Test content"
    And the JSON node "data.attributes.breadcrumb_field[1].url" should be equal to "/test-test-content-1"
    And the JSON node "data.attributes.breadcrumb_field[1].name" should be equal to "[TEST] Test content 1"
    And the JSON node "data.attributes.breadcrumb_field[2].url" should be equal to "/test-test-content-2"
    And the JSON node "data.attributes.breadcrumb_field[2].name" should be equal to "[TEST] Test content 2"
    And the JSON node "data.attributes.breadcrumb_field[3].url" should be equal to "/test-test-content-6"
    And the JSON node "data.attributes.breadcrumb_field[3].name" should be equal to "[TEST] Test content 6"


    When I go to "node/999912/delete"
    Then I press the "Delete" button
    Then I send a GET request to "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000005?site=999991"
    And the JSON node "data.attributes.breadcrumb_field[0].url" should be equal to "/test-test-content-4"
    And the JSON node "data.attributes.breadcrumb_field[0].name" should be equal to "[TEST] Test content 4"
    And the JSON node "data.attributes.breadcrumb_field[1].url" should be equal to "/test-test-content-5"
    And the JSON node "data.attributes.breadcrumb_field[1].name" should be equal to "[TEST] Test content 5"
