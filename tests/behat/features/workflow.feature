@tide
Feature: Workflow states and transitions

  Ensure that workflow states and transitions are configured as expected

  @api
  Scenario: Workflow states are available
    Given I am logged in as a user with the "administer workflows" permission
    When I go to "admin/config/workflow/workflows/manage/editorial"
    Then the "#edit-states-container" element should contain "Draft"
    And the "#edit-states-container" element should contain "Needs Review"
    And the "#edit-states-container" element should contain "Published"
    And the "#edit-states-container" element should contain "Archived"
    And the "#edit-states-container" element should contain "Archive pending"

    And the "#edit-transitions-container" element should contain "Archive pending"
    And the "#edit-transitions-container" element should contain "Create New Draft"
    And the "#edit-transitions-container" element should contain "Needs Review"
    And the "#edit-transitions-container" element should contain "Publish"
    And the "#edit-transitions-container" element should contain "Archive"
    And the "#edit-transitions-container" element should contain "Restore to Draft"
    And the "#edit-transitions-container" element should contain "Restore"

  @api
  Scenario: Approver transitions Test content through states:
  Draft -> Needs Review -> Published -> Archived -> Draft -> Published -> Archived -> Published

    Given I am logged in as a user with the Approver role
    When I go to "node/add/test"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"

    # Populate content fields and create content piece in Draft.
    When I fill in "Title" with "[TEST] Test title"
    And I fill in "Body" with "Test body content"
    And I select "Draft" from "Save as"
    And I press "Save"
    And I save screenshot
    Then I should see the success message "[TEST] Test title has been created."
    And I should see a "article.node--unpublished" element

    # Change state from Draft to Needs Review.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Needs Review" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Change state from Needs Review to Published.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should not contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Published" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should not see a "article.node--unpublished" element

    # Change state from Published to Archived.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should not contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Published"
    And the "#edit-moderation-state-0-state" element should contain "Archived"
    And I select "Archived" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Restore from Archived to Draft.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should not contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Draft" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Change state from Draft to Published.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Published" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should not see a "article.node--unpublished" element

    # Change state from Published to Archived.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should not contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Published"
    And the "#edit-moderation-state-0-state" element should contain "Archived"
    And I select "Archived" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Restore from Archived to Published.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should not contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Published" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should not see a "article.node--unpublished" element

  @api
  Scenario: Editor transitions Test content through states:
  Draft -> Draft -> Needs Review -> Archive pending -> Needs Review

    Given I am logged in as a user with the Editor role
    When I go to "node/add/test"
    And the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Archive pending"
    And the "#edit-moderation-state-0-state" element should not contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"

    # Populate content fields and create content piece in Draft.
    When I fill in "Title" with "[TEST] Test title"
    And I fill in "Body" with "Test body content"
    And I select "Draft" from "Save as"
    And I press "Save"
    And I save screenshot
    Then I should see the success message "[TEST] Test title has been created."
    And I should see a "article.node--unpublished" element

    # Change state from Draft to Draft.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Archive pending"
    And the "#edit-moderation-state-0-state" element should not contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"

    And I select "Draft" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Change state from Draft to Needs Review.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Archive pending"
    And the "#edit-moderation-state-0-state" element should not contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Needs Review" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Change state from Needs Review to Archive Pending.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Archive pending"
    And the "#edit-moderation-state-0-state" element should not contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Archive pending" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Change state from Archive Pending to Needs Review.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should not contain "Archive pending"
    And the "#edit-moderation-state-0-state" element should not contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Needs Review" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

  @api
  Scenario: Editor transitions Test content from Draft to Archive pending:
  Draft -> Archive pending -> Draft

    Given I am logged in as a user with the Editor role
    When I go to "node/add/test"
    And the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Archive pending"
    And the "#edit-moderation-state-0-state" element should not contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"

    # Populate content fields and create content piece in Draft.
    When I fill in "Title" with "[TEST] Test title"
    And I fill in "Body" with "Test body content"
    And I select "Draft" from "Save as"
    And I press "Save"
    And I save screenshot
    Then I should see the success message "[TEST] Test title has been created."
    And I should see a "article.node--unpublished" element

    # Change state from Draft to Archive pending.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should contain "Archive pending"
    And the "#edit-moderation-state-0-state" element should not contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Archive pending" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Change state from Archive pending to Draft.
    When I edit test "[TEST] Test title"
    Then the response status code should be 200
    And the "#edit-moderation-state-0-state" element should contain "Draft"
    And the "#edit-moderation-state-0-state" element should contain "Needs Review"
    And the "#edit-moderation-state-0-state" element should not contain "Archive pending"
    And the "#edit-moderation-state-0-state" element should not contain "Published"
    And the "#edit-moderation-state-0-state" element should not contain "Archived"
    And I select "Draft" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Test title has been updated."
    And I should see a "article.node--unpublished" element

  @api
  Scenario: Editor creates draft content and sends to Review, Approver reviews and publishes.
    Given I am logged in as a user with the Editor role
    When I go to "node/add/test"
    And the response status code should be 200

    # Populate content fields and create content piece in Draft.
    When I fill in "Title" with "[TEST] Editor Test title"
    And I fill in "Body" with "Test body content"
    And I select "Draft" from "Save as"
    And I press "Save"
    And I save screenshot
    Then I should see the success message "[TEST] Editor Test title has been created."
    And I should see a "article.node--unpublished" element

    # Change state from Draft to Needs Review.
    When I edit test "[TEST] Editor Test title"
    Then the response status code should be 200
    And I select "Needs Review" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Editor Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Approver can see the unpublished node in Content Overview.
    Given I am logged in as a user with the Approver role
    When I go to "admin/content"
    And the response status code should be 200
    And I should see the text "[TEST] Editor Test title"
    And I should see the text "Unpublished" in the "[TEST] Editor Test title" row

    When I go to "admin/content/moderated"
    And the response status code should be 200
    And I should see the text "[TEST] Editor Test title"
    And I should see the text "Needs Review" in the "[TEST] Editor Test title" row

    # Approver sends back to Draft.
    When I edit test "[TEST] Editor Test title"
    Then the response status code should be 200
    And I select "Draft" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Editor Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Editor send for Review.
    Given I am logged in as a user with the Editor role
    When I edit test "[TEST] Editor Test title"
    Then the response status code should be 200
    And I select "Needs Review" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Editor Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Approver reviews and publishes.
    Given I am logged in as a user with the Approver role
    When I edit test "[TEST] Editor Test title"
    Then the response status code should be 200
    And I select "Published" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Editor Test title has been updated."
    And I should not see a "article.node--unpublished" element

    # Editor send request to archive content.
    Given I am logged in as a user with the Editor role
    When I edit test "[TEST] Editor Test title"
    Then the response status code should be 200
    And I select "Archive pending" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Editor Test title has been updated."
    And I should see a "article.node--unpublished" element

    # Approver reviews and archive.
    Given I am logged in as a user with the Approver role
    When I edit test "[TEST] Editor Test title"
    Then the response status code should be 200
    And I select "Archived" from "Change to"
    And I press "Save"
    Then I should see the success message "[TEST] Editor Test title has been updated."
    And I should see a "article.node--unpublished" element

  @api @skipped
  Scenario: Users with permission to Archive content can use Archive operation on content
    Given test content:
      | title                       | body | moderation_state |
      | [TEST] Published article    | test | published        |
    # Verify the Archive operation link is available.
    Given I am logged in as a user with the administrator role
    And I go to "admin/content"
    Then the ".views-field views-field-operations" element should contain "Archive"
    # Check the action.
    When I click "Archive" in the "[TEST] Published article" row
    Then I should see "This action will unpublish the content."
    And I press "Archive"
    Then I should see the success message containing "[TEST] Archived article"
    # Verify the status of the
    When I edit test "[TEST] Published article"
    Then the response status code should be 200
    And the "Current state" element should contain "Archived"

  @api
  Scenario: Previewer has access to unpublished content

    Given test content:
      | title                       | body | moderation_state |
      | [TEST] Draft article        | test | draft            |
      | [TEST] Needs review article | test | needs_review     |
      | [TEST] Published article    | test | published        |
      | [TEST] Archived article     | test | archived         |

    When I am logged in as a user with the Previewer role
    And I go to "node/add/test"
    And the response status code should not be 200

    When I visit test "[TEST] Draft article"
    And the response status code should be 200
    When I visit test "[TEST] Needs review article"
    And the response status code should be 200
    When I visit test "[TEST] Published article"
    And the response status code should be 200
    When I visit test "[TEST] Archived article"
    And the response status code should be 200
