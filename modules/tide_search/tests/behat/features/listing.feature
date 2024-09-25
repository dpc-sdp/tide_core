Feature: Ensure the Search Listing content type is installed and available

  @api @listing
  Scenario: Ensure that the search listing can be added via create content
    Given I am logged in as a user with the "administrator" role
    When I go to "/node/add"
    And I should see the text "Search Listing"

  @api @listing
  Scenario: Assert that we can save search listing content.
    Given topic terms:
      | name       | parent |
      | Test Topic | 0      |
    And tags terms:
      | name     | parent |
      | Test Tag | 0      |
    And tide_search_listing content:
      | title                  | body:value | moderation_state | field_topic | field_tags |
      | SEARCHLISTINGPUBLISHED | TESTBODY   | published        | Test Topic  | Test Tag   |
    And I am logged in as a user with the "administrator" role
    Then I edit tide_search_listing "SEARCHLISTINGPUBLISHED"
    And I check "Published"
    And I press "Save"
    Then I go to "/admin/content"
    Then the response status code should be 200
    And I should see the text "SEARCHLISTINGPUBLISHED"
    And I should see the text "Search Listing"
