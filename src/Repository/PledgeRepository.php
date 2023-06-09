<?php

namespace App\Repository;

use App\Entity\GameRoom;
use App\Entity\Pledge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pledge>
 *
 * @method Pledge|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pledge|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pledge[]    findAll()
 * @method Pledge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PledgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pledge::class);
    }

    public function save(Pledge $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Pledge $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findParticipantPledgeIds(GameRoom $room): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id')
            ->innerJoin('p.owner', 'o')
            ->innerJoin('o.playedRooms', 'pr')
            ->where('pr.id = :roomId')
            ->setParameter('roomId', $room->getId())
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return Pledge[] Returns an array of Pledge objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Pledge
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
