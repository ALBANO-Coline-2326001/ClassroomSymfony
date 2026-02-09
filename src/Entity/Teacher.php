<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource; // Ajouté
use App\Repository\TeacherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups; // Ajouté

#[ORM\Entity(repositoryClass: TeacherRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read', 'teacher:read']]
)]
class Teacher extends User
{
    /**
     * @var Collection<int, Cours>
     */
    #[ORM\OneToMany(targetEntity: Cours::class, mappedBy: 'teacher')]
    #[Groups(['teacher:read'])] // Pour voir les cours créés par ce prof
    private Collection $cours;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
    }

    /**
     * @return Collection<int, Cours>
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
            $cour->setTeacher($this);
        }

        return $this;
    }

    public function removeCour(Cours $cour): static
    {
        if ($this->cours->removeElement($cour)) {
            // set the owning side to null (unless already changed)
            if ($cour->getTeacher() === $this) {
                $cour->setTeacher(null);
            }
        }

        return $this;
    }
}
