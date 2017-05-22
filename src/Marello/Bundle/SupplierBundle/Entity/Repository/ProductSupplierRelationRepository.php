<?php

namespace Marello\Bundle\SupplierBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Marello\Bundle\SupplierBundle\Entity\Supplier;

class ProductSupplierRelationRepository extends EntityRepository
{
    /**
     * Returns the product ids related to a given supplier id
     *
     * @param $supplierId
     * @return string
     */
    public function getProductIdsRelatedToSupplier($supplierId)
    {
        $qb = $this->createQueryBuilder('psr');
        $qb->select('p.id')
            ->leftJoin('psr.product','p')
            ->where('psr.supplier = :supplierId')
            ->setParameter('supplierId', $supplierId)
            ->groupBy('p.id')
            ;

        $query = $qb->getQuery();
        return implode(array_map('current', $query->getArrayResult()), ',');
    }
}
