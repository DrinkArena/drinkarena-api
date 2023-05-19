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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/user')]
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
        return new JsonResponse($userInfos, Response::HTTP_OK, ['accept' => 'json'], true);
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
        return new JsonResponse($userInfos, Response::HTTP_OK, ['accept' => 'json'], true);
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
        return new JsonResponse($userInfos, Response::HTTP_OK, ['accept' => 'json'], true);
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
}
