@tide
Feature: WYSIWYG Blockquote template

  As an Editor, I would like to add text quotes using WYSIWYG editor.

  @api
  Scenario: User with permission can see WYSIWYG Blockquote template.
    Given I am logged in as a user with the "administrator" role
    And I visit "admin/config/content/ckeditor-templates"
    Then I should see "Blockquote"

  @api @javascript @skipped
  Scenario: User with permission can add blockquote WYSIWYG editor.
    Given I am logged in as a user with the "Approver" role
    When I go to "node/add/test"
    And I fill in "Title" with "[TEST] Test blockquote"
    And I click "Insert template"
    And I wait for AJAX to finish
    And I click "Blockquote"
    And I wait for AJAX to finish
    And I wait for AJAX to finish
    And I press "Save"
    Then the response should contain "<blockquote class=\"quotation\">"
    And the response should contain "<p class=\"quotation__quote\">Berios sim destrum facientota nis ex eost aut prae vendis explam aliquis dolorpo rrorem reptaep elenis net.</p>"
    And the response should contain "<span class=\"quotation__author\">Her Excellency the Honourable Linda Dessau AC</span>"
    And the response should contain "<span class=\"quotation__author-title\">Governor of Victoria</span>"
