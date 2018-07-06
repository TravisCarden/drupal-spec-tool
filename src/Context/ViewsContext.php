<?php

namespace Acquia\DrupalSpecTool\Context;

use Behat\Gherkin\Node\TableNode;
use TravisCarden\BehatTableComparison\TableEqualityAssertion;

/**
 * Provides Views Behat step definitions.
 */
class ViewsContext extends BaseContext {

  /**
   * The list of Views base tables.
   *
   * @var array|string[]
   */
  private $baseTables;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The Views display manager.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager
   */
  private $viewsDisplayManager;

  /**
   * The Views wizard manager.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager
   */
  private $viewsWizardManager;

  /**
   * Constructs a ContentModelContext.
   */
  public function __construct() {
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->viewsDisplayManager = \Drupal::getContainer()
      ->get('plugin.manager.views.display');
    $this->viewsWizardManager = \Drupal::getContainer()
      ->get('plugin.manager.views.wizard');
    $this->baseTables = $this->getBaseTables();
  }

  /**
   * Asserts the configuration of views.
   *
   * @Then exactly the following views should exist
   *
   * @throws \Exception
   */
  public function assertViewsExist(TableNode $expected) {
    $views_info = [];
    foreach ($this->getViews() as $id => $view) {
      $views_info[] = [
        $view->label(),
        $id,
        $this->getBaseTableLabel($view->get('base_table')),
        $view->status() ? 'Enabled' : 'Disabled',
        $view->get('description'),
      ];
    }
    $actual = new TableNode($views_info);

    (new TableEqualityAssertion($expected, $actual))
      ->expectHeader([
        'Name',
        'Machine name',
        'Base table',
        'Status',
        'Description',
      ])
      ->ignoreRowOrder()
      ->setMissingRowsLabel(self::missingRowsLabelFor('views'))
      ->setUnexpectedRowsLabel(self::unexpectedRowsLabelFor('views'))
      ->assert();
  }

  /**
   * Asserts the configuration of views displays.
   *
   * @Then exactly the following views displays should exist
   *
   * @throws \Exception
   */
  public function assertViewDisplaysExist(TableNode $expected) {
    $displays_info = [];
    foreach ($this->getViews() as $view) {
      /** @var array $display */
      foreach ($view->get('display') as $display) {
        $definition = $this->viewsDisplayManager
          ->getDefinition($display['display_plugin']);
        $displays_info[] = [
          $view->label(),
          $display['display_title'],
          $display['id'],
          (string) $definition['title'],
        ];
      }
    }
    $actual = new TableNode($displays_info);

    (new TableEqualityAssertion($expected, $actual))
      ->expectHeader([
        'View',
        'Title',
        'Machine name',
        'Display plugin',
      ])
      ->ignoreRowOrder()
      ->setMissingRowsLabel(self::missingRowsLabelFor('views'))
      ->setUnexpectedRowsLabel(self::unexpectedRowsLabelFor('views'))
      ->assert();
  }

  /**
   * Gets the list of Views base tables.
   *
   * @return array|string[]
   *   An array of base table human-readable labels keyed by the corresponding
   *   IDs.
   */
  private function getBaseTables(): array {
    $base_tables = [];

    /** @var array[] $wizard_plugins */
    $wizard_plugins = $this->viewsWizardManager->getDefinitions();
    foreach ($wizard_plugins as $wizard) {
      if (!array_key_exists('base_table', $wizard)) {
        continue;
      }
      $base_tables[$wizard['base_table']] = (string) $wizard['title'];
    }

    return $base_tables;
  }

  /**
   * Gets the human-readable label for a given base table ID.
   *
   * @param string $id
   *   A base table ID.
   *
   * @return string
   *   A human-readable label for the given base table ID if found, or the ID if
   *   not.
   */
  private function getBaseTableLabel($id) {
    return array_key_exists($id, $this->baseTables) ? $this->baseTables[$id] : $id;
  }

  /**
   * Gets all defined view objects.
   *
   * @return \Drupal\views\Entity\View[]
   *   Returns an array of view objects.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getViews() {
    /** @var \Drupal\views\Entity\View[] $views */
    $views = $this->entityTypeManager
      ->getStorage('view')
      ->loadMultiple();
    return $views;
  }

}