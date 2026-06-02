@tide @jsonapi
Feature: Route lookup

  @api @nosuggest
  Scenario: Request to route lookup API to find a route by non-existing alias
    Given I am an anonymous user
    When I request "api/v1/route?path=/test-non-existing-alias" using HTTP GET
    Then the response code is 404
    And the response body contains JSON:
      """
      {
        "links": {
          "self": { "href": "@regExp(/api\\/v1\\/route/)" }
        },
        "errors": [
          { "title": "@regExp(/Path not found./)" }
        ]
      }
      """

  Scenario: Request to route lookup API without a parameter specified.
    Given I am an anonymous user
    When I request "api/v1/route" using HTTP GET
    Then the response code is 400
    And the response body contains JSON:
      """
      {
        "links": {
          "self": { "href": "@regExp(/api\\/v1\\/route/)" }
        },
        "errors": [
          { "title": "@regExp(/URL query parameter 'path' is required./)" }
        ]
      }
      """

  @api @nosuggest
  Scenario: Request to route lookup API to find a route by existing alias
    Given test content:
      | title                    | body | moderation_state | path                    |
      | [TEST] Draft article     | test | draft            | /test-draft-article     |
      | [TEST] Published article | test | published        | /test-published-article |
    And I am an anonymous user

    # Anonymous users should not have access to unpublished nodes.
    When I request "api/v1/route?path=/test-draft-article" using HTTP GET
    Then the response code is 403
    And the response body contains JSON:
      """
      {
        "links": {
          "self": { "href": "@regExp(/api\\/v1\\/route/)" }
        },
        "errors": [
          { "title": "@regExp(/Permission denied./)" }
        ]
      }
      """

    # Anonymous users should have access to published nodes.
    When I request "api/v1/route?path=/test-published-article" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "links": {
          "self": { "href": "@regExp(/api\\/v1\\/route/)" }
        },
        "data": {
          "attributes": {
            "bundle": "@regExp(/test/)",
            "endpoint": "@regExp(/api\\/v1\\/node\\/test\\//)"
          }
        }
      }
      """
    # Verify 'errors' does not exist
    And the response body matches:
      """
      /^(?!.*"errors":).*/s
      """

    Then the moderation state of test "[TEST] Published article" changes from "published" to "archived"
    And the moderation state of test "[TEST] Draft article" changes from "draft" to "published"
    And the cache has been cleared

    When I request "api/v1/route?path=/test-published-article" using HTTP GET
    Then the response code is 403
    And the response body contains JSON:
      """
      {
        "errors": [
          { "title": "@regExp(/Permission denied./)" }
        ]
      }
      """

    When I request "api/v1/route?path=/test-draft-article" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "data": "@variableType(object)"
      }
      """
    And the response body matches:
      """
      /^(?!.*"errors":).*/s
      """
