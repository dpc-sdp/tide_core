@tide @install:tide_landing_page @install:tide_content_collection_ui
Feature: Content Collection UI

  @api
  Scenario: Searchable taxonomies exists
    Given vocabulary "searchable_content_types" with name "Searchable Content Types" exists
    Given vocabulary "searchable_fields" with name "Searchable Fields" exists

  @javascript @jsonapi @api
  Scenario: Component works on Landing Page
    Given I am logged in as a user with the "administrator" role

    # Add the landing page component
    When I visit "node/add/landing_page"
    And I press the "edit-field-landing-page-component-add-more-add-modal-form-area-add-more" button
    And I scroll "[role='dialog'] [name='field_landing_page_component_content_collection_ui_add_more']" into view
    And I click on the modal element "[name='field_landing_page_component_content_collection_ui_add_more']"

    # Interact with the component
    When I click on the element "#tide-content-collection-0-source-auto"
    Then I should see the text "Refine Content"
    When I click on the element "#tide-content-collection-0-source-manual"
    Then I should see the text "Select Content"
