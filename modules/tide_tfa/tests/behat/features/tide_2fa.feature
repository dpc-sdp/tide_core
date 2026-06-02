@tide @wip
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
    Then I save screenshot
    Examples:
      | role               |
      | administrator      |
      | site_admin         |

  @api @javascript
  Scenario Outline: Enable multi-factor authentication
    Given I am logged in as an administrator
    Then I go to "/admin/config/people/tfa"
    And I click on the element "#edit-tfa-enabled"
    Then I save screenshot
    And I should see the text "Roles required to set up TFA"
    And I should see the text "Allowed Validation plugins"
    And I should see the text "TFA Email one-time password (EOTP)"
    And the "edit-tfa-allowed-validation-plugins-tfa-email-otp" checkbox should be checked
    And I should see the text "Validation Settings"
    And I should see the text "TFA Email one-time password (EOTP)"
    And the "#edit-validation-plugin-settings-tfa-email-otp-code-validity-period option[selected='selected']" element should contain "10"
    Then the "validation_plugin_settings[tfa_email_otp][email_setting][subject]" field should contain "Single Digital Presence CMS multi-factor authentication code"
    Then I press the "Save configuration" button

  @api @javascript
  Scenario Outline: Non admin user multi-factor authentication flow
    Given I am logged in as an administrator
    When I go to "/"
    And I click "Multi-factor authentication"
    Then I should see the heading "Multi-factor authentication" in the "header" region
    And I should see the text "Email verification code"
    And I click "Enable multi-factor authentication via email"
    Then I should see the heading "Multi-factor authentication setup" in the "header" region
    Then I save screenshot

  @api @javascript
  Scenario Outline: Disable multi-factor authentication
    Given I am logged in as an administrator
    Then I go to "/admin/config/people/tfa"
    And I click on the element "#edit-tfa-enabled"
    And I should not see the text "Roles required to set up TFA"
    Then I save screenshot
    Then I press the "Save configuration" button