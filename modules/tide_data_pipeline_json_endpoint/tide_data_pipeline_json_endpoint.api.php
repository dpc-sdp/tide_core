<?php

/**
 * @file
 * Hooks provided by the Tide Data Pipeline JSON Endpoint module.
 */

use Drupal\data_pipelines\Entity\DatasetInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Reacts to a dataset finishing processing after a JSON endpoint push.
 *
 * This is invoked from the "push" controller
 * (\Drupal\tide_data_pipeline_json_endpoint\Controller\DatasetPushController)
 * once a dataset pushed to POST /api/datasets/{machine_name}/push has been
 * validated and written to its configured destination(s) (e.g. indexed into
 * Elasticsearch by a data_pipelines destination plugin).
 *
 * This module deliberately has no knowledge of what a destination-specific
 * "after processing" step should do. For example, a site may want to remove
 * documents from an Elasticsearch index that no longer meet some business
 * rule (e.g. records whose status field indicates they are no longer
 * active) after every push. Implement this hook in a site-specific module
 * (not in this shared module, which may be used by other sites with
 * different requirements) to perform that kind of cleanup.
 *
 * @param \Drupal\data_pipelines\Entity\DatasetInterface $dataset
 *   The dataset entity that was just processed.
 * @param string $machine_name
 *   The machine name of the dataset, as used in the push URL.
 */
function hook_data_pipeline_json_endpoint_dataset_processed(DatasetInterface $dataset, string $machine_name) {
  // Example: react only to a specific dataset.
  if ($machine_name !== 'stolen_vehicles') {
    return;
  }

  // Perform site-specific post-processing, e.g. deleting stale records from
  // the destination index. See implementing modules for real examples.
}

/**
 * @} End of "addtogroup hooks".
 */
