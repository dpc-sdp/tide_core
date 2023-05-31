@tide
Feature: Embedded Maps Feature

  As a user with access to Admin Text and Rich Text formats,
  I would like to embed Google Map using WYSIWYG editor.

  @api @javascript @skipped
  Scenario: Admin Text formats provide embedding of a Google Map using WYSIWYG editor.
    Given I am logged in as a user with the "Approver" role
    When I go to "node/add/test"
    And I fill in "Title" with "[TEST] Test Maps"
    And I select "Admin Text" from "Text format"
    And I wait for AJAX to finish
    And I click "Insert a google map"
    And I wait for AJAX to finish
    And I fill in "Please enter your central map address" with "159 Victoria Pde, Collingwood, Victoria, Australia"
    And I fill in "Map Width (px)" with "500"
    And I fill in "Map Height (px)" with "500"
    And I click "OK"
    And I wait for AJAX to finish
    # No Moderation.
    And I press "Save"
    # With Moderation.
    # And I press "Save and Published"
    Then the response should contain "<iframe frameborder=\"0\" height=\"500\" scrolling=\"no\" src=\"//maps.google.com/maps?q=159 Victoria Pde, Collingwood, Victoria, Australia&amp;num=1&amp;t=m&amp;ie=UTF8&amp;z=14&amp;output=embed\" width=\"500\"></iframe>"