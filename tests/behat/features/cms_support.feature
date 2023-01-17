@tide @install:toolbar
Feature: CMS Support

  @api
  Scenario Outline: User logs into the CMS and clicks on the Manage tab
    Given I am logged in as a user with the "<role>" role
    Then I should find menu item text matching "CMS Support"
    Examples:
      | role          |
      | administrator |
      | site_admin    |
      | approver      |
      | editor        |
