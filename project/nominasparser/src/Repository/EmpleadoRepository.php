<?php

namespace App\Repository;

use App\Entity\Empleado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Empleado|null find($id, $lockMode = null, $lockVersion = null)
 * @method Empleado|null findOneBy(array $criteria, array $orderBy = null)
 * @method Empleado[]    findAll()
 * @method Empleado[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmpleadoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Empleado::class);
    }

    public function save(Empleado $empleado): void
    {
        $this->_em->persist($empleado);
        $this->_em->flush();
    }

    /**
     * Busca un empleado por apellidos exactos y nombre que contenga el texto dado.
     * Fallback cuando el PDF trunca el nombre (ej: "JOSE MIGUE" vs "JOSE MIGUEL").
     */
    public function findOneByApellidosAndNombreContains(string $apellidos, string $nombre): ?Empleado
    {
        $result = $this->createQueryBuilder('e')
            ->andWhere('e.apellidos = :apellidos')
            ->andWhere('e.nombre LIKE :nombre')
            ->setParameter('apellidos', $apellidos)
            ->setParameter('nombre', '%' . $nombre . '%')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $result[0] ?? null;
    }
}