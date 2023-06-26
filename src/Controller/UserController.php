<?php

namespace App\Controller;

use App\Entity\User;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/user')]
#[OA\Tag(name: 'User')]
#[OA\Response(
    response: 400,
    description: 'Bad request'
)]
#[OA\Response(
    response: 401,
    description: 'Unauthorized'
)]
class UserController extends AbstractController
{
    #[Route('/{userId<\d+>}', name: 'api.user.get_by_id', methods: ['GET'])]
    #[ParamConverter('user', options: ['id' => 'userId'])]
    #[OA\Parameter(
        name: 'userId',
        description: 'Field for unique user identifier (integer)',
        in: 'path',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Return infos of one user by id',
        content: new Model(type: User::class, groups: ['user:base'])
    )]
    public function get_by_id(
        SerializerInterface $serializer,
        User                $user
    ): JsonResponse
    {
        $userInfos = $serializer->serialize(
            $user,
            'json',
            SerializationContext::create()->setGroups(['user:base'])
        );
        return new JsonResponse($userInfos, Response::HTTP_OK, ['accept' => 'application/json'], true);
    }

    #[Route('/me', name: 'api.user.get_self', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Return current connected user infos',
        content: new Model(type: User::class, groups: ['user:base'])
    )]
    public function get_self(
        SerializerInterface $serializer
    ): JsonResponse
    {
        $userInfos = $serializer->serialize(
            $this->getUser(),
            'json',
            SerializationContext::create()->setGroups(['user:base'])
        );
        return new JsonResponse($userInfos, Response::HTTP_OK, ['accept' => 'application/json'], true);
    }

    #[Route('s', name: 'api.user.get_all', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Response(
        response: 200,
        description: 'Return all users infos in list (only admin)',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['user:base']))
        )
    )]
    public function get_all(
        SerializerInterface $serializer,
        UserRepository      $userRepository
    ): JsonResponse
    {
        $userInfos = $serializer->serialize(
            $userRepository->findAll(),
            'json',
            SerializationContext::create()->setGroups(['user:base'])
        );
        return new JsonResponse($userInfos, Response::HTTP_OK, ['accept' => 'application/json'], true);
    }

    #[Route('', name: 'api.user.create', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Specify credentials you want to set for the user',
        content: new Model(type: User::class, groups: ['user:register'])
    )]
    #[OA\Response(
        response: 201,
        description: 'Create user account (registration)',
        content: new Model(type: User::class, groups: ['user:base'])
    )]
    public function create(
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface      $entityManager,
        SerializerInterface         $serializer,
        ValidatorInterface          $validator,
        Request                     $request
    ): JsonResponse
    {
        $userInput = $serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            DeserializationContext::create()->setGroups(['user:register'])
        );

        $errors = $validator->validate($userInput, null, ['user:register']);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $user = new User();
        $user->setUsername($userInput->getUsername())
            ->setPassword($userPasswordHasher->hashPassword($user, $userInput->getPassword()))
            ->setEmail($userInput->getEmail())
            ->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(
            $serializer->serialize($user, 'json', SerializationContext::create()->setGroups(['user:base'])),
            Response::HTTP_CREATED,
            ['accept' => 'json'],
            true
        );
    }

    #[Route('/request-forgot-password', name: 'api.user.request_forgot_password', methods: ['GET'])]
    #[OA\Parameter(
        name: 'email',
        description: 'Email of the user who lost his email',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Send email to recover user password'
    )]
    public function request_forgot_password(
        MailerInterface         $mailer,
        Request                 $request,
        UserRepository          $userRepository
    ): JsonResponse
    {
        $targetEmail = $request->query->get('email') ?? throw new BadRequestException('The email field is missing in query');
        $user = $userRepository->findOneBy(['email' => $targetEmail]);

        if (!$user instanceof User) {
            throw new BadRequestException('The account for this email does not exist');
        }

        $recoverCode = [];
        for ($i = 0; $i < 10; $i++) {
            $recoverCode[] = mt_rand(1, 9);
        }
        $recoverCode = implode('', $recoverCode);

        $user->setRecoveryCode($recoverCode);
        $user->setRecoveryCodeExpiration((new \DateTimeImmutable())->modify('+ 2 hour'));
        $userRepository->save($user, true);

        $email = (new Email())
            ->from('drinkarena.game@gmail.com')
            ->to($targetEmail)
            ->subject('Drink Arena - Mot de passe oublié')
            ->text('Votre code de récupération pour changer de mot de passe, il sera valable pendant 2 heures : ' . $recoverCode)
        ;

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new BadRequestException('Failed to send email with recovery code, err : ', $e->getMessage());
        }

        return new JsonResponse(null, Response::HTTP_OK, ['accept' => 'application/json'], false);
    }

    #[Route('/recover-password', name: 'api.user.recover_password', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Specify information to recover user password (no logged)',
        content: new Model(type: User::class, groups: ['user:recover-password'])
    )]
    #[OA\Response(
        response: 201,
        description: 'Send email to recover user password'
    )]
    public function recover_password(
        SerializerInterface         $serializer,
        Request                     $request,
        ValidatorInterface          $validator,
        UserRepository              $userRepository,
        UserPasswordHasherInterface $userPasswordHasher
    ): JsonResponse
    {
        $userInput = $serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            DeserializationContext::create()->setGroups(['user:recover-password'])
        );

        $errors = $validator->validate($userInput, null, ['user:recover-password']);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $user = $userRepository->findOneBy(['email' => $userInput->getEmail()]);

        if ($user->getRecoveryCode() !== $userInput->getRecoveryCode()) {
            throw new BadRequestException('The recovery code provided is wrong');
        }

        if ($user->getRecoveryCodeExpiration() < (new \DateTimeImmutable())) {
            throw new BadRequestException('The recovery code has expired');
        }

        $user->setPassword($userPasswordHasher->hashPassword($user, $userInput->getPassword()));
        $user->setRecoveryCode(null);
        $user->setRecoveryCodeExpiration(null);
        $userRepository->save($user, true);

        return new JsonResponse(null, Response::HTTP_OK, ['accept' => 'application/json'], false);
    }
}
