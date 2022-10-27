@tide_core
Feature: Data Driven Fields for the user account

  Ensure that user account has business fields and right field permissions.

  @api
  Scenario: The user editor role can see business name, phone but not notes fields.
    Given I am logged in as a user with the "editor" role
    When I visit "/user"
    Then I should see "Edit"
    And I click "Edit"
    And I should see an "input#edit-field-business-name-0-value" element
    And I should see an "input#edit-field-business-name-0-value" element
    And I should not see a "textarea#edit-field-notes-0-value" element
    And save screenshot

  @api
  Scenario: The user site admin role can see business name, phone and notes fields.
    Given I am logged in as a user with the "Site Admin" role
    When I visit "/user"
    Then I should see "Edit"
    And I click "Edit"
    And I should see an "input#edit-field-business-name-0-value" element
    And I should see an "input#edit-field-business-name-0-value" element
    And I should see an "textarea#edit-field-notes-0-value" element
    And save screenshot

  @api
  Scenario: The user administrator role can see business name, phone and notes fields.
    Given I am logged in as a user with the "Administrator" role
    When I visit "/user"
    Then I should see "Edit"
    And I click "Edit"
    And I should see an "input#edit-field-business-name-0-value" element
    And I should see an "input#edit-field-business-name-0-value" element
    And I should see an "textarea#edit-field-notes-0-value" element
    And save screenshot
