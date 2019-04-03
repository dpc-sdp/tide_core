@tide
Feature: WYSIWYG toolbar configuration

  As an Approver or Editor, I want to to have access to all configured WYSIWYG buttons.

  Background:
    Given I define components:
      | Toolbar             | #cke_edit-body-0-value .cke_top                                  |
      | Bold                | #cke_edit-body-0-value .cke_top .cke_button__bold                |
      | Italic              | #cke_edit-body-0-value .cke_top .cke_button__italic              |
      | Strikethrough       | #cke_edit-body-0-value .cke_top .cke_button__strike              |
      | Styles              | #cke_edit-body-0-value .cke_top .cke_combo__styles               |
      | Superscript         | #cke_edit-body-0-value .cke_top .cke_button__superscript         |
      | Subscript           | #cke_edit-body-0-value .cke_top .cke_button__subscript           |
      | Remove format       | #cke_edit-body-0-value .cke_top .cke_button__removeformat        |
      | Copy                | #cke_edit-body-0-value .cke_top .cke_button__copy                |
      | Cut                 | #cke_edit-body-0-value .cke_top .cke_button__cut                 |
      | Paste               | #cke_edit-body-0-value .cke_top .cke_button__paste               |
      | Paste text          | #cke_edit-body-0-value .cke_top .cke_button__pastetext           |
      | Link                | #cke_edit-body-0-value .cke_top .cke_button__drupallink          |
      | Unlink              | #cke_edit-body-0-value .cke_top .cke_button__drupalunlink        |
      | Bulleted list       | #cke_edit-body-0-value .cke_top .cke_button__bulletedlist_icon   |
      | Numbered list       | #cke_edit-body-0-value .cke_top .cke_button__numberedlist_icon   |
      | Blockquote          | #cke_edit-body-0-value .cke_top .cke_button__blockquote          |
      | Table               | #cke_edit-body-0-value .cke_top .cke_button__blockquote          |
      | Horizontal rule     | #cke_edit-body-0-value .cke_top .cke_button__horizontalrule      |
      | Media browser       | #cke_edit-body-0-value .cke_top .cke_button__media_browser       |
      | Justify left        | #cke_edit-body-0-value .cke_top .cke_button__justifyleft         |
      | Justify center      | #cke_edit-body-0-value .cke_top .cke_button__justifycenter       |
      | Justify right       | #cke_edit-body-0-value .cke_top .cke_button__justifyright        |
      | Justify block       | #cke_edit-body-0-value .cke_top .cke_button__justifyblock        |
      | Outdent             | #cke_edit-body-0-value .cke_top .cke_button__outdent             |
      | Indent              | #cke_edit-body-0-value .cke_top .cke_button__indent              |
      | Format              | #cke_edit-body-0-value .cke_top .cke_combo__format               |
      | Undo                | #cke_edit-body-0-value .cke_top .cke_button__undo                |
      | Redo                | #cke_edit-body-0-value .cke_top .cke_button__redo                |
      | Show blocks         | #cke_edit-body-0-value .cke_top .cke_button__showblocks          |
      | Source              | #cke_edit-body-0-value .cke_top .cke_button__source              |
      | Templates           | #cke_edit-body-0-value .cke_top .cke_button__templateselector    |
      | Google Map          | #cke_edit-body-0-value .cke_top .cke_button__wenzgmap            |
      | Iframe              | #cke_edit-body-0-value .cke_top .cke_button__iframe              |
      | Embed Image Gallery | #cke_edit-body-0-value .cke_top .cke_button__embed_image_gallery |

  @api @javascript
  Scenario: Plain Text format has no WYSIWYG buttons
    Given I am logged in as a user with the "create test content" permissions
    When I go to "node/add/test"
    Then I don't see Toolbar

  @api @javascript
  Scenario: Rich Text format has expected WYSIWYG buttons
    Given I am logged in as a user with the "create test content, use text format rich_text" permissions
    When I go to "node/add/test"
    Then I see visible Toolbar
    And I see Bold inside of Toolbar
    And I see Italic inside of Toolbar
    And I see Strikethrough inside of Toolbar
    And I see Styles inside of Toolbar
    And I see Superscript inside of Toolbar
    And I see Subscript inside of Toolbar
    And I see Remove format inside of Toolbar
    And I see Copy inside of Toolbar
    And I see Cut inside of Toolbar
    And I see Paste inside of Toolbar
    And I see Paste text inside of Toolbar
    And I see Link inside of Toolbar
    And I see Unlink inside of Toolbar
    And I see Bulleted list inside of Toolbar
    And I see Numbered list inside of Toolbar
    And I see Blockquote inside of Toolbar
    And I see Table inside of Toolbar
    And I see Horizontal rule inside of Toolbar
    And I see Justify left inside of Toolbar
    And I see Justify center inside of Toolbar
    And I see Justify right inside of Toolbar
    And I see Justify block inside of Toolbar
    And I see Outdent inside of Toolbar
    And I see Indent inside of Toolbar
    And I see Format inside of Toolbar
    And I see Undo inside of Toolbar
    And I see Redo inside of Toolbar
    And I see Show blocks inside of Toolbar
    And I see Source inside of Toolbar
    And I see Templates inside of Toolbar
    And I should not see a "#cke_edit-body-0-value .cke_top .cke_button__wenzgmap" element

  @api @javascript
  Scenario: Admin Text format has expected WYSIWYG buttons
    Given I am logged in as a user with the "create test content, use text format admin_text" permissions
    When I go to "node/add/test"
    Then I see visible Toolbar
    And I see Bold inside of Toolbar
    And I see Italic inside of Toolbar
    And I see Strikethrough inside of Toolbar
    And I see Styles inside of Toolbar
    And I see Superscript inside of Toolbar
    And I see Subscript inside of Toolbar
    And I see Remove format inside of Toolbar
    And I see Copy inside of Toolbar
    And I see Cut inside of Toolbar
    And I see Paste inside of Toolbar
    And I see Paste text inside of Toolbar
    And I see Link inside of Toolbar
    And I see Unlink inside of Toolbar
    And I see Bulleted list inside of Toolbar
    And I see Numbered list inside of Toolbar
    And I see Blockquote inside of Toolbar
    And I see Table inside of Toolbar
    And I see Horizontal rule inside of Toolbar
    And I see Justify left inside of Toolbar
    And I see Justify center inside of Toolbar
    And I see Justify right inside of Toolbar
    And I see Justify block inside of Toolbar
    And I see Outdent inside of Toolbar
    And I see Indent inside of Toolbar
    And I see Format inside of Toolbar
    And I see Undo inside of Toolbar
    And I see Redo inside of Toolbar
    And I see Show blocks inside of Toolbar
    And I see Source inside of Toolbar
    And I see Templates inside of Toolbar
    And I see Google Map inside of Toolbar
    And I see Iframe inside of Toolbar
