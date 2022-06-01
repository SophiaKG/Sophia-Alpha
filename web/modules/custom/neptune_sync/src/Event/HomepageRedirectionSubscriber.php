<?php

/***
 * on going to <front> if you are not anonymous, redirect
 * Source: https://drupal.stackexchange.com/questions/288707/different-front-page-for-logged-in-users
 * @todo migrate out oof module
 * @uses neptune_sync.services.yml.event_subscriber
 * @author Alexis harper | DoF
 */

namespace Drupal\neptune_sync\Event;

use Drupal\neptune_sync\Utility\Helper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class HomepageRedirectionSubscriber
 * @package Drupal\neptune_sync\Event
 * @author Alexis Harper | DoF
 *
 * A quick and dirty class for catching traffic routing to <home> then routing that
 *  traffic based on their login status.
 */
class HomepageRedirectionSubscriber implements EventSubscriberInterface {

    /**
     * @param GetResponseEvent $event
     * check if event is a route to front page, then redirects accordingly
     */
    public function checkFrontRedirection(GetResponseEvent $event) {
        if (\Drupal::service('path.matcher')->matchPath(
                    \Drupal::service('path.current')->getPath(),'/node/43151') &&
                !\Drupal::currentUser()->isAnonymous()) {

            $path =  \Drupal::service('path_alias.manager')->getAliasByPath('/welcome-beta');
            $event->setResponse(new RedirectResponse($path));
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        $events[KernelEvents::REQUEST][] = array('checkFrontRedirection');
        return $events;
    }

}