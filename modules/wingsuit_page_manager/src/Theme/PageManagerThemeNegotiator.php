<?php

namespace Drupal\wingsuit_page_manager\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Sets the frontend theme to page manager layout builder page.
 */
class PageManagerThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PageManagerThemeNegotiator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $this->getActiveTheme($route_match) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->getActiveTheme($route_match);
  }

  /**
   * Determine the active theme for the current route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return string
   *   The active theme or an empty string.
   */
  protected function getActiveTheme(RouteMatchInterface $route_match) {
    if (gin_lb_is_layout_builder_route()) {
      return $this->configFactory->get('system.theme')->get('default');
    }
  }

}
