<?php

namespace Umanit\SamlBundle\Security\Http\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge\SamlAttributesBadge;

class CheckAttributesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [CheckPassportEvent::class => 'checkPassport'];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if (!$passport->hasBadge(SamlAttributesBadge::class)) {
            return;
        }

        /** @var SamlAttributesBadge $badge */
        $badge = $passport->getBadge(SamlAttributesBadge::class);
        $attributes = $badge->getAttributes();

        $attributeName = $badge->getGroupName();
        $attributeNeeded = $badge->getGroupRequired();

        if (!isset($attributes[$attributeName])) {
            throw new BadCredentialsException("Missing $attributeName attr.");
        }

        $attribute = $attributes[$attributeName];


        if (!\in_array($attributeNeeded, $attribute, true)) {
            throw new BadCredentialsException(
                sprintf('Attribute %s does not contain %s', $attributeName, $attributeNeeded)
            );
        }

        $badge->isResolved();
    }
}
