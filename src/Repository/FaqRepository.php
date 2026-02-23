<?php

namespace App\Repository;

use App\Entity\Admin\Faq\Faq;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Faq>
 */
class FaqRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faq::class);
    }

    public function findAllFaqsQuery(?string $question = null, array $types = [])
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.type', 't')
            ->addSelect('t')
            ->orderBy('f.id', 'ASC');

        $expr = $qb->expr();

        if ($question = trim((string) $question)) {
            $qb->andWhere(
                $expr->like('LOWER(f.question)', ':question')
            )
            ->setParameter('question', '%' . mb_strtolower($question) . '%');
        }

        if ($types = array_filter($types)) {
            $qb->andWhere(
                $expr->in('t.id', ':types')
            )
            ->setParameter('types', $types);
        }

        return $qb;
    }

    public function getFaqsGroupedByType(): array
    {
        $faqs = $this->createQueryBuilder('f')
            ->select('
                f.id,
                f.question,
                f.answer,
                t.name AS typeName,
                f.keywords
            ')
            ->leftJoin('f.type', 't')
            ->andWhere('t.isEnabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('t.sortOrder', 'ASC') 
            ->addOrderBy('f.id', 'DESC')
            ->getQuery()
            ->getArrayResult();   

        $faqGrouped = [];

        foreach ($faqs as $faq) {
            if (empty($faq['typeName'])) {
                continue; 
            }
            $faqGrouped[$faq['typeName']][] = $faq;
        }

        return $faqGrouped;
    }

    //    /**
    //     * @return Faq[] Returns an array of Faq objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Faq
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
