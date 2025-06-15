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
     * Retourne les contacts filtrés par recherche et/ou groupe.
     *
     * @param string|null $searchQuery Texte à rechercher
     * @param int|null $groupId ID du groupe sélectionné
     * @return Contact[]
     */
    public function findByFilters(?string $searchQuery, ?int $groupId): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.contactGroups', 'cg') // pour éviter les exclusions
            ->addSelect('cg') // optimisation si on affiche les groupes
            ->orderBy('c.firstName', 'ASC');

        if ($searchQuery) {
            $qb->andWhere('c.firstName LIKE :query OR c.lastName LIKE :query OR c.phoneNumber LIKE :query OR c.email LIKE :query')
               ->setParameter('query', '%' . $searchQuery . '%');
        }

        if ($groupId) {
            $qb->andWhere('cg.id = :groupId')
               ->setParameter('groupId', $groupId);
        }

        return $qb->getQuery()->getResult();
    }
}
