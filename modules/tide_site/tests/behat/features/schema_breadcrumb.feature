@tide @breadcrumb @schema
Feature: JSON-LD breadcrumb (tide_core)

  As a Tide site owner, I want each page's JSON-LD to include a menu-based
  Schema.org BreadcrumbList, gated per site, so that structured data reflects
  the site's information architecture.

  The breadcrumb engine lives in tide_core: it builds the trail from the main
  menu of the site being viewed (starting at the site root "Home"), is switched
  on/off per site via the "Enable breadcrumbs" field, and is exposed through the
  node's computed json_ld field.

  This feature lives under tide_site (not tide_core) because the scenarios set
  field_node_primary_site / field_node_site on nodes, and those field instances
  are only provisioned onto content types when tide_site is installed.

  Background:
    Given vocabulary "sites" with name "Sites" exists
    And no "sites" terms:
      | Breadcrumb On  |
      | Breadcrumb Off |
      | MS Site On     |
      | MS Site Off    |
    And no menus:
      | Breadcrumb Menu |

  @api
  Scenario: An enabled site emits a menu-based BreadcrumbList starting at Home
    Given menus:
      | label           |
      | Breadcrumb Menu |
    And sites terms:
      | name          | field_site_domains        | field_enable_breadcrumbs |
      | Breadcrumb On | www.breadcrumb-on.example | 1                        |
    And the main menu of site "Breadcrumb On" is "Breadcrumb Menu"
    And test content:
      | title          | field_node_primary_site | field_node_site |
      | BC Parent page | Breadcrumb On           | Breadcrumb On   |
      | BC Child page  | Breadcrumb On           | Breadcrumb On   |
    And "Breadcrumb Menu" menu_links for nodes:
      | title        | node           | parent       |
      | Parent crumb | BC Parent page |              |
      | Child crumb  | BC Child page  | Parent crumb |

    # The child page sits under "Parent crumb" in the site menu, so its trail is
    # Home > Parent crumb > (current page).
    Then the JSON-LD of "test" "BC Child page" should contain "BreadcrumbList"
    # Home is the first crumb and points at the site root (absolute, https).
    And the JSON-LD of "test" "BC Child page" should contain "Home"
    And the JSON-LD of "test" "BC Child page" should contain "https://www.breadcrumb-on.example"
    # The ancestor uses the menu link title, and the current page is appended.
    And the JSON-LD of "test" "BC Child page" should contain "Parent crumb"
    And the JSON-LD of "test" "BC Child page" should contain "BC Child page"

  @api
  Scenario: A disabled site emits no BreadcrumbList
    Given sites terms:
      | name           | field_site_domains         | field_enable_breadcrumbs |
      | Breadcrumb Off | www.breadcrumb-off.example | 0                        |
    And test content:
      | title       | field_node_primary_site | field_node_site |
      | BC Off page | Breadcrumb Off          | Breadcrumb Off  |

    # Breadcrumbs are off for this site, so no BreadcrumbList is emitted at all.
    Then the JSON-LD of "test" "BC Off page" should not contain "BreadcrumbList"

  @api
  Scenario: The breadcrumb follows the site being viewed on a multisite node
    Given sites terms:
      | name        | field_site_domains | field_enable_breadcrumbs |
      | MS Site On  | www.ms-on.example  | 1                        |
      | MS Site Off | www.ms-off.example | 0                        |
    And test content:
      | title     | field_node_primary_site | field_node_site         |
      | MS Shared | MS Site On              | MS Site On, MS Site Off |

    # Same node, viewed on the enabled site: breadcrumb present, on its domain.
    Then with site "MS Site On" the JSON-LD of "test" "MS Shared" should contain "BreadcrumbList"
    And with site "MS Site On" the JSON-LD of "test" "MS Shared" should contain "https://www.ms-on.example"
    # Viewed on the disabled site: no breadcrumb, even though it is enabled elsewhere.
    And with site "MS Site Off" the JSON-LD of "test" "MS Shared" should not contain "BreadcrumbList"
