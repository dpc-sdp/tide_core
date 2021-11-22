<?php

namespace Drupal\tide_jira;

class TideJiraTicketModel {

  private string $name;
  private string $email;
  private string $department;
  private string $title;
  private string $summary;
  private string $id;
  private string $moderation_state;
  private string $bundle;
  private string $is_new;
  private string $updated_date;
  private string $account_id;
  private string $description;
  private string $project;
  public function __construct($name, $email, $department, $title, $summary, $id, $moderation_state, $bundle, $is_new, $updated_date, $account_id, $description, $project) {
    $this->name = $name;
    $this->email = $email;
    $this->department = $department;
    $this->title = $title;
    $this->summary = $summary;
    $this->id = $id;
    $this->moderation_state = $moderation_state;
    $this->bundle = $bundle;
    $this->is_new = $is_new;
    $this->updated_date = $updated_date;
    $this->account_id = $account_id;
    $this->description = $description;
    $this->project = $project;
  }

  /**
   * @return string
   */
  public function getProject(): string {
    return $this->project;
  }

  /**
   * @param string $project
   */
  public function setProject(string $project): void {
    $this->project = $project;
  }

  /**
   * @return string
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName(string $name): void {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getEmail(): string {
    return $this->email;
  }

  /**
   * @param string $email
   */
  public function setEmail(string $email): void {
    $this->email = $email;
  }

  /**
   * @return string
   */
  public function getDepartment(): string {
    return $this->department;
  }

  /**
   * @param string $department
   */
  public function setDepartment(string $department): void {
    $this->department = $department;
  }

  /**
   * @return string
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle(string $title): void {
    $this->title = $title;
  }

  /**
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * @param string $id
   */
  public function setId(string $id): void {
    $this->id = $id;
  }

  /**
   * @return string
   */
  public function getModerationState(): string {
    return $this->moderation_state;
  }

  /**
   * @param string $moderation_state
   */
  public function setModerationState(string $moderation_state): void {
    $this->moderation_state = $moderation_state;
  }

  /**
   * @return string
   */
  public function getBundle(): string {
    return $this->bundle;
  }

  /**
   * @param string $bundle
   */
  public function setBundle(string $bundle): void {
    $this->bundle = $bundle;
  }

  /**
   * @return string
   */
  public function getIsNew(): string {
    return $this->is_new;
  }

  /**
   * @param string $is_new
   */
  public function setIsNew(string $is_new): void {
    $this->is_new = $is_new;
  }

  /**
   * @return string
   */
  public function getUpdatedDate(): string {
    return $this->updated_date;
  }

  /**
   * @param string $updated_date
   */
  public function setUpdatedDate(string $updated_date): void {
    $this->updated_date = $updated_date;
  }

  /**
   * @return string
   */
  public function getAccountId(): string {
    return $this->account_id;
  }

  /**
   * @param string $account_id
   */
  public function setAccountId(string $account_id): void {
    $this->account_id = $account_id;
  }

  /**
   * @return string
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * @param string $description
   */
  public function setDescription(string $description): void {
    $this->description = $description;
  }

  /**
   * @return string
   */
  public function getSummary(): string {
    return $this->summary;
  }

  /**
   * @param string $summary
   */
  public function setSummary(string $summary): void {
    $this->summary = $summary;
  }

}
