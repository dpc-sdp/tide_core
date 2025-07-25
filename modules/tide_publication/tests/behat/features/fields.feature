@tide @test-me @tide_publication
Feature: Fields for Publication content type

  Ensure that Publication content has the expected fields.

  @api @javascript
  Scenario: The content type has the expected fields (and labels where we can use them).
    Given I am logged in as a user with the "create publication content, access toolbar" permission
    When I visit "node/add/publication"
    And save screenshot

    And I click on the horizontal tab "Header"
    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element
    And I see field "Introduction text"
    And I should see an "textarea#edit-field-landing-page-intro-text-0-value" element
    And I should not see an "textarea#edit-field-landing-page-intro-text-0-value.required" element

    And I see field "Summary"
    And I should see a "textarea#edit-field-landing-page-summary-0-value" element
    And I should see a "textarea#edit-field-landing-page-summary-0-value.required" element

    And I click on the horizontal tab "Customised Header"

    And the "#edit-field-landing-page-hero-image" element should contain "Hero image"
    And I should see a "input#edit-field-landing-page-hero-image-target-id" element
    And I should see a "select#edit-field-landing-page-hero-theme" element

    And I select the radio button "Corner graphics"
    And the "#edit-field-bottom-graphical-image" element should contain "Bottom Corner Graphic"
    And I should see an "input#edit-field-bottom-graphical-image-entity-browser-target" element

    And the "#edit-field-graphical-image" element should contain "Top Corner Graphic"
    And I should see an "input#edit-field-graphical-image-entity-browser-target" element

    And the "#edit-field-landing-page-hero-logo" element should contain "Logo"
    And I should see an "input#edit-field-landing-page-hero-logo-entity-browser-entity-browser-open-modal" element

    And I see the text "Date of publication"
    And I should see an "input#edit-field-publication-date-0-value-date" element

    And I should see "License Type" in the "label[for=edit-field-license-type]" element
    And I should see an "select#edit-field-license-type" element

    And I see field "Publication author"
    And I should see an "input#edit-field-publication-authors-0-target-id" element

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
    And I should see the button "Complex image"
    And I should see the button "Reusable content item"
    And I should see the button "Statistics Grid"
    And I press the "Close" button

    And I scroll selector "#edit-group-sidebar" into view
    And I click on the horizontal tab "Sidebar"

    Then I press "Documents"
    Then I should see an "#edit-field-node-documents" element
    And I should see an "input#edit-field-node-documents-entity-browser-entity-browser-open-modal" element

    And I click on the detail "Related links"
    And I see field "Show related content?"
    And I should see an "input#edit-field-show-related-content-value" element
    And I should not see an "input#edit-field-show-related-content-value.required" element
    And I should see text matching "Related links"
    And I should see the button "Add Related links" in the "content" region

    And I click on the detail "Contact"
    And I see field "Show contact details"
    And I should see an "input#edit-field-landing-page-show-contact-value" element
    And I should not see an "input#edit-field-landing-page-show-contact-value.required" element
    And I should see text matching "Contact us"
    And I should see text matching "No Contact Us block added yet."
    And I should see the button "Add Contact Us" in the "content" region

    And I should see text matching "Social sharing"

    And I see field "Show content rating?"
    And I should see an "input#edit-field-show-content-rating-value" element
    And I should not see an "input#edit-field-show-content-rating-value.required" element

    And I see field "Tags"
    And I should see an "input#edit-field-tags-0-target-id" element
    And I should not see an "input#edit-field-tags-0-target-id.required" element

    And I see field "Topic"
    And I should see an "input#edit-field-topic-0-target-id" element
    And I should see an "input#edit-field-topic-0-target-id.required" element

    And I see field "Show topic term and tags?"
    And I should see text matching "Social sharing"

  @api @javascript @publication
  Scenario: The content type has the expected fields (and labels where we can use them).
    # Given I am logged in as a user with the "editor" role
    Given I am logged in as a user with the "create publication_page content" permission
    When I visit "node/add/publication_page"
    And save screenshot

    And I click on the horizontal tab "Header"
    Then I see field "Publication / Parent page"
    And I should see an "input#edit-field-publication-0-target-id-target-id.required" element
    Then I see field "Title"
    And I should see an "input#edit-title-0-value.required" element
    And I see field "Introduction text"
    And I should see an "textarea#edit-field-landing-page-intro-text-0-value" element
    And I should not see an "textarea#edit-field-landing-page-intro-text-0-value.required" element

    And I see field "Summary"
    And I should see a "textarea#edit-field-landing-page-summary-0-value" element
    And I should see a "textarea#edit-field-landing-page-summary-0-value.required" element

    And I click on the horizontal tab "Customised Header"

    And the "#edit-field-landing-page-hero-image" element should contain "Hero image"
    And I should see a "input#edit-field-landing-page-hero-image-target-id" element
    And I should see a "select#edit-field-landing-page-hero-theme" element

    And I select the radio button "Corner graphics"
    And the "#edit-field-bottom-graphical-image" element should contain "Bottom Corner Graphic"
    And I should see an "input#edit-field-bottom-graphical-image-entity-browser-target" element

    And the "#edit-field-graphical-image" element should contain "Top Corner Graphic"
    And I should see an "input#edit-field-graphical-image-entity-browser-target" element

    And the "#edit-field-landing-page-hero-logo" element should contain "Logo"
    And I should see an "input#edit-field-landing-page-hero-logo-entity-browser-entity-browser-open-modal" element


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
    And I should see the button "Data driven component"
    And I should see the button "Call to action"
    And I should see the button "Card carousel"
    And I should see the button "Promotion card"
    And I should see the button "Navigation card"
    And I should see the button "Key dates"
    And I should see the button "Data table"
    And I should see the button "Image gallery"
    And I should see the button "Complex image"
    And I should see the button "Form embed (Drupal)"
    And I should see the button "Timelines"
    And I should see the button "Statistics grid"
    And I should see the button "Reusable content item"
    And I press the "Close" button

    And I scroll selector "#edit-group-sidebar" into view
    And I click on the horizontal tab "Sidebar"

    And I click on the detail "Related links"
    And I see field "Show related content?"
    And I should see an "input#edit-field-show-related-content-value" element
    And I should not see an "input#edit-field-show-related-content-value.required" element
    And I should see text matching "Related links"
    And I should see the button "Add Related links" in the "content" region

    And I click on the detail "Contact"
    And I see field "Show contact details"
    And I should see an "input#edit-field-landing-page-show-contact-value" element
    And I should not see an "input#edit-field-landing-page-show-contact-value.required" element
    And I should see text matching "Contact us"
    And I should see text matching "No Contact Us block added yet."
    And I should see the button "Add Contact Us" in the "content" region

    And I see field "Show topic term and tags?"
    And I should see text matching "Social sharing"

    And I see field "Show content rating?"
    And I should see an "input#edit-field-show-content-rating-value" element
    And I should not see an "input#edit-field-show-content-rating-value.required" element
