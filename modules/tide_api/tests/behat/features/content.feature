@tide @jsonapi
Feature: Page

  @api @nosuggest
  Scenario: Request to "test" collection endpoint
    Given I am an anonymous user
    When I request "api/v1/node/test" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "jsonapi": {
          "version": "1.0"
        },
        "links": {
          "self": {
            "href": "@regExp(/api\\/v1\\/node\\/test/)"
          }
        },
        "meta": {
          "count": "@variableType(integer)"
        },
        "data": "@variableType(array)"
      }
      """

  @api @nosuggest
  Scenario: Request to "test" individual/collection endpoint with results.
    Given test content:
    | title             | path             | moderation_state | uuid                                 |
    | [TEST] Page title | /test-page-alias | published        | 99999999-aaaa-bbbb-ccc-000000000000 |

    Given I am an anonymous user

    # --- FIRST REQUEST: Individual Node ---
    When I request "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000000" using HTTP GET
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
          "id": "99999999-aaaa-bbbb-ccc-000000000000"
        }
      }
      """

    # --- SECOND REQUEST: Invalid Include ---
    # Note: Imbo doesn't have a direct "node should not exist" step easily. 
    # We use the full structure match to ensure 'included' is absent.
    When I request "api/v1/node/test/99999999-aaaa-bbbb-ccc-000000000000?include=abcd1234" using HTTP GET
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
          "id": "99999999-aaaa-bbbb-ccc-000000000000"
        }
      }
      """
    # To strictly verify 'included' is missing, we use a regex on the whole body
    And the response body matches:
      """
      /^(?!.*"included":).*/s
      """

    # --- THIRD REQUEST: Collection with Sort ---
    When I request "api/v1/node/test?sort=-created" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "jsonapi": { "version": "1.0" },
        "links": { "self": { "href": "@regExp(/api\\/v1\\/node\\/test/)" } },
        "meta": { "count": "@variableType(integer)" },
        "data": [
          {
            "type": "node--test",
            "id": "@variableType(string)",
            "attributes": {
              "title": "[TEST] Page title"
            }
          }
        ]
      }
      """
