@tide
Feature: Webform "Content Rating" exists.

  Ensure that the 'Content Rating' webform exists

  @api @nosuggest
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "administer webform" permission
    When I visit "admin/structure/webform"
    And save screenshot

    And I see the text "Was this page helpful"
