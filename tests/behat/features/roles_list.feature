@tide
Feature: Roles

  Ensure that users's role are listed in the profile page

  @api
  Scenario Outline: User with assigned roles visits profile page
    Given I am logged in as a user with the "<role>" role
    And I visit "/user"
    Then the ".views-label-sorted-roles-views-field" element should contain "Roles"
    Examples:
      | role          |
      | administrator |
      | site_admin    |
      | approver      |
      | editor        |
      | previewer     |
      | site_auditor  |
