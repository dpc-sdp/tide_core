@tide @install:tide_site_restriction @install:tide_landing_page @install:tide_content_collection_ui
Feature: Content Collection UI

  @api
  Scenario: Searchable taxonomies exists
    Given vocabulary "searchable_content_types" with name "Searchable Content Types" exists
    Given vocabulary "searchable_fields" with name "Searchable Fields" exists

  @javascript @jsonapi @api
  Scenario: Component works on Landing Page
    Given I am logged in as a user with the "administrator" role

    # Seed with test data
    Given sites terms:
      | name        | tid    |
      | Test Site 1 | 999901 |
    Given content_category terms:
      | name            | tid    |
      | Test Category 1 | 999902 |
    Given topic terms:
      | name      | tid    |
      | Test      | 999903 |
      | Education | 999904 |
    Given searchable_content_types terms:
      | name         | field_machine_name | tid    |
      | Landing page | landing_page       | 999905 |
      | Test         | test               | 999906 |
    Given searchable_fields terms:
      | name  | field_taxonomy_machine_name | field_elasticsearch_id | field_elasticsearch_field | tid    |
      | Topic | topic                       | field_topic            | field_topic_name          | 999907 |
      | Tags  | tags                        | field_tags             | field_tags_name           | 999908 |
    Given landing_page content:
      | title                  | field_landing_page_summary | moderation_state | field_node_site | field_node_primary_site | field_topic | field_content_category | nid    |
      | Test collection        | Some text                  | published        | Test Site 1     | Test Site 1             | Test        | Test Category 1        | 999909 |
      | Test education content | Some text                  | published        | Test Site 1     | Test Site 1             | Education   | Test Category 1        | 999910 |
      | Test other content     | Some text                  | published        | Test Site 1     | Test Site 1             | Test        | Test Category 1        | 999911 |

    # Add the landing page component
    When I visit "node/999909/edit"
    And I press the "edit-field-landing-page-component-add-more-add-modal-form-area-add-more" button
    And I scroll "[role='dialog'] [name='field_landing_page_component_content_collection_ui_add_more']" into view
    And I click on the modal element "[name='field_landing_page_component_content_collection_ui_add_more']"

    # Test the auto source
    When I click on the element "#tide-content-collection-0-source-auto"
    Then I click on the element "#tide-content-collection-0-contentType"
    And I click on the element "#tide-content-collection-0-contentType-menu-landing_page"
    Then I should see an "[aria-label='Remove Landing Page']" element

    When I press the "Add filter" button
    Then I select "Topic" from "Filter by"
    Then I fill in "Filter terms" with "Edu"
    And I wait for 2 seconds
    Then I scroll "[name='tide-content-collection-0-displayType']" into view
    And I click on the element "#tide-content-collection-0-filters-terms-0-menu-999904"
    Then I should see an "[aria-label='Remove Education']" element

    # Save auto config
    Then I press the "Save" button
    Then I should see the text "Landing Page Test collection has been updated."

    # Ensure auto app has seeded
    When I visit "node/999909/edit"
    Then I press the "field_landing_page_component_0_edit" button
    And I wait for AJAX to finish
    Then I should see an "[aria-label='Remove Landing Page']" element
    Then I should see an "[aria-label='Remove Education']" element
    Then I scroll "[name='tide-content-collection-0-displayType']" into view
    And save screenshot

    # Test the manual source
    When I click on the element "#tide-content-collection-0-source-manual"
    Then I fill in "Search content" with "edu"
    And I wait for 2 seconds
    Then I scroll "[name='tide-content-collection-0-displayType']" into view
    And I should see the text "Test education content"
    And I should not see the text "Test other content"
    And I click on the element "#tide-content-collection-0-manualItems-0-menu-999910"

      # Save manual config
    Then I press the "Save" button
    Then I should see the text "Landing Page Test collection has been updated."

    # Ensure manual app has seeded
    When I visit "node/999909/edit"
    Then I press the "field_landing_page_component_0_edit" button
    And I wait for AJAX to finish
    Then I should see an "[aria-label='Remove Test education content']" element
    Then I scroll "[name='tide-content-collection-0-displayType']" into view
    And save screenshot
