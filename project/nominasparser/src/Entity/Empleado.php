<?php

namespace App\Entity;

use App\Repository\EmpleadoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmpleadoRepository::class)]
#[ORM\Table(name: 'empleado')]
#[ORM\Index(columns: ['apellidos'], name: 'IDX_D9D9BF52D9A2AA6D')]
class Empleado
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $apellidos = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $periodo = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $dni = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): self
    {
        $this->apellidos = $apellidos;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPeriodo(): ?string
    {
        return $this->periodo;
    }

    public function setPeriodo(?string $periodo): self
    {
        $this->periodo = $periodo;

        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(?string $dni): self
    {
        $this->dni = $dni;

        return $this;
    }

    public function __toString(): string
    {
        return ($this->apellidos ?? '') . ', ' . ($this->nombre ?? '');
    }
}
