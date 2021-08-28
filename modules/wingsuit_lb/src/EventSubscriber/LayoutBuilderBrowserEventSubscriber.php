<?php

namespace Drupal\wingsuit_lb\EventSubscriber;

use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\section_library\Entity\SectionLibraryTemplate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class LayoutBuilderBrowserEventSubscriber.
 *
 * Add layout builder css class layout-builder-browser.
 */
class LayoutBuilderBrowserEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;
  use AjaxHelperTrait;

  /**
   * Add layout-builder-browser class layout_builder.choose_block build block.
   */
  public function onView(GetResponseForControllerResultEvent $event) {
    $request = $event->getRequest();
    $route = $request->attributes->get('_route');
    if ($route == 'layout_builder.choose_section') {
      $build = $event->getControllerResult();

      $build['#attached']['library'][] = 'wingsuit_lb/core';
      $add_sections = [
        '#type' => 'details',
        '#title' => $this->t('Sections'),
        '#attributes' => [
          'class' => ['ws-lb ws-lb-container']
        ],
      ];
      $add_sections['items'] = $build['layouts']['#items'];
      foreach ($add_sections['items']  as &$item) {
        $item['#title']['icon']['#theme'] = 'wingsuit_lb_icon';
        $item['#title']['icon']['#icon_type']  = 'section';
        $item['#title']['label']['#attributes']['class'] = ['ws-lb-link__label'];
        $item['#attributes']['class'][] = 'ws-lb-link';
      }
      $request = $event->getRequest();
      /** @var \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage $section_storage */
      $section_storage = $request->attributes->get('section_storage');
      $delta = $request->attributes->get('delta');
      $add_library = [
        '#type' => 'details',
        '#title' => $this->t('Library'),
        '#attributes' => [
          'class' => ['ws-lb ws-lb-container']
        ],
      ];
      $add_library['items'] = $this->getLibrarySectionLinks(
        $section_storage,
        $delta
      );
      $add_library['#access'] = count($add_library['items']) > 0;
      unset($build['layouts']);
      $build['layouts'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['ws-tabs']
        ],
      ];
      $build['layouts']['tabs'] = [
        '#type' => 'horizontal_tabs',
        'items' => [
          $add_sections,
          $add_library,
        ],
      ];
      $event->setControllerResult($build);
    }

    if ($route == 'layout_builder.choose_block') {
      $build['#attached']['library'][] = 'wingsuit_lb/core';
      $build = $event->getControllerResult();
      if (is_array($build) && !isset($build['add_block'])) {
        $build['block_categories']['#type'] = 'horizontal_tabs';
        foreach (Element::children($build['block_categories']) as $child) {
          foreach (
            Element::children(
              $build['block_categories'][$child]['links']
            ) as $link_id
          ) {
            $link = &$build['block_categories'][$child]['links'][$link_id];
            $link['#attributes']['class'][] = 'ws-lb-link';
            $link['#title']['image']['#theme'] = 'wingsuit_lb_icon';
            $link['#title']['image']['#icon_type'] = 'block';
            $link['#title']['label']['#markup'] = '<div class="ws-lb-link__label">' . $link['#title']['label']['#markup'] . '</div>';
            if (($key = array_search(
                'layout-builder-browser-block-item',
                $link['#attributes']['class']
              )) !== FALSE) {
              unset($link['#attributes']['class'][$key]);
            }
          }
        }

        if (($key = array_search(
            'layout-builder-browser',
            $build['block_categories']['#attributes']['class']
          )) !== FALSE) {
          unset($build['block_categories']['#attributes']['class'][$key]);
        }

        $build['block_categories']['#attributes']['class'][] = 'ws-lb';
        $event->setControllerResult($build);
      }
    }
  }

  /**
   * Gets a render array of section links.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The region the section is going in.
   *
   * @return array
   *   The section links render array.
   */
  protected function getLibrarySectionLinks(
    SectionStorageInterface $section_storage,
    $delta
  ) {
    $sections = SectionLibraryTemplate::loadMultiple();
    $links = [];
    foreach ($sections as $section_id => $section) {
      $attributes = $this->getAjaxAttributes();
      $attributes['class'][] = 'js-layout-builder-section-library-link';
      $attributes['class'][] = 'ws-lb-link';
      // Default library image.
      $img_path = drupal_get_path(
          'module',
          'wingsuit_lb'
        ) . '/images/section-empty-icon.svg';
      if ($fid = $section->get('image')->target_id) {
        $file = File::load($fid);
        $img_path = $file->getFileUri();
      }

      $icon_url = file_url_transform_relative(file_create_url($img_path));
      $link = [
        '#type' => 'link',
        '#title' => Markup::create(
          '<div class="ws-lb__icon"><img src="' . $icon_url . '" class="section-library-link-img" /> </div>' . '<div class="ws-lb-link__label">' . $section->label(
          ) . '</div>'
        ),
        '#url' => Url::fromRoute(
          'section_library.import_section_from_library',
          [
            'section_library_id' => $section_id,
            'section_storage_type' => $section_storage->getStorageType(),
            'section_storage' => $section_storage->getStorageId(),
            'delta' => $delta,
          ]
        ),
        '#attributes' => $attributes,
      ];

      $links[] = $link;
    }
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onView', 45];
    return $events;
  }

  /**
   * Get dialog attributes if an ajax request.
   *
   * @return array
   *   The attributes array.
   */
  protected function getAjaxAttributes() {
    if ($this->isAjax()) {
      return [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'dialog',
        'data-dialog-renderer' => 'off_canvas',
      ];
    }
    return [];
  }


}
