@jsonapi @install:tide_site
Feature: Route lookup

  @api @suggest
  Scenario: Request to route lookup API to find a route by existing alias
    And vocabulary "sites" with name "Sites" exists
    And sites terms:
      | name            | parent      | tid   |
      | Test Site 1     | 0           | 10001 |
      | Test Section 11 | Test Site 1 | 10011 |
      | Test Site 2     | 0           | 10002 |

    Given test content:
      | title                         | body | moderation_state | path                    | field_node_primary_site | field_node_site              |
      | [TEST] Article with no site   | test | published        | /test-article-no-site   |                         |                              |
      | [TEST] Article with one site  | test | published        | /test-article-one-site  | Test Site 1             | Test Site 1, Test Section 11 |
      | [TEST] Article with two sites | test | published        | /test-article-two-sites | Test Site 2             | Test Site 2, Test Site 1     |

    And I am an anonymous user

    When I request "api/v1/route?path=/test-article-no-site" using HTTP GET
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

    When I request "api/v1/route?path=/test-article-one-site" using HTTP GET
    Then the response code is 404
    And the response body contains JSON:
      """
      {
        "errors": "@variableType(array)"
      }
      """
    And the response body matches:
      """
      /^(?!.*"data":).*/s
      """

    When I request "api/v1/route?path=/test-article-one-site&site=10001" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "data": {
          "attributes": {
            "section": "@regExp(/10011/)"
          }
        }
      }
      """
    # Negative match to ensure 10001 is NOT in the section string
    And the response body matches:
      """
      /^(?!.*"section":"[^"]*10001").*/s
      """
    And the response body matches:
      """
      /^(?!.*"errors":).*/s
      """

    When I request "api/v1/route?path=/test-article-one-site&site=10011" using HTTP GET
    Then the response code is 404
    And the response body contains JSON:
      """
      {
        "errors": [
          { "title": "@regExp(/Path not found./)" }
        ]
      }
      """
    And the response body matches:
      """
      /^(?!.*"data":).*/s
      """

    When I request "api/v1/route?path=/test-article-two-sites&site=10001" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "data": {
          "attributes": {
            "section": "@regExp(/10001/)"
          }
        }
      }
      """
    And the response body matches:
      """
      /^(?!.*"errors":).*/s
      """

    When I request "api/v1/route?path=/test-article-two-sites&site=10002" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "data": {
          "attributes": {
            "section": "@regExp(/10002/)"
          }
        }
      }
      """
    And the response body matches:
      """
      /^(?!.*"errors":).*/s
      """

  @api @suggest
  Scenario: Request to route lookup API to find the homepage of a site
    Given sites terms:
      | name        | parent | tid   | field_site_domains |
      | Test Site 3 | 0      | 10003 | test.site.local    |

    Given test content:
      | title           | moderation_state | field_node_primary_site | field_node_site | uuid                                 |
      | [TEST] Homepage | published        | Test Site 3             | Test Site 3     | 00000000-1111-2222-3333-0123456789ab |

    Given I am logged in as a user with the "administer taxonomy" permission
    When I visit "/taxonomy/term/10003/edit"
    Then I fill in "Homepage" with "[TEST] Homepage"
    And I press the Save button

    Given I am an anonymous user
    When I request "api/v1/route?path=/&site=10003" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "data": {
          "attributes": {
            "entity_type": "node",
            "bundle": "test",
            "uuid": "@regExp(/00000000-1111-2222-3333-0123456789ab/)",
            "section": "10003"
          }
        }
      }
      """
    And the response body matches:
      """
      /^(?!.*"errors":).*/s
      """
