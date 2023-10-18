@tide
Feature: Force 2FA setup

  Ensure that all users setup 2FA.

  @api
  Scenario Outline: Users login to CMS are forced to setup 2FA
    Given I am logged in as a user with the "<role>" role
    When I go to "<path>"
    Then I should see the text "You need to enable Two Factor Authentication."
    And I save screenshot
    Examples:
      | role               | path          |
      # Content.
      | authenticated user | admin/content |
      | administrator      | admin/content |
      | site_admin         | admin/content |
      | contributor        | admin/content |
      | editor             | admin/content |
      | approver           | admin/content |
      | previewer          | admin/content |
