<?php

namespace Drupal\jira_rest\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jira_rest\JiraRestWrapperService;
use JiraRestApi\JiraException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Jira_RestController.
 */
class JiraRestController extends ControllerBase {

  /**
   * Jira Rest API Wrapper.
   *
   * @var \Drupal\jira_rest\JiraRestWrapperService
   */
  protected $jiraRestWrapperService;

  /**
   * Class constructor.
   */
  public function __construct(JiraRestWrapperService $jira_rest_wrapper_service) {
    $this->jiraRestWrapperService = $jira_rest_wrapper_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jira_rest_wrapper_service')
    );
  }

  /**
   * Just for testing.
   */
  public function test() {

    // Example for searching open tickets and returning the key of the first one
    // found.
    try {
      $search = $this->jiraRestWrapperService->getIssueService()->search(utf8_encode("status = Open"));

      foreach ($search->getIssues() as $i) {
        $issue = $i;
      }

      if (!empty($issue)) {
        return [
          '#markup' => $this->t('Controller action test successful, found a jira issue with key: @key', ['@key' => $issue->key]),
        ];
      }
      else {
        return [
          '#markup' => $this->t('Controller action test successful, but no open issue found'),
        ];
      }
    }
    catch (JiraException $e) {
      return [
        '#markup' => $this->t('This test page requires one and only one JIRA Endpoint to be setup.'),
      ];
    }
  }

}
