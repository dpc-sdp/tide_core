@tide @install:tide_cms_support
Feature: CMS Support

  @api
  Scenario Outline: User logs into the CMS and clicks on the Manage tab
    Given I am logged in as a user with the "<role>" role
    Then I should find menu item text matching "Help"
    Examples:
      | role            |
      | administrator   |
      | site_admin      |
      | approver        |
      | contributor     |
      | editor          |
