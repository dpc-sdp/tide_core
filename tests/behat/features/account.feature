@tide
Feature: Access settings

  Ensure that people account settings configuration are set correctly.

  @api @javascript
  Scenario: User creation require admin approval.
    Given I am logged in as a user with the "administrator" role
    When I go to "/admin/config/people/accounts"
    And I see field "edit-user-register-visitors-admin-approval"
    And the "edit-user-register-visitors-admin-approval" checkbox should be checked
    Then I save screenshot