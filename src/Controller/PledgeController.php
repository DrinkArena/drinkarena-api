<?php

namespace App\Controller;

use App\Entity\Pledge;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use App\Repository\PledgeRepository;
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

#[Route('/api/v1/pledge')]
#[OA\Tag(name: 'Pledge')]
#[OA\Response(
    response: 400,
    description: 'Bad request'
)]
#[OA\Response(
    response: 401,
    description: 'Unauthorized'
)]
class PledgeController extends AbstractController
{
    #[Route('', name: 'api.pledge.create', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Specify content of your pledge',
        content: new Model(type: Pledge::class, groups: ['pledge:create'])
    )]
    #[OA\Response(
        response: 201,
        description: 'Create pledge card',
        content: new Model(type: Pledge::class, groups: ['pledge:base'])
    )]
    public function create(
        Request                 $request,
        SerializerInterface     $serializer,
        ValidatorInterface      $validator,
        EntityManagerInterface  $entityManager,
        PledgeRepository        $pledgeRepository,
        UserRepository          $userRepository
    ): JsonResponse
    {
        $currentUser = $userRepository->find($this->getUser());
        $userPledgeCount = $pledgeRepository->count(['owner' => $currentUser]);
        if ($userPledgeCount >= 30) {
            throw new BadRequestException('Maximum number of pledges reached');
        }

        $inputPledge = $serializer->deserialize(
            $request->getContent(),
            Pledge::class,
            'json',
            DeserializationContext::create()->setGroups(['pledge:create'])
        );

        $errors = $validator->validate($inputPledge, null, ['pledge:create']);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $pledge = (new Pledge())
            ->setTitle($inputPledge->getTitle())
            ->setOwner($currentUser)
        ;
        $entityManager->persist($pledge);
        $entityManager->flush();

        return new JsonResponse(
            $serializer->serialize($pledge, 'json', SerializationContext::create()->setGroups(['pledge:base'])),
            Response::HTTP_CREATED,
            ['accept' => 'json'],
            true
        );
    }
}
