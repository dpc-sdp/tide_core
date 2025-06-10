@jsonapi
Feature: Access restrictions

  Ensure the roles has expected restrictions.

  @api
  Scenario: Site admin role should have access to site alert.
    Given I am logged in as a user with the "site_admin" role
    When I go to "admin/config/system/site-alerts"
    Then I should get a 200 HTTP response
