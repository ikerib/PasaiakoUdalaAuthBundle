<?php

declare(strict_types=1);

namespace PasaiaUdala\AuthBundle\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
    ) {
    }

    public function selector(): Response
    {
        return $this->render('@PasaiakoUdalaAuth/auth_selector.html.twig');
    }

    public function loginLdap(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('@PasaiakoUdalaAuth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    public function oauthConnect(): RedirectResponse
    {
        return $this->clientRegistry->getClient('giltza')->redirect([], []);
    }

    /**
     * OAuth callback route — handled entirely by CertificateAuthenticator.
     * This action is never actually executed; it only exists so the route is defined.
     */
    public function oauthCheck(): void
    {
    }
}
