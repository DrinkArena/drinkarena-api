<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\GameRoom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameRoom>
 *
 * @method GameRoom|null find($id, $lockMode = null, $lockVersion = null)
 * @method GameRoom|null findOneBy(array $criteria, array $orderBy = null)
 * @method GameRoom[]    findAll()
 * @method GameRoom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameRoom::class);
    }

    public function save(GameRoom $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GameRoom $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function checkToJoinGame(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.id')
            ->innerJoin('r.participants', 'p')
            ->where('p.id = :userId')
            ->andWhere('r.state != :roomState')
            ->setParameter('userId', $user->getId())
            ->setParameter('roomState', 'FINISHED')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findUserElapsedGameHistory(User $user): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.participants', 'p')
            ->addSelect('p')
            ->where('p.id = :userId')
            ->andWhere('u.state = :roomState')
            ->setParameter('userId', $user->getId())
            ->setParameter('roomState', 'FINISHED')
            ->getQuery()
            ->getResult()
            ;
    }

//    /**
//     * @return GameRoom[] Returns an array of GameRoom objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?GameRoom
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
