@tide @jsonapi
Feature: Link Enhancer

  @api @nosuggest
  Scenario: Request to "test" individual endpoint with results.
    Given test content:
      | title         | path         | moderation_state | uuid                                | nid    | field_test_link             |
      | [TEST] Page 1 | /test-page-1 | published        | 99999999-aaaa-bbbb-ccc-000000000001 | 999991 | Page 2 - entity:node/999992 |
      | [TEST] Page 2 | /test-page-2 | published        | 99999999-aaaa-bbbb-ccc-000000000002 | 999992 |                             |

    Given I am an anonymous user
    When I request "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000001" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "links": {
          "self": {
            "href": "@regExp(/api\\/v1\\/node\\/test/)"
          }
        },
        "data": {
          "type": "node--test",
          "id": "99999999-aaaa-bbbb-ccc-000000000001",
          "attributes": {
            "field_test_link": {
              "uri": "entity:node/test/99999999-aaaa-bbbb-ccc-000000000002",
              "title": "Page 2",
              "url": "@variableType(string)",
              "entity": {
                "uri": "entity:node/999992",
                "entity_type": "node",
                "entity_id": "999992",
                "bundle": "test",
                "uuid": "99999999-aaaa-bbbb-ccc-000000000002"
              }
            }
          }
        }
      }
      """
