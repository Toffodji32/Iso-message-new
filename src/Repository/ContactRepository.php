<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * @return Contact[] Returns an array of Contact objects based on filters
     */
    public function findByFilters(?string $searchQuery, ?int $groupId): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($searchQuery) {
            $qb->andWhere('c.firstName LIKE :query OR c.lastName LIKE :query OR c.phoneNumber LIKE :query OR c.email LIKE :query')
               ->setParameter('query', '%' . $searchQuery . '%');
        }

        if ($groupId) {
            // Utilise la relation Many-to-Many
            $qb->join('c.contactGroups', 'cg')
               ->andWhere('cg.id = :groupId')
               ->setParameter('groupId', $groupId);
        }

        return $qb->orderBy('c.firstName', 'ASC') // Tri par défaut
                  ->getQuery()
                  ->getResult();
    }

    // Vous pouvez laisser les méthodes findByExampleField et findOneBySomeField commentées,
    // ou les supprimer si vous ne les utilisez pas. L'important est que findByFilters soit actif.
    // /**
    //  * @return Contact[] Returns an array of Contact objects
    //  */
    // public function findByExampleField($value): array
    // {
    //    return $this->createQueryBuilder('c')
    //        ->andWhere('c.exampleField = :val')
    //        ->setParameter('val', $value)
    //        ->orderBy('c.id', 'ASC')
    //        ->setMaxResults(10)
    //        ->getQuery()
    //        ->getResult()
    //    ;
    // }

    // public function findOneBySomeField($value): ?Contact
    // {
    //    return $this->createQueryBuilder('c')
    //        ->andWhere('c.exampleField = :val')
    //        ->setParameter('val', $value)
    //        ->getQuery()
    //        ->getOneOrNullResult()
    //    ;
    // }
}
