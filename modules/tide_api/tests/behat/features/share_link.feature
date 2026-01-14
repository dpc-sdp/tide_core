@tide @jsonapi @install:tide_share_link
Feature: Share Link

  @api @nosuggest
  Scenario: Request to "test" individual endpoint with Share Link Token.
    Given test content:
      | title             | path             | moderation_state | uuid                                | nid    |
      | [TEST] Page title | /test-page-alias | draft            | 99999999-aaaa-bbbb-ccc-000000000000 | 999999 |
    Given share_link_token share_link_token entity:
      | name       | status | uid | nid    | token                               |
      | Test token | 1      | 1   | 999999 | 99999999-aaaa-bbbb-ccc-000000000001 |

    Given I am an anonymous user

    # Anon user can't see the draft.
    When I request "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000000" using HTTP GET
    Then the response code is 404

    # Share link without the correspond NID can't be retrieved.
    When I request "api/v1/share_link/99999999-aaaa-bbbb-ccc-000000000001" using HTTP GET
    Then the response code is 404

    # Share link with the correspond NID.
    When I request "api/v1/share_link/99999999-aaaa-bbbb-ccc-000000000001/999999" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "data": {
          "type": "share_link_token--share_link_token",
          "id": "99999999-aaaa-bbbb-ccc-000000000001",
          "attributes": {
            "nid": 999999
          },
          "relationships": {
            "shared_node": {
              "data": {
                "type": "node--test",
                "id": "99999999-aaaa-bbbb-ccc-000000000000"
              }
            }
          }
        }
      }
      """

    # Retrieve the draft with Share Link token header.
    Given the "X-Share-Link-Token" request header is "99999999-aaaa-bbbb-ccc-000000000001"
    And I request "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000000" using HTTP GET
    Then the response code is 200
    # Changed "contains JSON" to "contains"
    And the response body contains JSON:
      """
      {
        "data": {
          "type": "node--test",
          "id": "99999999-aaaa-bbbb-ccc-000000000000",
          "attributes": {
            "title": "[TEST] Page title"
          }
        }
      }
      """

  @api @nosuggest
  Scenario: Request to test relationship fetching with include parameter
    Given test content:
      | title             | path             | moderation_state | uuid                                | nid    |
      | [TEST] Page title | /test-page-alias | published        | 99999999-aaaa-bbbb-ccc-000000000000 | 999999 |
    Given share_link_token share_link_token entity:
      | name       | status | uid | nid    | token                               |
      | Test token | 1      | 1   | 999999 | 99999999-aaaa-bbbb-ccc-000000000001 |

    # Fetch the relationship of the node using include
    When I request "api/v1/share_link/99999999-aaaa-bbbb-ccc-000000000001/999999?include=shared_node" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "data": {
          "type": "share_link_token--share_link_token",
          "id": "99999999-aaaa-bbbb-ccc-000000000001",
          "attributes": {
            "nid": 999999
          },
          "relationships": {
            "shared_node": "@variableType(object)"
          }
        },
        "included": [
          {
            "type": "node--test",
            "id": "99999999-aaaa-bbbb-ccc-000000000000"
          }
        ]
      }
      """
