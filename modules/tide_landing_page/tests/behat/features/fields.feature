@tide
Feature: Fields for Landing Page content type

  Ensure that Landing Page content has the expected fields.

  @api @nosuggest @javascript
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create landing_page content" permission
    # Given I am logged in as a user with the "editor" role
    When I visit "node/add/landing_page"
    And save screenshot
    Then I see field "Page title"
    And I should see an "input#edit-title-0-value.required" element

    And I click on the horizontal tab "Header"
    And I see field "Introduction text"
    And I should see an "textarea#edit-field-landing-page-intro-text-0-value" element
    And I should not see an "textarea#edit-field-landing-page-intro-text-0-value.required" element

    And I should see text matching "Header links"
    And I should see text matching "No Header link added yet."
    And I should see the button "Add Header link" in the "content" region

    And I click on the horizontal tab "Customised Header"

    And I select the radio button "Call to action banner"
    And I should see text matching "Call to action banner"
    And I should see text matching "No Hero Banner with CTA added yet."
    And I should see the button "Add Hero banner with CTA" in the "content" region

    And the "#edit-field-landing-page-hero-logo" element should contain "Logo"
    And I should see an "input#edit-field-landing-page-hero-logo-entity-browser-entity-browser-open-modal" element

    And I select the radio button "Corner graphics"
    And the "#edit-field-bottom-graphical-image" element should contain "Bottom Corner Graphic"
    And I should see an "input#edit-field-bottom-graphical-image-entity-browser-target" element

    And I click on the horizontal tab "Header extras"
    And I should see text matching "Header components"
    And I press the "edit-field-landing-page-header-add-more-add-modal-form-area-add-more" button
    And I should see the button "Introduction banner"
    And I should see the button "Search banner"
    And I press the "Close" button

    And I click on the horizontal tab "Page campaign"
    And I click on the detail "Primary campaign"
    And I should see text matching "Primary Campaign"
    And I should see an "input#edit-field-landing-page-c-primary-0-target-id" element
    And I should not see an "input#edit-field-landing-page-c-primary-0-target-id.required" element

    And I click on the detail "Secondary campaign"
    And I should see text matching "Secondary campaign"
    And I should see an "input#edit-field-landing-page-c-secondary-0-target-id" element
    And I should not see an "input#edit-field-landing-page-c-secondary-0-target-id.required" element

    And I scroll selector "#edit-field-featured-image" into view
    And the "#edit-field-featured-image" element should contain "Feature Image"
    And I should see an "input#edit-field-featured-image-entity-browser-entity-browser-open-modal" element

    And I click on the horizontal tab "Page content"
    And I see field "Show table of contents"
    And I should see an "input#edit-field-show-table-of-content-value" element
    And I should not see an "input#edit-field-show-table-of-content.required" element

    When I check "edit-field-show-table-of-content-value"
    Then I should see text matching "Choose headings to display"
    And I should see an "input#edit-field-node-display-headings-showh2" element
    And I should see an "input#edit-field-node-display-headings-showh2andh3" element

    And I should see text matching "Content components"
    And I press the "edit-field-landing-page-component-add-more-add-modal-form-area-add-more" button
    And I should see the button "Basic text"
    And I should see the button "Accordion"
    And I should see the button "Call to action"
    And I should see the button "Card carousel"
    And I should see the button "Promotion card"
    And I should see the button "Navigation card"
    And I should see the button "Key dates"
    And I should see the button "Image gallery"
    And I should see the button "Complex image"
    And I press the "Close" button

    And I scroll selector "#edit-group-sidebar" into view
    And I click on the horizontal tab "Sidebar"
    And I see field "Show Site-section navigation?"
    And I should see an "input#edit-field-landing-page-nav-title-0-value" element
    And I should not see an "input#edit-field-landing-page-nav-title-0-value.required" element

    And I click on the detail "Related links"
    And I see field "Show related content?"
    And I should see an "input#edit-field-show-related-content-value" element
    And I should not see an "input#edit-field-show-related-content-value.required" element
    And I should see text matching "Related links"
    And I should see the button "Add Related links" in the "content" region

    And I click on the detail "What's next"
    And I see field "Show What's next?"
    And I should see an "input#edit-field-show-whats-next-value" element
    And I should not see an "input#edit-field-show-whats-next-value.required" element
    And I should see text matching "What's next"
    And I should see text matching "No What's Next block added yet."
    And I should see the button "Add Link" in the "content" region

    And I click on the detail "Contact"
    And I see field "Show contact details"
    And I should see an "input#edit-field-landing-page-show-contact-value" element
    And I should not see an "input#edit-field-landing-page-show-contact-value.required" element
    And I should see text matching "Contact us"
    And I should see text matching "No Contact Us block added yet."
    And I should see the button "Add Contact Us" in the "content" region

    And I should see text matching "Social sharing"

    And I see field "Tags"
    And I should see an "input#edit-field-tags-0-target-id" element
    And I should not see an "input#edit-field-tags-0-target-id.required" element

    And I see field "Topic"
    And I should see an "input#edit-field-topic-0-target-id" element
    And I should see an "input#edit-field-topic-0-target-id.required" element

    And I see field "Show topic term and tags?"

    And I see field "Background colour"
    And I should see an "select#edit-field-landing-page-bg-colour" element
    And I should see an "select#edit-field-landing-page-bg-colour.required" element

  @api
  Scenario: The content type has the menu settings.
    Given I am logged in as a user with the "create landing_page content, administer menu" permission
    When I visit "node/add/landing_page"
    And I should see the text "Menu settings"
    And I see field "Provide a menu link"

  @api @suggest @javascript
  Scenario: The content type has the expected fields (and labels where we can use them) including from suggested modules.
    Given I am logged in as a user with the "create landing_page content" permission
    # Given I am logged in as a user with the "editor" role
    When I visit "node/add/landing_page"
    And save screenshot
    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element

    And I click on the horizontal tab "Header"
    And I see field "Introduction text"
    And I should see an "textarea#edit-field-landing-page-intro-text-0-value" element
    And I should not see an "textarea#edit-field-landing-page-intro-text-0-value.required" element

    And I should see text matching "Header links"
    And I should see text matching "No Header link added yet."
    And I should see the button "Add Header link" in the "content" region

    And I click on the horizontal tab "Customised Header"

    And I select the radio button "Call to action banner"
    And I should see text matching "Call to action banner"
    And I should see text matching "No Hero Banner with CTA added yet."
    And I should see the button "Add Hero banner with CTA" in the "content" region

    And the "#edit-field-landing-page-hero-logo" element should contain "Logo"
    And I should see an "input#edit-field-landing-page-hero-logo-entity-browser-entity-browser-open-modal" element

    And I select the radio button "Corner graphics"
    And the "#edit-field-bottom-graphical-image" element should contain "Bottom Corner Graphic"
    And I should see an "input#edit-field-bottom-graphical-image-entity-browser-target" element

    And I click on the horizontal tab "Header extras"
    And I should see text matching "Header components"
    And I press the "edit-field-landing-page-header-add-more-add-modal-form-area-add-more" button
    And I should see the button "Introduction banner"
    And I should see the button "Search banner"
    And I press the "Close" button

    And I click on the horizontal tab "Page campaign"
    And I click on the detail "Primary campaign"
    And I should see text matching "Primary Campaign"
    And I should see an "input#edit-field-landing-page-c-primary-0-target-id" element
    And I should not see an "input#edit-field-landing-page-c-primary-0-target-id.required" element

    And I click on the detail "Secondary campaign"
    And I should see text matching "Secondary campaign"
    And I should see an "input#edit-field-landing-page-c-secondary-0-target-id" element
    And I should not see an "input#edit-field-landing-page-c-secondary-0-target-id.required" element

    And I scroll selector "#edit-field-featured-image" into view
    And the "#edit-field-featured-image" element should contain "Feature Image"
    And I should see an "input#edit-field-featured-image-entity-browser-entity-browser-open-modal" element

    And I scroll selector "#edit-group-components" into view
    And I click on the horizontal tab "Page content"
    And I see field "Show table of contents"
    And I should see an "input#edit-field-show-table-of-content-value" element
    And I should not see an "input#edit-field-show-table-of-content.required" element

    When I check "edit-field-show-table-of-content-value"
    Then I should see text matching "Choose headings to display"
    And I should see an "input#edit-field-node-display-headings-showh2" element
    And I should see an "input#edit-field-node-display-headings-showh2andh3" element

    And I should see text matching "Content components"
    And I press the "edit-field-landing-page-component-add-more-add-modal-form-area-add-more" button
    And I should see the button "Basic text"
    And I should see the button "Accordion"
    And I should see the button "Call to action"
    And I should see the button "Card carousel"
    And I should see the button "Promotion card"
    And I should see the button "Navigation card"
    And I should see the button "Key dates"
    And I should see the button "Image gallery"
    And I should see the button "Complex image"
    And I should see the button "Statistics Grid"
    And I should see the button "Compact card collection"

    And I should see the button "Form embed (Drupal)"
    And I should see the button "Form embed (OpenForms)"

    And I press the "Close" button

    And I scroll selector "#edit-group-sidebar" into view
    And I click on the horizontal tab "Sidebar"
    And I see field "Show Site-section navigation?"
    And I should see an "input#edit-field-landing-page-nav-title-0-value" element
    And I should not see an "input#edit-field-landing-page-nav-title-0-value.required" element

    And I click on the detail "Related links"
    And I see field "Show related content?"
    And I should see an "input#edit-field-show-related-content-value" element
    And I should not see an "input#edit-field-show-related-content-value.required" element
    And I should see text matching "Related links"
    And I should see the button "Add Related links" in the "content" region

    And I click on the detail "What's next"
    And I see field "Show What's next?"
    And I should see an "input#edit-field-show-whats-next-value" element
    And I should not see an "input#edit-field-show-whats-next-value.required" element
    And I should see text matching "What's next"
    And I should see text matching "No What's Next block added yet."
    And I should see the button "Add Link" in the "content" region

    And I click on the detail "Contact"
    And I see field "Show contact details"
    And I should see an "input#edit-field-landing-page-show-contact-value" element
    And I should not see an "input#edit-field-landing-page-show-contact-value.required" element
    And I should see text matching "Contact us"
    And I should see text matching "No Contact Us block added yet."
    And I should see the button "Add Contact Us" in the "content" region

    And I should see text matching "Social sharing"

    And I see field "Tags"
    And I should see an "input#edit-field-tags-0-target-id" element
    And I should not see an "input#edit-field-tags-0-target-id.required" element

    And I see field "Topic"
    And I should see an "input#edit-field-topic-0-target-id" element
    And I should see an "input#edit-field-topic-0-target-id.required" element

    And I see field "Show topic term and tags?"

    And I see field "Background colour"
    And I should see an "select#edit-field-landing-page-bg-colour" element
    And I should see an "select#edit-field-landing-page-bg-colour.required" element

  @api @javascript @skipped
  # TODO: This test must be rewritten to work with the new form UI.
  Scenario: The promotion card paragraph type has the expected fields.
    Given I am logged in as a user with the "create landing_page content" permission
    When I visit "node/add/landing_page"
    And I click "Body Content"
    Then I select "Promotion card" from "edit-field-landing-page-component-add-more-add-more-select"
    And I press "edit-field-landing-page-component-add-more-add-more-button"
    And I wait for 5 seconds
    Then I see field "Link"
    And I see field "Title"
    And I see field "Summary"
    And I see field "field_landing_page_component[0][subform][field_promo_card_display_style]"
    And save screenshot
    And I select "thumbnail" from "field_landing_page_component[0][subform][field_promo_card_display_style]"
    And I wait for 5 seconds
    # This field can be "seen" but not visible.
    And I see field "field_landing_page_component[0][subform][field_customise][value]"
    And save screenshot

  @api @javascript @skipped
  # TODO: This test must be rewritten to work with the new form UI.
  Scenario: The navigation card paragraph type has the expected fields.
    Given I am logged in as a user with the "create landing_page content" permission
    When I visit "node/add/landing_page"
    And I click "Body Content"
    Then I select "Navigation card" from "edit-field-landing-page-component-add-more-add-more-select"
    And I press "edit-field-landing-page-component-add-more-add-more-button"
    And I wait for 5 seconds
    Then I see field "Link"
    And I see field "Title"
    And I see field "Summary"
    And I see field "field_landing_page_component[0][subform][field_nav_card_display_style]"
    And save screenshot
    And I select "thumbnail" from "field_landing_page_component[0][subform][field_nav_card_display_style]"
    And I wait for 5 seconds
    # This field can be "seen" but not visible.
    And I see field "field_landing_page_component[0][subform][field_customise][value]"
    And save screenshot
