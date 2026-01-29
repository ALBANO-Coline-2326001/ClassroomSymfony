<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\ManyToOne(inversedBy: 'cours')]
    private ?Teacher $teacher = null;

    /**
     * @var Collection<int, Video>
     */
    #[ORM\OneToMany(targetEntity: Video::class, mappedBy: 'cours')]
    private Collection $videos;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'cours')]
    private Collection $documents;

    /**
     * @var Collection<int, Qcm>
     */
    #[ORM\OneToMany(targetEntity: Qcm::class, mappedBy: 'cours')]
    private Collection $qcms;

    public function __construct()
    {
        $this->videos = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->qcms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(?Teacher $teacher): static
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * @return Collection<int, Video>
     */
    public function getVideos(): Collection
    {
        return $this->videos;
    }

    public function addVideo(Video $video): static
    {
        if (!$this->videos->contains($video)) {
            $this->videos->add($video);
            $video->setCours($this);
        }

        return $this;
    }

    public function removeVideo(Video $video): static
    {
        if ($this->videos->removeElement($video)) {
            // set the owning side to null (unless already changed)
            if ($video->getCours() === $this) {
                $video->setCours(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setCours($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getCours() === $this) {
                $document->setCours(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Qcm>
     */
    public function getQcms(): Collection
    {
        return $this->qcms;
    }

    public function addQcm(Qcm $qcm): static
    {
        if (!$this->qcms->contains($qcm)) {
            $this->qcms->add($qcm);
            $qcm->setCours($this);
        }

        return $this;
    }

    public function removeQcm(Qcm $qcm): static
    {
        if ($this->qcms->removeElement($qcm)) {
            // set the owning side to null (unless already changed)
            if ($qcm->getCours() === $this) {
                $qcm->setCours(null);
            }
        }

        return $this;
    }
}
