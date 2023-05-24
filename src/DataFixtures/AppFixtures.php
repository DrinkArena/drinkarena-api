<?php

namespace App\DataFixtures;

use App\Entity\GameRoom;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private Faker\Generator $faker;
    private UserPasswordHasherInterface $userPasswordHasher;
    private ObjectManager $manager;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHasher,
    )
    {
        $this->faker = Faker\Factory::create('fr_FR');
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->makeAdmin();
        $this->makeUser(15);
        $this->makeRoom(20, 40);
    }

    private function makeAdmin(): void
    {
        $user = new User();
        $user
            ->setUsername('admin')
            ->setPassword($this->userPasswordHasher->hashPassword($user, 'Test#69'))
            ->setEmail('admin@eristich.dev')
            ->setRoles(['ROLE_ADMIN'])
        ;
        $this->manager->persist($user);
        $this->manager->flush();
    }

    private function makeUser(int $it): void
    {
        for ($i = 0; $i < $it; $i++) {
            $user = new User();
            $user
                ->setUsername($this->faker->userName())
                ->setPassword($this->userPasswordHasher->hashPassword($user, 'Test#69'))
                ->setEmail($this->faker->email())
                ->setRoles(['ROLE_USER'])
            ;
            $this->manager->persist($user);
        }
        $this->manager->flush();
    }

    private function makeRoom(int $rndMin, int $rndMax): void
    {
        $numRoom = random_int($rndMin, $rndMax);
        $allUser = $this->manager->getRepository(User::class)->findAll();
        for ($i = 0; $i < $numRoom; $i++) {
            $currentOwner = $this->faker->randomElement($allUser);
            $gameRoom = new GameRoom();
            $gameRoom
                ->setName('Room #'.$i + 1)
                ->setOwner($currentOwner)
                ->setState('FINISHED')
                ->addParticipant($currentOwner)
            ;
            $this->manager->persist($gameRoom);
        }
        $this->manager->flush();
    }
}
