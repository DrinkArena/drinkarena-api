<?php

namespace App\DataFixtures;

use App\Entity\GameRoom;
use App\Entity\Pledge;
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
        $this->makePledge();
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
        $userEsteban = new User();
        $userEsteban
            ->setUsername('esteban')
            ->setPassword($this->userPasswordHasher->hashPassword($userEsteban, 'Test#69'))
            ->setEmail('esteban.ristich@protonmail.com')
            ->setRoles(['ROLE_USER'])
        ;
        $this->manager->persist($userEsteban);

        $userIsmael = new User();
        $userIsmael
            ->setUsername('ismael')
            ->setPassword($this->userPasswordHasher->hashPassword($userIsmael, 'Test#69'))
            ->setEmail('hacquin.ismael@gmail.com')
            ->setRoles(['ROLE_USER'])
        ;
        $this->manager->persist($userIsmael);

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

    private function makePledge(): void
    {
        $pledges = json_decode(file_get_contents(__DIR__ . '/pledge.json'));
        $allUser = $this->manager->getRepository(User::class)->findAll();
        for ($i = 0; $i < count($pledges); $i++) {
            $currentOwner = $this->faker->randomElement($allUser);
            if (random_int(1, 20) <= 4) {
                $currentOwner = null;
            }
            $pledge = (new Pledge())
                ->setTitle($pledges[$i])
                ->setOwner($currentOwner)
            ;
            $this->manager->persist($pledge);
        }
        $this->manager->flush();
    }
}
