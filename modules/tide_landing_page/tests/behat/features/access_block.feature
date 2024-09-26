@tide
Feature: Check access to block layout

  @api
  Scenario Outline: Approver, Editors and Site Admins can't have access to block layout
    Given I am logged in as a user with the "<role>" role
    When I go to "admin/structure/block/"
    Then I should get a "<response>" HTTP response
    And save screenshot
    Examples:
      | role               | response |
      | administrator      | 200      |
      | editor             | 404      |
      | approver           | 404      |
      | site_admin         | 404      |
