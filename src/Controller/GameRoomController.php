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

    #[Route('/{roomId<\d+>}/join', name: 'api.room.join', methods: ['GET'])]
    #[ParamConverter('room', options: ['id' => 'roomId'])]
    public function join(
        GameRoom                $room,
        EntityManagerInterface  $entityManager,
        GameRoomRepository      $gameRoomRepository
    ): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($this->getUser());
        if (!empty($gameRoomRepository->checkToJoinGame($user))) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST, [], false);
        }

        $room->addParticipant($user);
        $gameRoomRepository->save($room, true);

        return new JsonResponse(
            null,
            Response::HTTP_OK,
            ['accept' => 'json'],
            false
        );
    }

    #[Route('/{roomId<\d+>}/leave', name: 'api.room.leave', methods: ['GET'])]
    #[ParamConverter('room', options: ['id' => 'roomId'])]
    public function leave(
        GameRoom                $room,
        EntityManagerInterface  $entityManager,
        GameRoomRepository      $gameRoomRepository
    ): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($this->getUser());
        if ($room->getOwner() === $user && $room->getState() !== 'FINISHED') {
            $room->setState('FINISHED');
        }

        $room->removeParticipant($user);
        $gameRoomRepository->save($room, true);

        return new JsonResponse(
            null,
            Response::HTTP_OK,
            ['accept' => 'json'],
            false
        );
    }

    #[Route('/{roomId<\d+>}', name: 'api.room.get_by_id', methods: ['GET'])]
    #[ParamConverter('room', options: ['id' => 'roomId'])]
    public function get_by_id(
        GameRoom                $room,
        SerializerInterface     $serializer
    ): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($room, 'json', SerializationContext::create()->setGroups(['room:detail'])),
            Response::HTTP_OK,
            ['accept' => 'json'],
            true
        );
    }
}
