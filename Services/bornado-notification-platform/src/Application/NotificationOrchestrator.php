<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Application;

use Bornado\NotificationPlatform\Contracts\EventCatalog;
use Bornado\NotificationPlatform\Infrastructure\FileDeliveryLog;
use Bornado\NotificationPlatform\Infrastructure\ProviderAdapterInterface;
use Bornado\NotificationPlatform\Infrastructure\WhatsAppStateStore;

final class NotificationOrchestrator
{
    /**
     * @var PolicyEngine
     */
    private $policyEngine;

    /**
     * @var TemplateEngine
     */
    private $templateEngine;

    /**
     * @var FileDeliveryLog
     */
    private $deliveryLog;

    /**
     * @var array<string,ProviderAdapterInterface>
     */
    private $providers;

    /**
     * @var WhatsAppStateStore|null
     */
    private $whatsAppStateStore;

    /**
     * @param array<string,ProviderAdapterInterface> $providers
     */
    public function __construct(
        PolicyEngine $policyEngine,
        TemplateEngine $templateEngine,
        FileDeliveryLog $deliveryLog,
        array $providers,
        ?WhatsAppStateStore $whatsAppStateStore = null
    ) {
        $this->policyEngine       = $policyEngine;
        $this->templateEngine     = $templateEngine;
        $this->deliveryLog        = $deliveryLog;
        $this->providers          = $providers;
        $this->whatsAppStateStore = $whatsAppStateStore;
    }

    /**
     * @param array<string,mixed> $event
     * @return array<string,mixed>
     */
    public function handle(array $event): array
    {
        $eventId = (string) ($event['eventId'] ?? '');
        $attempts = array();

        if ('' !== $eventId && $this->deliveryLog->hasProcessed($eventId)) {
            return array(
                'status'  => 'duplicate',
                'eventId' => $eventId,
                'attempts'=> $attempts,
            );
        }

        $errors = EventCatalog::validate($event);
        if (!empty($errors)) {
            $this->deliveryLog->markEvent($event, 'invalid', array('errors' => $errors));

            return array(
                'status'  => 'invalid',
                'eventId' => $eventId,
                'errors'  => $errors,
                'attempts'=> $attempts,
            );
        }

        $plan = $this->policyEngine->buildPlan($event);
        if (empty($plan['plan'])) {
            $this->deliveryLog->markEvent($event, 'no_route', array('skipped' => $plan['skipped']));

            return array(
                'status'  => 'no_route',
                'eventId' => $eventId,
                'skipped' => $plan['skipped'],
                'attempts'=> $attempts,
            );
        }

        foreach ($plan['plan'] as $route) {
            $channel = (string) ($route['channel'] ?? '');
            $message = $this->templateEngine->render($event, $channel);

            if ('' === trim((string) ($message['body'] ?? ''))) {
                $this->deliveryLog->appendAttempt(
                    array(
                        'eventId' => $eventId,
                        'channel' => $channel,
                        'status'  => 'skipped',
                        'reason'  => 'missing_template',
                    )
                );
                $attempts[] = array(
                    'channel' => $channel,
                    'status'  => 'skipped',
                    'reason'  => 'missing_template',
                );
                continue;
            }

            foreach ((array) ($route['contacts'] ?? array()) as $contact) {
                foreach ((array) ($route['providers'] ?? array()) as $providerName) {
                    $provider = $this->providers[$providerName] ?? null;

                    if (!$provider instanceof ProviderAdapterInterface) {
                        $this->deliveryLog->appendAttempt(
                            array(
                                'eventId'  => $eventId,
                                'channel'  => $channel,
                                'provider' => $providerName,
                                'status'   => 'skipped',
                                'reason'   => 'provider_not_registered',
                            )
                        );
                        $attempts[] = array(
                            'channel'  => $channel,
                            'provider' => $providerName,
                            'status'   => 'skipped',
                            'reason'   => 'provider_not_registered',
                        );
                        continue;
                    }

                    $result = $provider->send(
                        $channel,
                        $contact,
                        $message,
                        array(
                            'eventId'   => $eventId,
                            'eventType' => (string) ($event['eventType'] ?? ''),
                            'event'     => $event,
                            'channel'   => $channel,
                        )
                    );

                    $attempt = array(
                        'eventId'     => $eventId,
                        'eventType'   => (string) ($event['eventType'] ?? ''),
                        'channel'     => $channel,
                        'provider'    => $provider->getName(),
                        'destination' => (string) ($contact['address'] ?? ''),
                        'status'      => !empty($result['success']) ? 'sent' : 'failed',
                        'result'      => $result,
                    );

                    $this->deliveryLog->appendAttempt($attempt);
                    $attempts[] = array(
                        'channel'     => $channel,
                        'provider'    => $provider->getName(),
                        'destination' => (string) ($contact['address'] ?? ''),
                        'status'      => !empty($result['success']) ? 'sent' : 'failed',
                        'result'      => $result,
                    );

                    if (
                        !empty($result['success'])
                        && 'whatsapp-cloud-api' === $provider->getName()
                        && $this->whatsAppStateStore instanceof WhatsAppStateStore
                    ) {
                        $this->whatsAppStateStore->recordOutboundDispatch($event, (array) $contact, $result);
                    }

                    if (!empty($result['success'])) {
                        $this->deliveryLog->markEvent(
                            $event,
                            'sent',
                            array(
                                'channel'  => $channel,
                                'provider' => $provider->getName(),
                            )
                        );

                        return array(
                            'status'  => 'sent',
                            'eventId' => $eventId,
                            'channel' => $channel,
                            'provider'=> $provider->getName(),
                            'attempts'=> $attempts,
                        );
                    }
                }
            }
        }

        $this->deliveryLog->markEvent($event, 'failed', array('reason' => 'all_routes_exhausted'));

        return array(
            'status'  => 'failed',
            'eventId' => $eventId,
            'attempts'=> $attempts,
        );
    }
}
