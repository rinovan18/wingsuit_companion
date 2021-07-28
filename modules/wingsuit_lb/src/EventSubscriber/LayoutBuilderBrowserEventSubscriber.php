<?php

namespace Drupal\wingsuit_lb\EventSubscriber;

use Drupal\Core\Render\Element;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class LayoutBuilderBrowserEventSubscriber.
 *
 * Add layout builder css class layout-builder-browser.
 */
class LayoutBuilderBrowserEventSubscriber implements EventSubscriberInterface {

  /**
   * Add layout-builder-browser class layout_builder.choose_block build block.
   */
  public function onView(GetResponseForControllerResultEvent $event) {
    $request = $event->getRequest();
    $route = $request->attributes->get('_route');
    if ($route == 'layout_builder.choose_section') {
      $build = $event->getControllerResult();
      $build['#attached']['library'][] = 'wingsuit_lb/core';

      if (($key = array_search('layout-selection', $build['layouts']['#attributes']['class'])) !== false) {
        unset($build['layouts']['#attributes']['class'][$key]);
      }
      unset($build['layouts']['#theme']);
      $build['layouts']['#type'] = 'container';
      $build['layouts']['items'] = $build['layouts']['#items'];
      $build['layouts']['#attributes']['class'][] = 'ws-lb ws-lb-container';
      $event->setControllerResult($build);
    }
    if ($route == 'layout_builder.choose_block') {
      $build['#attached']['library'][] = 'wingsuit_lb/core';
      $build = $event->getControllerResult();
      if (is_array($build) && !isset($build['add_block'])) {
        $build['block_categories']['#type'] = 'horizontal_tabs';
        foreach (Element::children($build['block_categories']) as $child) {
          foreach (Element::children($build['block_categories'][$child]['links']) as $link_id) {
            $link = &$build['block_categories'][$child]['links'][$link_id];
            $link['#title']['label']['#markup'] = '<div>' . $link['#title']['label']['#markup'] . '</div>';
            if (($key = array_search('layout-builder-browser-block-item', $link['#attributes']['class'])) !== false) {
              unset($link['#attributes']['class'][$key]);
            }
          }
        }

        if (($key = array_search('layout-builder-browser', $build['block_categories']['#attributes']['class'])) !== false) {
          unset($build['block_categories']['#attributes']['class'][$key]);
        }

        $build['block_categories']['#attributes']['class'][] = 'ws-lb';
        $event->setControllerResult($build);
      }
    }
    if ($route == 'section_library.choose_template_from_library') {
      $build = $event->getControllerResult();
      $build['#attached']['library'][] = 'wingsuit_lb/core';
      $build['sections']['#attributes']['class'] = ['ws-lb', 'ws-lb-container'];
      $build['sections']['#type'] = 'container';
      unset($build['sections']['#theme']);
      $build['sections']['items'] = $build['sections']['#links'];
      foreach ($build['sections']['items'] as &$link) {
        $link['#type'] = 'link';
        $link['#title'] = $link['title'];
        $link['#url'] = $link['url'];
        $link['#attributes'] = $link['attributes'];
        unset($link['url']);
        unset($link['title']);
        unset($link['attributes']);
      }
      $event->setControllerResult($build);

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onView', 45];
    return $events;
  }

}
