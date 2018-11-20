@tide
Feature: Check that Timelines paragraphs.

  Ensure Timeline and Timelines paragraphs and their fields exist.

  @api
  Scenario: Timeline paragraph exists
    Given I am logged in as a user with the "administrator" role
    When I go to "admin/structure/paragraphs_type"
    Then I should see the text "Timeline" in the "timeline" row
    And I should see the text "Timelines" in the "timelines" row
