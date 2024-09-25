@tide
Feature: Fields for Test media entity

  Ensure that Test media has the expected fields.

  @api @nosuggest
  Scenario: The media entity the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create media" permission
    When I visit "media/add/testmedia"
    And save screenshot
    Then I see field "Name"
    And I should see an "input#edit-name-0-value.required" element
    Then I see field "File"
    And I should see an "input#edit-submit" element
