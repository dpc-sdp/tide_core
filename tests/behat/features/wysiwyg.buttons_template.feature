@tide
Feature: WYSIWYG Blockquote template

  As an Editor, I would like to add text quotes using WYSIWYG editor.

  @api
  Scenario: User with permission can see WYSIWYG buttons template.
    Given I am logged in as a user with the "administrator" role
    And I visit "admin/config/content/ckeditor-templates"
    Then I should see "Primary Button"
    And I should see "Secondary Button"

  @api @javascript @skipped
  Scenario Outline: User with permission can add button WYSIWYG editor.
    Given I am logged in as a user with the "Approver" role
    When I go to "node/add/test"
    And I fill in "Title" with "[TEST] Test Primary Button"
    And I click "Insert template"
    And I wait for 2 seconds
    And I click "<button>"
    And I wait for 2 seconds
    And I press "Save"
    Then the response should contain "<markup>"
    Examples:
      | button           | markup                                                                                         |
      | Primary Button   | <a class=\"button\" href=\"Primary-button-link\">Primary button text</a>                       |
      | Secondary Button | <a class=\"button button--secondary\" href=\"Secondary-button-link\">Secondary button text</a> |
