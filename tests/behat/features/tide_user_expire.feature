@tide_core
Feature: Data Driven Fields for the user expire

  Ensure that user expire email notification template exist.

  @api
  Scenario: The user Administrator role can see email notification template.
    Given I am logged in as a user with the "Administrator" role
    When I visit "/admin/config/people/user-expire"
    Then I should see "Email notification"
    And I should see an "input#edit-tide-user-expire-from-email" element
    And I should see an "input#edit-tide-user-expire-email-subject" element
    And I should see an "textarea#edit-tide-user-expire-email-content" element
    Then I save screenshot
