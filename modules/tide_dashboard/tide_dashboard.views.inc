<?php

/**
 * @file
 * Provides views data.
 */

/**
 * Implements hook_views_data().
 */
function tide_dashboard_views_data() {
  $data = [];

  $data['views']['views_tide_dashboard_admin_content_search_form'] = [
    'title' => t('Admin Content Search form'),
    'help' => t('Insert the Admin Content Search form inside an area.'),
    'area' => [
      'id' => 'views_tide_dashboard_admin_content_search_form',
    ],
  ];

  return $data;
}
