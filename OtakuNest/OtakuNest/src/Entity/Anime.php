<?php

namespace App\Entity;

use App\Repository\AnimeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AnimeRepository::class)]
class Anime
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $year = null;

    #[ORM\Column(length: 30)]
    private ?string $status = 'announced';

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalEpisodes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extraPayload = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $externalId = null;

    /**
     * @var Collection<int, Episode>
     */
    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: Episode::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['number' => 'ASC'])]
    private Collection $episodes;

    /**
     * @var Collection<int, Genre>
     */
    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'animes')]
    #[ORM\JoinTable(name: 'anime_genre')]
    private Collection $genres;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: Favorite::class, orphanRemoval: true)]
    private Collection $favorites;

    /**
     * @var Collection<int, LibraryItem>
     */
    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: LibraryItem::class, orphanRemoval: true)]
    private Collection $libraryItems;

    /**
     * @var Collection<int, ApiUpdate>
     */
    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: ApiUpdate::class, orphanRemoval: true)]
    private Collection $apiUpdates;

    public function __construct()
    {
        $this->episodes = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->libraryItems = new ArrayCollection();
        $this->apiUpdates = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = strtolower($slug);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(?string $coverUrl): self
    {
        $this->coverUrl = $coverUrl;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): self
    {
        $this->rating = $rating;

        return $this;
    }

    public function getTotalEpisodes(): ?int
    {
        return $this->totalEpisodes;
    }

    public function setTotalEpisodes(?int $totalEpisodes): self
    {
        $this->totalEpisodes = $totalEpisodes;

        return $this;
    }

    public function getExtraPayload(): ?array
    {
        return $this->extraPayload;
    }

    public function setExtraPayload(?array $extraPayload): self
    {
        $this->extraPayload = $extraPayload;

        return $this;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): self
    {
        $this->lastSyncedAt = $lastSyncedAt;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return Collection<int, Episode>
     */
    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public function addEpisode(Episode $episode): self
    {
        if (!$this->episodes->contains($episode)) {
            $this->episodes->add($episode);
            $episode->setAnime($this);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): self
    {
        if ($this->episodes->removeElement($episode)) {
            if ($episode->getAnime() === $this) {
                $episode->setAnime(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): self
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
            $genre->addAnime($this);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): self
    {
        if ($this->genres->removeElement($genre)) {
            $genre->removeAnime($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setAnime($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): self
    {
        if ($this->favorites->removeElement($favorite)) {
            if ($favorite->getAnime() === $this) {
                $favorite->setAnime(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LibraryItem>
     */
    public function getLibraryItems(): Collection
    {
        return $this->libraryItems;
    }

    public function addLibraryItem(LibraryItem $item): self
    {
        if (!$this->libraryItems->contains($item)) {
            $this->libraryItems->add($item);
            $item->setAnime($this);
        }

        return $this;
    }

    public function removeLibraryItem(LibraryItem $item): self
    {
        if ($this->libraryItems->removeElement($item)) {
            if ($item->getAnime() === $this) {
                $item->setAnime(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApiUpdate>
     */
    public function getApiUpdates(): Collection
    {
        return $this->apiUpdates;
    }

    public function addApiUpdate(ApiUpdate $update): self
    {
        if (!$this->apiUpdates->contains($update)) {
            $this->apiUpdates->add($update);
            $update->setAnime($this);
        }

        return $this;
    }

    public function removeApiUpdate(ApiUpdate $update): self
    {
        if ($this->apiUpdates->removeElement($update)) {
            if ($update->getAnime() === $this) {
                $update->setAnime(null);
            }
        }

        return $this;
    }
}
