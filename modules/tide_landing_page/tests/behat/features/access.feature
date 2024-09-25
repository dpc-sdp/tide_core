@tide
Feature: Access to Landing Page content type

  Ensure that Landing Page content access permissions are set correctly
  for designated roles.

  @api
  Scenario Outline: Users have access to create Landing Page content
    Given I am logged in as a user with the "<role>" role
    When I go to "node/add/landing_page"
    Then I should get a "<response>" HTTP response
    And save screenshot
    Examples:
      | role               | response |
      | authenticated user | 404      |
      | administrator      | 200      |
      | editor             | 200      |
      | approver           | 200      |
      | previewer          | 404      |
