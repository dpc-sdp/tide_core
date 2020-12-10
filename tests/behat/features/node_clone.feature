@tide
Feature: Node clone test

  As an editor I can see Clone tab and creat a cloned node.

  Background:
    And test content:
      | title             | path             | moderation_state | author      | nid    | body |
      | [TEST] Page title | /test-page-alias | published        | test.editor | 999999 | Test |

  @api @javascript
  Scenario: check if the Clone tab exists and the node can be cloned.
    Given I am logged in as a user with the "site_admin" role
    Then I go to "/node/999999"
    Then I should see the link "Clone"
    And I click "Clone"
    And I press "Save"
    Then I save screenshot
    Then I should see the success message "Test Clone of [TEST] Page title (clone) has been created."
