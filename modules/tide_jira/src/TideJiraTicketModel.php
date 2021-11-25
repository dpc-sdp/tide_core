<?php

namespace Drupal\tide_jira;

/**
 * Holds state for queued Jira tickets.
 */
class TideJiraTicketModel {

  /**
   * Name of the user.
   *
   * @var string
   */
  private string $name;
  /**
   * User's email address.
   *
   * @var string
   */
  private string $email;
  /**
   * User's department.
   *
   * @var string
   */
  private string $department;
  /**
   * Title field of the ticket.
   *
   * @var string
   */
  private string $title;
  /**
   * Ticket summary.
   *
   * @var string
   */
  private string $summary;
  /**
   * Revision ID.
   *
   * @var string
   */
  private string $id;
  /**
   * Moderation state.
   *
   * @var string
   */
  private string $moderationState;
  /**
   * Content type.
   *
   * @var string
   */
  private string $bundle;
  /**
   * Whether this is a new page.
   *
   * @var string
   */
  private string $isNew;
  /**
   * Update date.
   *
   * @var string
   */
  private string $updatedDate;
  /**
   * User account ID from Jira.
   *
   * @var string
   */
  private string $accountID;
  /**
   * Ticket description.
   *
   * @var string
   */
  private string $description;
  /**
   * Jira project.
   *
   * @var string
   */
  private string $project;

  /**
   * Constructs a new TideJiraTicketModel.
   *
   * @param string $name
   *   Name of the user.
   * @param string $email
   *   User's email address.
   * @param string $department
   *   User's department.
   * @param string $title
   *   Title field of the ticket.
   * @param string $summary
   *   Ticket summary.
   * @param string $id
   *   Revision ID.
   * @param string $moderation_state
   *   Moderation state.
   * @param string $bundle
   *   Content type.
   * @param string $is_new
   *   Whether this is a new page.
   * @param string $updated_date
   *   Update date.
   * @param string $account_id
   *   User account ID from Jira.
   * @param string $description
   *   Ticket description.
   * @param string $project
   *   Jira project.
   */
  public function __construct($name, $email, $department, $title, $summary, $id, $moderation_state, $bundle, $is_new, $updated_date, $account_id, $description, $project) {
    $this->name = $name;
    $this->email = $email;
    $this->department = $department;
    $this->title = $title;
    $this->summary = $summary;
    $this->id = $id;
    $this->moderationState = $moderation_state;
    $this->bundle = $bundle;
    $this->isNew = $is_new;
    $this->updatedDate = $updated_date;
    $this->accountID = $account_id;
    $this->description = $description;
    $this->project = $project;
  }

  /**
   * Get the project ID.
   *
   * @return string
   *   The project ID.
   */
  public function getProject(): string {
    return $this->project;
  }

  /**
   * Set the project ID.
   *
   * @param string $project
   *   The project ID.
   */
  public function setProject(string $project): void {
    $this->project = $project;
  }

  /**
   * Get the user's name.
   *
   * @return string
   *   The user's name.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Set the user's name.
   *
   * @param string $name
   *   The user's name.
   */
  public function setName(string $name): void {
    $this->name = $name;
  }

  /**
   * Get the user's email.
   *
   * @return string
   *   The user's email.
   */
  public function getEmail(): string {
    return $this->email;
  }

  /**
   * Set the user's email.
   *
   * @param string $email
   *   The user's email.
   */
  public function setEmail(string $email): void {
    $this->email = $email;
  }

  /**
   * Get the user's department.
   *
   * @return string
   *   The user's department.
   */
  public function getDepartment(): string {
    return $this->department;
  }

  /**
   * Set the user's department.
   *
   * @param string $department
   *   The user's department.
   */
  public function setDepartment(string $department): void {
    $this->department = $department;
  }

  /**
   * Get the ticket title.
   *
   * @return string
   *   The ticket title.
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * Set the ticket title.
   *
   * @param string $title
   *   The ticket title.
   */
  public function setTitle(string $title): void {
    $this->title = $title;
  }

  /**
   * Get the revision ID.
   *
   * @return string
   *   The revision ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Set the revision ID.
   *
   * @param string $id
   *   The revision ID.
   */
  public function setId(string $id): void {
    $this->id = $id;
  }

  /**
   * Get the moderation state.
   *
   * @return string
   *   The moderation state.
   */
  public function getModerationState(): string {
    return $this->moderationState;
  }

  /**
   * Set the moderation state.
   *
   * @param string $moderationState
   *   The moderation state.
   */
  public function setModerationState(string $moderationState): void {
    $this->moderationState = $moderationState;
  }

  /**
   * Get the content type.
   *
   * @return string
   *   The content type.
   */
  public function getBundle(): string {
    return $this->bundle;
  }

  /**
   * Set the content type.
   *
   * @param string $bundle
   *   The content type.
   */
  public function setBundle(string $bundle): void {
    $this->bundle = $bundle;
  }

  /**
   * Get whether the ticket is new.
   *
   * @return string
   *   Whether the ticket is new.
   */
  public function getIsNew(): string {
    return $this->isNew;
  }

  /**
   * Set whether the ticket is new.
   *
   * @param string $isNew
   *   Whether the ticket is new.
   */
  public function setIsNew(string $isNew): void {
    $this->isNew = $isNew;
  }

  /**
   * Get the updated date.
   *
   * @return string
   *   The updated date.
   */
  public function getUpdatedDate(): string {
    return $this->updatedDate;
  }

  /**
   * Set the updated date.
   *
   * @param string $updatedDate
   *   The updated date.
   */
  public function setUpdatedDate(string $updatedDate): void {
    $this->updatedDate = $updatedDate;
  }

  /**
   * Get the account ID.
   *
   * @return string
   *   The account ID.
   */
  public function getAccountId(): string {
    return $this->accountID;
  }

  /**
   * Set the account ID.
   *
   * @param string $accountID
   *   The account ID.
   */
  public function setAccountId(string $accountID): void {
    $this->accountID = $accountID;
  }

  /**
   * Get the description.
   *
   * @return string
   *   The description.
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * Set the description.
   *
   * @param string $description
   *   The description.
   */
  public function setDescription(string $description): void {
    $this->description = $description;
  }

  /**
   * Get the summary.
   *
   * @return string
   *   The summary.
   */
  public function getSummary(): string {
    return $this->summary;
  }

  /**
   * Set the summary.
   *
   * @param string $summary
   *   The summary.
   */
  public function setSummary(string $summary): void {
    $this->summary = $summary;
  }

}
