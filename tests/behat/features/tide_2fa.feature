@tide
Feature: Force 2FA setup

  Ensure that all users setup 2FA.

  @api
  Scenario Outline: Users have access to 2FA settings page
    Given I am logged in as a user with the "<role>" role
    When I go to "/admin/config/people/tfa"
    Then I should get a "<response>" HTTP response
    Examples:
      | role               | response |
      | authenticated user | 404      |
      | administrator      | 200      |
      | site_admin         | 200      |
      | contributor        | 404      |
      | editor             | 404      |
      | approver           | 404      |
      | previewer          | 404      |

  @api
  Scenario Outline: 2FA settings page is set disabled by default
    Given I am logged in as a user with the "<role>" role
    When I go to "/admin/config/people/tfa"
    And I see the text "TFA Settings"
    And I see field "edit-tfa-enabled"
    And the "edit-tfa-enabled" checkbox should not be checked
    And I see field "edit-tfa-forced"
    And the "edit-tfa-forced" checkbox should be checked
    Then I save screenshot
    Examples:
      | role               |
      | administrator      |
      | site_admin         |
