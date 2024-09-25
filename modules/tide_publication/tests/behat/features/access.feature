Feature: Access to Publication content type

  Ensure that Publication content access permissions are set correctly
  for designated roles.

  @api
  Scenario Outline: Users have access to create Publication content
    Given I am logged in as a user with the "<role>" role
    When I go to "node/add/publication"
    Then I should get a "<response>" HTTP response
    And save screenshot
    Examples:
      | role               | response |
      | authenticated user | 404      |
      | administrator      | 200      |
      | site_admin         | 200      |
      | editor             | 200      |
      | approver           | 200      |
      | previewer          | 404      |

  @api
  Scenario Outline: Users have access to create Publication Page content
    Given I am logged in as a user with the "<role>" role
    When I go to "node/add/publication_page"
    Then I should get a "<response>" HTTP response
    And save screenshot
    Examples:
      | role               | response |
      | authenticated user | 404      |
      | administrator      | 200      |
      | site_admin         | 200      |
      | editor             | 200      |
      | approver           | 200      |
      | previewer          | 404      |
