<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('/api/v1/user')]
class UserController extends AbstractController
{
    #[Route('/{userId<\d+>}', name: 'api.user.get_by_id', methods: ['GET'])]
    #[ParamConverter('user', options: ['id' => 'userId'])]
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
}
