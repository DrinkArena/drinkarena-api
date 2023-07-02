<?php

namespace App\Controller;

use App\Entity\PlayedPledge;
use App\Entity\User;
use App\Entity\GameRoom;
use App\Repository\PlayedPledgeRepository;
use App\Repository\PledgeRepository;
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
#[OA\Tag(name: 'Game Room')]
#[OA\Response(
    response: 400,
    description: 'Bad request'
)]
#[OA\Response(
    response: 401,
    description: 'Unauthorized'
)]
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

        // exit all rooms in which the user is currently enrolled
        foreach ($gameRoomRepository->checkToJoinGame($userRepository->find($this->getUser())) as $roomId) {
            $this->forward(
                'App\Controller\GameRoomController::leave',
                ['roomId' => $roomId]
            );
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
            ['accept' => 'application/json'],
            true
        );
    }

    #[Route('/{roomId<\d+>}/join', name: 'api.room.join', methods: ['GET'])]
    #[ParamConverter('room', options: ['id' => 'roomId'])]
    #[OA\Parameter(
        name: 'roomId',
        description: 'The game room ID',
        in: 'path',
        schema: new OA\Schema(type: 'integer')
    )]
    public function join(
        GameRoom                $room,
        EntityManagerInterface  $entityManager,
        GameRoomRepository      $gameRoomRepository
    ): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($this->getUser());

        // exit all rooms in which the user is currently enrolled
        foreach ($gameRoomRepository->checkToJoinGame($user) as $roomId) {
            $this->forward(
                'App\Controller\GameRoomController::leave',
                ['roomId' => $roomId]
            );
        }

        $room->addParticipant($user);
        $gameRoomRepository->save($room, true);

        return new JsonResponse(
            null,
            Response::HTTP_OK,
            ['accept' => 'application/json'],
            false
        );
    }

    #[Route('/{roomId<\d+>}/leave', name: 'api.room.leave', methods: ['GET'])]
    #[ParamConverter('room', options: ['id' => 'roomId'])]
    #[OA\Parameter(
        name: 'roomId',
        description: 'The game room ID',
        in: 'path',
        schema: new OA\Schema(type: 'integer')
    )]
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
            ['accept' => 'application/json'],
            false
        );
    }

    #[Route('/{roomId<\d+>}', name: 'api.room.get_by_id', methods: ['GET'])]
    #[ParamConverter('room', options: ['id' => 'roomId'])]
    #[OA\Parameter(
        name: 'roomId',
        description: 'The game room ID',
        in: 'path',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Get detailed room infos',
        content: new Model(type: GameRoom::class, groups: ['room:detail'])
    )]
    public function get_by_id(
        GameRoom                $room,
        SerializerInterface     $serializer
    ): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($room, 'json', SerializationContext::create()->setGroups(['room:detail'])),
            Response::HTTP_OK,
            ['accept' => 'application/json'],
            true
        );
    }

    #[Route('', name: 'api.room.search', methods: ['GET'])]
    #[OA\Parameter(
        name: 'page',
        description: 'Field to paginate data (offset)',
        in: 'query',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Field for max room displayed per request',
        in: 'query',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'name',
        description: 'Field to search explicit room name (case sensitivity)',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Field to order by room creation',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'status',
        description: 'Field to filter current room status',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Return a list of game rooms',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: GameRoom::class, groups: ['room:base']))
        )
    )]
    public function search(
        Request                 $request,
        GameRoomRepository      $gameRoomRepository,
        SerializerInterface     $serializer
    ): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $name = $request->query->get('name', '');
        $order = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $status = strtoupper($request->query->get('status', 'WAITING_PLAYER'));
        $limit = $limit > 40 ? 20 : $limit;
        in_array($status, ['WAITING_PLAYER', 'STARTED', 'FINISHED']) ? true : throw new BadRequestException('The status parameter is not valid');

        $criteria = ['state' => $status];
        if (!empty($name)) {
            $criteria['name'] = $name;
        }
        $rooms = $gameRoomRepository->findBy($criteria, ['createdAt' => $order], $limit, $page - 1);

        return new JsonResponse(
            $serializer->serialize($rooms, 'json', SerializationContext::create()->setGroups(['room:base'])),
            Response::HTTP_OK,
            ['accept' => 'application/json'],
            true
        );
    }

    #[Route('/{roomId<\d+>}/pledge/next', name: 'api.room.next_pledge', methods: ['GET'])]
    #[ParamConverter('room', options: ['id' => 'roomId'])]
    #[OA\Parameter(
        name: 'roomId',
        description: 'The game room ID',
        in: 'path',
        schema: new OA\Schema(type: 'integer')
    )]
    public function next_pledge(
        GameRoom                $room,
        SerializerInterface     $serializer,
        PledgeRepository        $pledgeRepository,
        GameRoomRepository      $gameRoomRepository,
        PlayedPledgeRepository  $playedPledgeRepository
    ): JsonResponse
    {
        if ($room->getOwner() !== $this->getUser()) {
            throw new BadRequestException('You are not the owner of the game room');
        }

        if ($room->getState() === 'FINISHED') {
            throw new BadRequestException('The game session is over');
        }

        $pledgeIds = $pledgeRepository->findParticipantPledgeIds($room);
        $pledgeRandIdx = array_rand($pledgeIds);
        $pledge = $pledgeRepository->find($pledgeIds[$pledgeRandIdx]);

        if ($room->getState() === 'WAITING_PLAYER') {
            $room->setState('STARTED');
        }

        if ($playedPledgeRepository->count(['room' => $room]) >= 29) {
            $room->setState('FINISHED');
        }

        $playedPledge = (new PlayedPledge())
            ->setRoom($room)
            ->setPledge($pledge);
        $gameRoomRepository->save($room, true);
        $playedPledgeRepository->save($playedPledge, true);

        // todo: count number of pledges elapsed in game session
        // todo: allow to add default pledge if there aren't enough user pledges

        return new JsonResponse(
            $serializer->serialize($pledge, 'json', SerializationContext::create()->setGroups(['pledge:detail'])),
            Response::HTTP_OK,
            ['accept' => 'application/json'],
            true
        );
    }
}
