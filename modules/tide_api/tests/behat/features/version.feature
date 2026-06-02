@tide @jsonapi
Feature: API version

  @api @nosuggest
  Scenario: Request to API to get version
    When I request "api/v1" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "api_version": "1.0",
        "links": "@variableType(object)"
      }
      """
