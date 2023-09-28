<?php

/*
 * This file is part of the LightSAML SP-Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SpBundle\Controller;

use LightSaml\Builder\Profile\Metadata\MetadataProfileBuilder;
use LightSaml\Builder\Profile\WebBrowserSso\Sp\SsoSpSendAuthnRequestProfileBuilderFactory;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\PartyContainer;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\StoreContainer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends AbstractController
{
    private MetadataProfileBuilder $metadataProfileBuilder;
    private SsoSpSendAuthnRequestProfileBuilderFactory $profileLoginFactory;
    private StoreContainer $storeContainer;
    private PartyContainer $partyContainer;
    private string $discoveryRoute;

    public function __construct(
        MetadataProfileBuilder $metadataProfileBuilder,
        SsoSpSendAuthnRequestProfileBuilderFactory $profileLoginFactory,
        StoreContainer $storeContainer,
        PartyContainer $partyContainer,
        string $discoveryRoute
    ) {
        $this->metadataProfileBuilder = $metadataProfileBuilder;
        $this->profileLoginFactory = $profileLoginFactory;
        $this->storeContainer = $storeContainer;
        $this->discoveryRoute = $discoveryRoute;
        $this->partyContainer = $partyContainer;
    }

    public function metadataAction()
    {
        $context = $this->metadataProfileBuilder->buildContext();
        $action = $this->metadataProfileBuilder->buildAction();

        $action->execute($context);

        return $context->getHttpResponseContext()->getResponse();
    }

    public function discoveryAction()
    {
        $parties = $this->partyContainer->getIdpEntityDescriptorStore()->all();

        if (1 == count($parties)) {
            return $this->redirect($this->generateUrl('lightsaml_sp.login', ['idp' => $parties[0]->getEntityID()]));
        }

        return $this->render('@LightSamlSp/discovery.html.twig', [
            'parties' => $parties,
        ]);
    }

    public function loginAction(Request $request)
    {
        $idpEntityId = $request->get('idp');
        if (null === $idpEntityId) {
            return $this->redirect($this->generateUrl($this->discoveryRoute));
        }

        $profile = $this->profileLoginFactory->get($idpEntityId);
        $context = $profile->buildContext();
        $action = $profile->buildAction();

        $action->execute($context);

        return $context->getHttpResponseContext()->getResponse();
    }

    public function sessionsAction()
    {
        $ssoState = $this->storeContainer->getSsoStateStore()->get();

        return $this->render('@LightSamlSp/sessions.html.twig', [
            'sessions' => $ssoState->getSsoSessions(),
        ]);
    }
}
