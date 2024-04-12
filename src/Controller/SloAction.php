<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Umanit\SamlBundle\Service\SloServiceInterface;

#[Route('slo/{provider<\w+>}', name: 'umanit_saml_slo', methods: ['GET', 'POST'])]
class SloAction extends AbstractController
{
    public function __invoke(
        string $provider,
        SloServiceInterface $sloService,
        Request $request,
    ): Response {
        dump('je passe');
        $response = $sloService->logoutDepuislapp($provider, $this->getUser());
        // dd('stop');
        // $response = $sloService->logout($request, $provider, $this->getUser());

        if (null === $response) {
            throw new LogoutException("Unable to log out user");
        }

        return $response;
    }
}
