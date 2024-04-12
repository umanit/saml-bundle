<?php
declare(strict_types=1);

namespace Umanit\SamlBundle\Service;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Protocol\Response;
use Symfony\Component\HttpFoundation\Request;
use Umanit\SamlBundle\Validator\ResponseValidatorInterface;

class SamlMessageService implements SamlMessageServiceInterface
{
    public function __construct(
        protected ResponseValidatorInterface $responseValidator
    ) {
    }

    public function getSamlMessage(Request $request): MessageContext
    {
        $messageContext = new MessageContext();
        $bindingFactory = new BindingFactory();
        $bindingType = $bindingFactory->detectBindingType($request);
        $bindingFactory->create($bindingType)->receive($request, $messageContext);
        $messageContext->setBindingType($bindingType);

        return $messageContext;
    }

    public function validate(string $provider, Response $samlMessage, bool $strict = true): void
    {
        $this->responseValidator->validate($provider, $samlMessage, $strict);
    }
}
