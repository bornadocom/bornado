<?php
declare(strict_types=1);

namespace Bornado\NotificationPlatform\Infrastructure;

final class WhatsAppWebhookHandler
{
    /**
     * @var array<string,mixed>
     */
    private $config;

    /**
     * @var WhatsAppStateStore
     */
    private $stateStore;

    public function __construct(array $config, WhatsAppStateStore $stateStore)
    {
        $this->config     = $config;
        $this->stateStore = $stateStore;
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function handleVerification(array $query): array
    {
        $mode        = trim((string) ($query['hub_mode'] ?? $query['hub.mode'] ?? ''));
        $verifyToken = trim((string) ($query['hub_verify_token'] ?? $query['hub.verify_token'] ?? ''));
        $challenge   = trim((string) ($query['hub_challenge'] ?? $query['hub.challenge'] ?? ''));
        $expected    = trim((string) ($this->config['verify_token'] ?? ''));

        if ('subscribe' !== $mode || '' === $expected || !hash_equals($expected, $verifyToken)) {
            return array(
                'status' => 403,
                'body'   => 'Forbidden',
                'type'   => 'text/plain; charset=utf-8',
            );
        }

        return array(
            'status' => 200,
            'body'   => $challenge,
            'type'   => 'text/plain; charset=utf-8',
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function handleNotification(string $rawBody, string $signatureHeader): array
    {
        $expectedAppSecret = trim((string) ($this->config['app_secret'] ?? ''));
        if ('' === $expectedAppSecret) {
            return array(
                'status' => 503,
                'body'   => array('message' => 'Webhook app secret is not configured.'),
            );
        }

        if (!$this->isSignatureValid($rawBody, $signatureHeader, $expectedAppSecret)) {
            return array(
                'status' => 401,
                'body'   => array('message' => 'Invalid webhook signature.'),
            );
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return array(
                'status' => 400,
                'body'   => array('message' => 'Invalid JSON payload.'),
            );
        }

        $statusUpdates = 0;
        $inboundMessages = 0;
        $ignoredChanges = 0;

        foreach ((array) ($payload['entry'] ?? array()) as $entry) {
            foreach ((array) ($entry['changes'] ?? array()) as $change) {
                $value = isset($change['value']) && is_array($change['value']) ? $change['value'] : array();

                if (!$this->matchesPhoneNumber($value)) {
                    $ignoredChanges++;
                    continue;
                }

                foreach ((array) ($value['statuses'] ?? array()) as $status) {
                    if (!is_array($status)) {
                        continue;
                    }

                    $this->stateStore->applyStatusUpdate(
                        $status,
                        array(
                            'metadata' => $value['metadata'] ?? array(),
                        )
                    );
                    $statusUpdates++;
                }

                foreach ((array) ($value['messages'] ?? array()) as $message) {
                    if (!is_array($message)) {
                        continue;
                    }

                    $this->stateStore->recordInboundMessage(
                        $message,
                        array(
                            'contacts' => $value['contacts'] ?? array(),
                            'metadata' => $value['metadata'] ?? array(),
                            'mode'     => (string) ($this->config['inbound_mode'] ?? 'log_only'),
                        )
                    );
                    $inboundMessages++;
                }
            }
        }

        return array(
            'status' => 200,
            'body'   => array(
                'message'         => 'Webhook accepted.',
                'statusUpdates'   => $statusUpdates,
                'inboundMessages' => $inboundMessages,
                'ignoredChanges'  => $ignoredChanges,
                'inboundMode'     => (string) ($this->config['inbound_mode'] ?? 'log_only'),
            ),
        );
    }

    private function matchesPhoneNumber(array $value): bool
    {
        $expectedPhoneNumberId = trim((string) ($this->config['phone_number_id'] ?? ''));
        if ('' === $expectedPhoneNumberId) {
            return true;
        }

        $actualPhoneNumberId = trim((string) (($value['metadata']['phone_number_id'] ?? '')));

        return '' !== $actualPhoneNumberId && hash_equals($expectedPhoneNumberId, $actualPhoneNumberId);
    }

    private function isSignatureValid(string $rawBody, string $signatureHeader, string $appSecret): bool
    {
        $signatureHeader = trim($signatureHeader);
        if ('' === $signatureHeader || 0 !== strpos($signatureHeader, 'sha256=')) {
            return false;
        }

        $provided = substr($signatureHeader, 7);
        $expected = hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $provided);
    }
}
