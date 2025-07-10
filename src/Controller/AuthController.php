<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TwitterOAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    #[Route('/auth/twitter', name: 'auth_twitter')]
    public function twitter(TwitterOAuthService $twitterOAuthService, Request $request): Response
    {
        $server = $twitterOAuthService->getServer();
        $temporaryCredentials = $server->getTemporaryCredentials();
        $request->getSession()->set('oauth_temp_credentials', serialize($temporaryCredentials));
        $authUrl = $server->getAuthorizationUrl($temporaryCredentials);

        return $this->redirect($authUrl);
    }

    #[Route('/auth/twitter/callback', name: 'auth_twitter_callback')]
    public function twitterCallback(
        TwitterOAuthService $twitterOAuthService,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $server = $twitterOAuthService->getServer();
        $tempCredentials = unserialize($request->getSession()->get('oauth_temp_credentials'));
        $tokenCredentials = $server->getTokenCredentials(
            $tempCredentials,
            $request->query->get('oauth_token'),
            $request->query->get('oauth_verifier')
        );
        $user = $server->getUserDetails($tokenCredentials);

        
        // Store or update user in DB
        $userEntity = $em->getRepository(User::class)->findOneBy(['twitterId' => $user->uid]);
        if (!$userEntity) {
            $userEntity = new User();
        }
        $userEntity->setName($user->name);
        $userEntity->setUsername($user->nickname);
        $userEntity->setEmail($user->email ?? '');
        $userEntity->setRole('ADMIN');
        $em->persist($userEntity);
        $em->flush();

        // Redirect back to the app (custom URL scheme or with a token)
        return $this->redirect('yourapp://auth/success?user_id=' . $userEntity->getId());
    }
}
