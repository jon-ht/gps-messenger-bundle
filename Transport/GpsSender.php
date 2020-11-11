<?php

declare(strict_types=1);

namespace PetitPress\GpsMessengerBundle\Transport;

use PetitPress\GpsMessengerBundle\Transport\Stamp\OrderingKeyStamp;
use Google\Cloud\PubSub\MessageBuilder;
use Google\Cloud\PubSub\PubSubClient;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * @author Ronald Marfoldi <ronald.marfoldi@petitpress.sk>
 */
final class GpsSender implements SenderInterface
{
    private PubSubClient $pubSubClient;
    private GpsConfigurationInterface $gpsConfiguration;
    private SerializerInterface $serializer;

    public function __construct(
        PubSubClient $pubSubClient,
        GpsConfigurationInterface $gpsConfiguration,
        SerializerInterface $serializer
    ) {
        $this->pubSubClient = $pubSubClient;
        $this->gpsConfiguration = $gpsConfiguration;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope): Envelope
    {
        $encodedMessage = $this->serializer->encode($envelope);

        $messageBuilder = new MessageBuilder();
        $messageBuilder = $messageBuilder->setData(json_encode($encodedMessage));

        /** @var OrderingKeyStamp|null $orderingKeyStamp */
        $orderingKeyStamp = $envelope->last(OrderingKeyStamp::class);
        if ($orderingKeyStamp) {
            $messageBuilder->setOrderingKey($orderingKeyStamp->getOrderingKey());
        }

        $this->pubSubClient
            ->topic($this->gpsConfiguration->getQueueName())
            ->publish($messageBuilder->build())
        ;

        return $envelope;
    }
}