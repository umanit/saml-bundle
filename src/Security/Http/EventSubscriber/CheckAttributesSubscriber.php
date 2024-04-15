<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Security\Http\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Umanit\SamlBundle\Security\Http\Authenticator\Passport\Badge\SamlAttributesBadge;

use function in_array;

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

        foreach ($badge->getSamlRestrictions() as $restriction) {
            $attributeName = $restriction['attribute_name'];
            $attributeNeeded = $restriction['needed'];

            if (!isset($attributes[$attributeName])) {
                throw new BadCredentialsException(
                    sprintf('Attribute %s not found', $attributeName)
                );
            }

            $attribute = $attributes[$attributeName];

            if (is_string($attribute) && $attribute !== $attributeNeeded) {
                throw new BadCredentialsException(
                    sprintf('Attribute %s does not contain %s', $attributeName, $attributeNeeded)
                );
            }

            if (!in_array($attributeNeeded, $attribute, true)) {
                throw new BadCredentialsException(
                    sprintf('Attribute %s does not contain %s', $attributeName, $attributeNeeded)
                );
            }
        }
    }
}
