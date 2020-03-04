<?php

namespace Drupal\vipps_recurring_payments\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\vipps_recurring_payments\Entity\MonthlyChargesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MonthlyChargesController.
 *
 *  Returns responses for Monthly charges routes.
 */
class MonthlyChargesController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Monthly charges revision.
   *
   * @param int $monthly_charges_revision
   *   The Monthly charges revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($monthly_charges_revision) {
    $monthly_charges = $this->entityTypeManager()->getStorage('monthly_charges')
      ->loadRevision($monthly_charges_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('monthly_charges');

    return $view_builder->view($monthly_charges);
  }

  /**
   * Page title callback for a Monthly charges revision.
   *
   * @param int $monthly_charges_revision
   *   The Monthly charges revision ID.
   *
   * @return string
   *   The page title.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($monthly_charges_revision) {
    $monthly_charges = $this->entityTypeManager()->getStorage('monthly_charges')
      ->loadRevision($monthly_charges_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $monthly_charges->label(),
      '%date' => $this->dateFormatter->format($monthly_charges->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Monthly charges.
   *
   * @param \Drupal\vipps_recurring_payments\Entity\MonthlyChargesInterface $monthly_charges
   *   A Monthly charges object.
   *
   * @return array
   *   An array as expected by drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionOverview(MonthlyChargesInterface $monthly_charges) {
    $account = $this->currentUser();
    $monthly_charges_storage = $this->entityTypeManager()->getStorage('monthly_charges');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $monthly_charges->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all monthly charges revisions") || $account->hasPermission('administer monthly charges entities')));
    $delete_permission = (($account->hasPermission("delete all monthly charges revisions") || $account->hasPermission('administer monthly charges entities')));

    $rows = [];

    $vids = $monthly_charges_storage->revisionIds($monthly_charges);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\vipps_recurring_payments\MonthlyChargesInterface $revision */
      $revision = $monthly_charges_storage->loadRevision($vid);
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $monthly_charges->getRevisionId()) {
          $link = $this->l($date, new Url('entity.monthly_charges.revision', [
            'monthly_charges' => $monthly_charges->id(),
            'monthly_charges_revision' => $vid,
          ]));
        }
        else {
          $link = $monthly_charges->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.monthly_charges.revision_revert', [
                'monthly_charges' => $monthly_charges->id(),
                'monthly_charges_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.monthly_charges.revision_delete', [
                'monthly_charges' => $monthly_charges->id(),
                'monthly_charges_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
    }

    $build['monthly_charges_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
