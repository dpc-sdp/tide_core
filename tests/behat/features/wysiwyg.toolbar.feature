@tide
Feature: WYSIWYG toolbar configuration

  As an Approver or Editor, I want to to have access to all configured WYSIWYG buttons.

  @api @javascript @trait:VisibilityTrait
  Scenario: Rich Text format has expected WYSIWYG buttons
    Given I am logged in as a user with the "create test content, use text format rich_text" permissions
    When I go to "node/add/test"
    Then I save screenshot
    Then I should see a visible "#cke_edit-body-0-value" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__bold" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__italic" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__strike" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_combo__styles" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__superscript" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__subscript" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__removeformat" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__copy " element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__cut" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__paste" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__pastetext" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__drupallink" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__drupalunlink" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__bulletedlist_icon" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__numberedlist_icon" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__blockquote" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__horizontalrule" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__justifyleft" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__justifycenter" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__justifyright" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__justifyblock" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__outdent" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__indent" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_combo__format" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__undo" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__redo" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__showblocks" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__source" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__templateselector" element
    And I should not see a visible "#cke_edit-body-0-value .cke_top .cke_button__wenzgmap" element

  @api @javascript
  Scenario: Admin Text format has expected WYSIWYG buttons
    Given I am logged in as a user with the "create test content, use text format admin_text" permissions
    When I go to "node/add/test"
    Then I save screenshot
    Then I should see a visible "#cke_edit-body-0-value" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__bold" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__italic" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__strike" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_combo__styles" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__superscript" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__subscript" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__removeformat" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__copy " element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__cut" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__paste" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__pastetext" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__drupallink" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__drupalunlink" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__bulletedlist_icon" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__numberedlist_icon" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__blockquote" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__horizontalrule" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__justifyleft" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__justifycenter" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__justifyright" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__justifyblock" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__outdent" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__indent" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_combo__format" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__undo" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__redo" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__showblocks" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__source" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__templateselector" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__wenzgmap" element
    And I should see a visible "#cke_edit-body-0-value .cke_top .cke_button__iframe" element

  @api @javascript
  Scenario: Plain Text format has no WYSIWYG buttons
    Given I am logged in as a user with the "create test content" permissions
    When I go to "node/add/test"
    Then I save screenshot
    And I should not see a visible "#cke_edit-body-0-value" element