<?php

namespace App\DataFixtures;

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
    }

    private function makeAdmin(): void
    {
        $user = new User();
        $user
            ->setUsername('admin')
            ->setPassword($this->userPasswordHasher->hashPassword($user, 'Test#69'))
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
                ->setRoles(['ROLE_USER'])
            ;
            $this->manager->persist($user);
        }
        $this->manager->flush();
    }
}
