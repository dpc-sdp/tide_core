<?php

namespace Tide\Tests\Context;

/**
 * Common test trait for Tide.
 *
 * @todo Review this trait and try using BehatSteps trait instead.
 */
trait TideCommonTrait {

  /**
   * @Then I am in the :path path
   */
  public function assertCurrentPath($path) {
    $current_path = $this->getSession()->getCurrentUrl();
    $current_path = parse_url($current_path, PHP_URL_PATH);
    $current_path = ltrim($current_path, '/');
    $current_path = $current_path == '' ? '<front>' : $current_path;

    if ($current_path != $path) {
      throw new \Exception(sprintf('Current path is "%s", but expected is "%s"', $current_path, $path));
    }
  }

  /**
   * Overriding parent function.
   */
  public function assertAuthenticatedByRole($role) {
    // Override parent assertion to allow using 'anonymous user' role without
    // actually creating a user with role. By default,
    // assertAuthenticatedByRole() will create a user with 'authenticated role'
    // even if 'anonymous user' role is provided.
    if ($role === 'anonymous user') {
      if (!empty($this->loggedInUser)) {
        $this->logout();
      }
    }
    else {
      parent::assertAuthenticatedByRole($role);
    }
  }

  /**
   * @Then I wait for :sec second(s)
   */
  public function waitForSeconds($sec) {
    sleep($sec);
  }

  /**
   * Wait for AJAX to finish.
   *
   * @see \Drupal\FunctionalJavascriptTests\JSWebAssert::assertWaitOnAjaxRequest()
   *
   * @Given I wait :timeout seconds for AJAX to finish
   */
  public function iWaitForAjaxToFinish($timeout) {
    $condition = <<<JS
      (function() {
        function isAjaxing(instance) {
          return instance && instance.ajaxing === true;
        }
        return (
          // Assert no AJAX request is running (via jQuery or Drupal) and no
          // animation is running.
          (typeof jQuery === 'undefined' || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
          (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
        );
      }());
JS;
    $result = $this->getSession()->wait($timeout * 1000, $condition);
    if (!$result) {
      throw new \RuntimeException('Unable to complete AJAX request.');
    }
  }

  /**
   * @When I scroll :selector into view
   * @When I scroll selector :selector into view
   *
   * @param string $selector
   *   Allowed selectors: #id, .className, //xpath.
   *
   * @throws \Exception
   */
  public function scrollIntoView($selector) {
    $function = <<<JS
      (function() {
        jQuery("$selector").get(0).scrollIntoView(false);
      }());
JS;
    try {
      $this->getSession()->executeScript($function);
    }
    catch (Exception $e) {
      throw new \Exception(__METHOD__ . ' failed');
    }
  }

  /**
   * @Then /^I click on link with href "([^"]*)"$/
   * @Then /^I click on link with href value "([^"]*)"$/
   *
   * @param string $href
   *   The href value.
   */
  public function clickOnLinkWithHref(string $href) {
    $page = $this->getSession()->getPage();
    $link = $page->find('xpath', '//a[@href="' . $href . '"]');
    if ($link === NULL) {
      throw new \Exception('Link with href "' . $href . '" not found.');
    }
    $link->click();
  }

  /**
   * @Then /^I click on the horizontal tab "([^"]*)"$/
   * @Then /^I click on the horizontal tab with text "([^"]*)"$/
   *
   * @param string $text
   *   The text.
   */
  public function clickOnHorzTab(string $text) {
    $page = $this->getSession()->getPage();
    $link = $page->find('xpath', '//ul[contains(@class, "horizontal-tabs-list")]/li[contains(@class, "horizontal-tab-button")]/a/strong[text()="' . $text . '"]');
    if ($link === NULL) {
      throw new \Exception('The horizontal tab with text "' . $text . '" not found.');
    }
    $link->click();
  }

  /**
   * @Then /^I click on the detail "([^"]*)"$/
   * @Then /^I click on the detail with text "([^"]*)"$/
   *
   * @param string $text
   *   The text.
   */
  public function clickOnDetail(string $text) {
    $page = $this->getSession()->getPage();

    // Try finding summary that contains the exact text.
    $link = $page->find('xpath', '//details/summary[contains(text(), "' . $text . '")]');

    // If not found, try summary that contains a node with the text.
    if ($link === NULL) {
      $link = $page->find('xpath', '//details/summary[.//*[contains(text(), "' . $text . '")]]');
    }

    // If still not found, try summary that has the text at the beginning
    // (with potential spaces and child elements after)
    if ($link === NULL) {
      $link = $page->find('xpath', '//details/summary[starts-with(normalize-space(.), "' . $text . '")]');
    }

    if ($link === NULL) {
      throw new \Exception('The detail with text "' . $text . '" not found.');
    }

    $link->click();
  }

  /**
   * @Then /^I should find menu item text matching "([^"]*)"$/
   */
  public function findMenuItemMatchingText(string $text): void {
    $xpath = '//*/ul/li/a[text() = "' . $text . '"]';

    // Get the mink session.
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('xpath', $xpath)
    );

    if ($element === NULL) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate XPath: "%s"', $xpath));
    }
  }

  /**
   * Wait for the Batch API to finish.
   *
   * Wait until the id="updateprogress" element is gone,
   * or timeout after the given duration.
   *
   * @Given /^I wait for the batch process to finish for (\d+) seconds$/
   */
  public function iWaitForTheBatchProcessToFinish(int $seconds): void {
    $durationInMilliseconds = $seconds * 1000;
    $this->getSession()->wait($durationInMilliseconds, 'jQuery("#updateprogress").length === 0');
  }

}
