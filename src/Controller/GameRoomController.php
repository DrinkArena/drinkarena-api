<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\GameRoom;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use App\Repository\GameRoomRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use JMS\Serializer\DeserializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('/api/v1/room')]
class GameRoomController extends AbstractController
{
    // todo: return SSE data source in response
    #[Route('', name: 'api.room.create', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Specify only the name of your game room',
        content: new Model(type: GameRoom::class, groups: ['room:create'])
    )]
    #[OA\Response(
        response: 201,
        description: 'Create game room',
        content: new Model(type: GameRoom::class, groups: ['room:base'])
    )]
    public function create(
        Request                 $request,
        SerializerInterface     $serializer,
        ValidatorInterface      $validator,
        GameRoomRepository      $gameRoomRepository,
        EntityManagerInterface  $entityManager,
        UserRepository          $userRepository
    ): JsonResponse
    {
        $inputGameRoom = $serializer->deserialize(
            $request->getContent(),
            GameRoom::class,
            'json',
            DeserializationContext::create()->setGroups(['room:create'])
        );

        $errors = $validator->validate($inputGameRoom, null, ['room:create']);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Check if there are already game in progress
        if (!empty($gameRoomRepository->checkToJoinGame($userRepository->find($this->getUser())))) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST, [], false);
        }

        $user = $entityManager->getRepository(User::class)->find($this->getUser());
        $gameRoom = (new GameRoom())
            ->setName($inputGameRoom->getName())
            ->setOwner($user)
            ->addParticipant($user)
        ;
        $entityManager->persist($gameRoom);
        $entityManager->flush();

        return new JsonResponse(
            $serializer->serialize($gameRoom, 'json', SerializationContext::create()->setGroups(['room:base'])),
            Response::HTTP_CREATED,
            ['accept' => 'json'],
            true
        );
    }
}
