@tide @jsonapi @install:jsonapi_menu_items
Feature: JSONAPI Menu Items

  @api @nosuggest
  Scenario: Request to ensure menu items endpoint is available in tide_api

    Given I am an anonymous user

    # Menu items without a menu supplied should be 404
    When I request "/api/v1/menu_items" using HTTP GET
    Then the response code is 404

    # Menu items with a test menu setup
    When I request "/api/v1/menu_items/admin" using HTTP GET
    Then the response code is 200
    And the response body contains JSON:
      """
      {
        "data": "@variableType(array)"
      }
      """
