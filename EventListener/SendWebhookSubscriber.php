<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticExtendeeWebhookResponseBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\WebhookBundle\Event\SendWebhookEvent;
use MauticPlugin\MauticExtendeeWebhookResponseBundle\Form\Type\CampaignEventSendWebhookResponseType;
use MauticPlugin\MauticExtendeeWebhookResponseBundle\WebhookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SendWebhookSubscriber implements EventSubscriberInterface
{
    /**
     * @var RealTimeExecutioner
     */
    private $executioner;

    /**
     * SendWebhookSubscriber constructor.
     *
     * @param RealTimeExecutioner $executioner
     */
    public function __construct(RealTimeExecutioner $executioner)
    {
        $this->executioner = $executioner;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                    => ['onCampaignBuild', 0],
            \Mautic\WebhookBundle\WebhookEvents::ON_WEBHOOK_RESPONSE => ['onSendWebhook', 0],
            WebhookEvents::ON_CAMPAIGN_TRIGGER_DECISION          => ['onCampaignTriggerDecision', 0],
        ];
    }

    /**
     * Add event triggers and actions.
     *
     * @param Events\CampaignBuilderEvent $event
     */
    public function onCampaignBuild(Events\CampaignBuilderEvent $event)
    {
        //Add action to remote url call
        $sendWebhookDecision = [
            'label'                  => 'mautic.plugin.webhook.event.sendwebhook.response',
            'description'            => 'mautic.plugin.webhook.event.sendwebhook_desc.response',
            'formType'               => CampaignEventSendWebhookResponseType::class,
            'eventName'              => WebhookEvents::ON_CAMPAIGN_TRIGGER_DECISION,
            'connectionRestrictions' => [
                'source' => [
                    'action' => [
                        'campaign.sendwebhook',
                    ],
                ],
            ],
        ];
        $event->addDecision('plugin.webhook.response', $sendWebhookDecision);
    }

    /**
     * @param SendWebhookEvent $event
     */
    public function onSendWebhook(SendWebhookEvent $event)
    {
        $this->executioner->execute('plugin.webhook.response', $event->getResponse()->body);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerDecision(CampaignExecutionEvent $event)
    {
        $eventDetails = $event->getEventDetails();
        $config       = $event->getConfig();

        if (!$event->checkContext('plugin.webhook.response')) {
            return false;
        }

        return $event->setResult(true);

        if ($config['match'] === $eventDetails) {
            return $event->setResult(true);
        }

        return $event->setResult(false);
    }
}
