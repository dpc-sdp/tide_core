@jsonapi
Feature: Access restrictions

  Ensure the roles has expected restrictions.

  @api
  Scenario: Approver plus role should not have access to create new user.
    Given I am logged in as a user with the "approver_plus" role
    When I go to "admin/people/create"
    Then I should get a 404 HTTP response
