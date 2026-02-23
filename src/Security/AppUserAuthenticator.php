<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\RolesEnum;
use App\Enum\WholeSellerEnum;
use App\Service\StoreInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface as SessionFlashBagAwareSessionInterface;

class AppUserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;


    public function __construct(private readonly UrlGeneratorInterface $urlGenerator, private readonly EntityManagerInterface $entityManager, private readonly StoreInfoService $storeInfoService,)
    {
    }

    public function supports(Request $request): bool
    {
        if($request->getPathInfo() === WholeSellerEnum::LOGIN_ROUTE_PATH->value){
            return $request->isMethod('POST') && $request->getPathInfo() === WholeSellerEnum::LOGIN_ROUTE_PATH->value;
        } else {
            return $request->isMethod('POST') && $request->getPathInfo() === WholeSellerEnum::WHOLE_SELLER_LOGIN_PATH->value;
        }
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username', '');
        $path = $request->getPathInfo();

        if (empty($username)) {
            throw new BadCredentialsException('Username cannot be empty.');
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username, function ($userIdentifier) use ($path) {
                $user = $this->entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['username' => $userIdentifier]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Invalid credentials.');
                }

                $userRoles = $user->getRoles();

                if ($path === WholeSellerEnum::WHOLE_SELLER_LOGIN_PATH->value) {
                    if (!in_array(RolesEnum::WHOLE_SELLER->value, $userRoles, true)) {
                        throw new CustomUserMessageAuthenticationException(
                            'Access denied. You are not a wholeseller.'
                        );
                    }

                    $status = $user->getWholeSellerStatus();

                    if ($status === WholeSellerEnum::PENDING) {
                        throw new CustomUserMessageAuthenticationException(
                            'Your wholeseller account verification is in progress. Please wait for approval.'
                        );
                    }

                    if ($status === WholeSellerEnum::REJECTED) {
                        throw new CustomUserMessageAuthenticationException(
                            'Your wholeseller account has been rejected. Please contact support.'
                        );
                    }
                }

                if ($path === WholeSellerEnum::WHOLE_SELLER_LOGIN_PATH->value && !in_array(RolesEnum::WHOLE_SELLER->value, $userRoles, true)) {
                    throw new CustomUserMessageAuthenticationException('Access denied. You are not a wholeseller.');
                }

                if ($path === WholeSellerEnum::LOGIN_ROUTE_PATH->value && !in_array('ROLE_USER', $userRoles, true)) {
                    throw new CustomUserMessageAuthenticationException('Access denied. You are not a regular user.');
                }

                return $user;
            }),

            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $user->setLastLoginAt(new \DateTimeImmutable());
        $this->entityManager->persist($token->getUser());
        $this->entityManager->flush();

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('my_account'));
    }

    protected function getLoginUrl(Request $request): string
    {
        $path = $request->getPathInfo();

        if ($path === WholeSellerEnum::WHOLE_SELLER_LOGIN_PATH->value) {
            return $this->urlGenerator->generate(WholeSellerEnum::WHOLE_SELLER_LOGIN_ROUTE->value);
        }

        return $this->urlGenerator->generate(WholeSellerEnum::LOGIN_ROUTE->value);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $session = $request->getSession();

        if ($session instanceof SessionFlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('danger', $exception->getMessage());
        }

        return new RedirectResponse($this->getLoginUrl($request));
    }

}
