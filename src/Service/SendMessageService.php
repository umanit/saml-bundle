<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Protocol\SamlMessage;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

/**
 * @implements SendMessageServiceInterface<HttpFoundationResponse>
 */
class SendMessageService implements SendMessageServiceInterface
{
    public function __construct(
        protected readonly ConfigurationServiceInterface $configurationService,
        protected ?MessageContext $messageContext = null,
    ) {
        $this->messageContext = new MessageContext();
    }

    public function send(string $provider, SamlMessage $message, string $bindingType): HttpFoundationResponse
    {
        $messageContext = new MessageContext();
        $messageContext->setMessage($message);

        if ($this->messageContext->getMessage() instanceof SamlMessage) {
            $messageContext->getMessage()->setRelayState(
                $this->messageContext->getMessage()->getRelayState(),
            );
        }

        return (new BindingFactory())->create($bindingType)->send($messageContext);
    }
}
