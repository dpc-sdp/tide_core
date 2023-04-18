@tide
Feature: Roles

  Ensure that all roles exist on the site.

  @api
  Scenario Outline: User with assigned role visits homepage
    Given I am logged in as a user with the "<role>" role
    And I go to the homepage
    And I save screenshot
    Examples:
      | role            |
      | administrator   |
      | site_admin      |
      | approver        |
      | external_editor |
      | editor          |
      | previewer       |
