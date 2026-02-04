@tide @install:tide_site @install:tide_site_restriction @install:tide_search @install:tide_data_driven_component @install:tide_landing_page @install:tide_content_collection_ui
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
      | name          | tid    |
      | [TEST] Site 1 | 987601 |
    Given content_category terms:
      | name              | tid    |
      | [TEST] Category 1 | 987602 |
    Given topic terms:
      | name             | tid    |
      | [TEST] Topic 1   | 987603 |
      | [TEST] Education | 987604 |
    Given searchable_content_types terms:
      | name         | field_machine_name | tid    |
      | Landing page | landing_page       | 987605 |
      | Test         | test               | 987606 |
    Given searchable_fields terms:
      | name  | field_taxonomy_machine_name | field_elasticsearch_id | field_elasticsearch_field | tid    |
      | Topic | topic                       | field_topic            | field_topic_name          | 987607 |
      | Tags  | tags                        | field_tags             | field_tags_name           | 987608 |
    Given test content:
      | title                    | moderation_state | nid    |
      | [TEST] Education Content | published        | 987610 |
      | [TEST] Other Content     | published        | 987611 |
    Given landing_page content:
      | title                        | field_landing_page_summary | moderation_state | field_node_site | field_node_primary_site | field_topic    | field_content_category | nid    |
      | [TEST] Content Collection UI | Some text                  | published        | [TEST] Site 1   | [TEST] Site 1           | [TEST] Topic 1 | [TEST] Category 1      | 987609 |

    # Add the landing page component
    When I visit "node/987609/edit"
    And I press the "edit-field-landing-page-component-add-more-add-modal-form-area-add-more" button
    And I scroll "[role='dialog'] [name='field_landing_page_component_content_collection_ui_add_more']" into view
    And I click on the modal element "[name='field_landing_page_component_content_collection_ui_add_more']"

    # Test the auto source (choose source auto and content type landing page)
    When I click on the element "#tide-content-collection-0-source-auto"
    Then I click on the element "#tide-content-collection-0-contentType"
    And I click on the element "#tide-content-collection-0-contentType-menu-landing_page"

    # Add a filter (find and select the [TEST] Education (987604) topic)
    When I press the "Add filter" button
    Then I select "Topic" from "Filter by"
    Then I fill in "Filter terms" with "Edu"
    And I wait for 2 seconds
    Then I scroll "[name='tide-content-collection-0-displayType']" into view
    And I click on the element "#tide-content-collection-0-filters-terms-0-menu-987604"

    # Save auto config
    Then I press the "Save" button
    Then I should see the text "Landing Page [TEST] Content Collection UI has been updated."

    # Ensure auto app has seeded
    When I visit "node/987609/edit"
    Then I press the "field_landing_page_component_0_edit" button
    And I wait for AJAX to finish
    Then I should see an "[aria-label='Remove Landing page']" element
    Then I should see an "[aria-label='Remove [TEST] Education']" element
    Then I scroll "[name='tide-content-collection-0-displayType']" into view
    And save screenshot

    # Test the manual source (choose source manual and find and add Test education content)
    When I click on the element "#tide-content-collection-0-source-manual"
    Then I fill in "Search content" with "edu"
    And I wait for 2 seconds
    Then I scroll "[name='tide-content-collection-0-displayType']" into view
    And I should see the text "[TEST] Education Content"
    And I should not see the text "[TEST] Other Content"
    And I click on the element "#tide-content-collection-0-manualItems-0-menu-987610"

      # Save manual config
    Then I press the "Save" button
    Then I should see the text "Landing Page [TEST] Content Collection UI has been updated."

    # Ensure manual app has seeded
    When I visit "node/987609/edit"
    Then I press the "field_landing_page_component_0_edit" button
    And I wait for AJAX to finish
    Then I should see an "[aria-label='Remove [TEST] Education Content']" element
    Then I scroll "[name='tide-content-collection-0-displayType']" into view
    And save screenshot

    Then I delete landing_page "[TEST] Content Collection UI"
