<?php

declare(strict_types=1);

namespace Umanit\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RedirectionAction extends AbstractController
{
    #[Route('redirection', name: 'umanit_saml_redirection')]
    public function __invoke(): Response
    {
        return $this->render('redirection.html.twig');
    }
}
