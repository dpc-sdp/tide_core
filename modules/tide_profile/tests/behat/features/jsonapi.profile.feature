@tide @jsonapi @suggest
Feature: JSON API Profile

  Ensure that the Profile nodes are exposed via JSON API.

  @api
  Scenario: Request to "profile" collection endpoint
    Given I am an anonymous user
    When I send a GET request to "api/v1/node/profile"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "jsonapi.version" should be equal to "1.0"
    And the JSON node "links" should exist
    And the JSON node "links.self.href" should contain "api/v1/node/profile"
    And the JSON node "meta.count" should exist
    And the JSON node "data" should exist

  @api
  Scenario: Request to "profile" individual/collection endpoint with results.
    Given profile content:
      | title                | path                | moderation_state | uuid                                |
      | [TEST] Profile title | /profile-test-alias | published        | 99999999-aaaa-bbbb-ccc-000000000001 |

    Given I am an anonymous user

    When I send a GET request to "api/v1/node/profile/99999999-aaaa-bbbb-ccc-000000000001"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "links" should exist
    And the JSON node "links.self.href" should contain "api/v1/node/profile"
    And the JSON node "data" should exist
    And the JSON node "data.type" should be equal to "node--profile"
    And the JSON node "data.id" should be equal to "99999999-aaaa-bbbb-ccc-000000000001"

    When I send a GET request to "api/v1/node/profile?sort=-created"
    Then the response code should be 200
    And the response should be in JSON
    And the JSON node "jsonapi.version" should be equal to "1.0"
    And the JSON node "links" should exist
    And the JSON node "links.self.href" should contain "api/v1/node/profile"
    And the JSON node "meta.count" should exist
    And the JSON node "data" should exist
    And the JSON node "data[0].type" should be equal to "node--profile"
    And the JSON node "data[0].id" should exist
    And the JSON node "data[0].attributes.title" should be equal to "[TEST] Profile title"
