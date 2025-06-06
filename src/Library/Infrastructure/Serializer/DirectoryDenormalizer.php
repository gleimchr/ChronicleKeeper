<?php

declare(strict_types=1);

namespace ChronicleKeeper\Library\Infrastructure\Serializer;

use ChronicleKeeper\Library\Application\Query\FindDirectoryById;
use ChronicleKeeper\Library\Domain\Entity\Directory;
use ChronicleKeeper\Library\Domain\RootDirectory;
use ChronicleKeeper\Shared\Application\Query\QueryService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Webmozart\Assert\Assert;

use function array_diff;
use function array_keys;
use function is_string;

#[Autoconfigure(lazy: true)]
class DirectoryDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    /** @var array<string, Directory> */
    private array $cachedEntries = [];

    private DenormalizerInterface $denormalizer;

    public function __construct(private readonly QueryService $queryService)
    {
    }

    public function setDenormalizer(DenormalizerInterface $denormalizer): void
    {
        $this->denormalizer = $denormalizer;
    }

    /** @inheritDoc */
    public function denormalize(mixed $data, string $type, string|null $format = null, array $context = []): Directory
    {
        if (is_string($data)) {
            if ($data === RootDirectory::ID) {
                return RootDirectory::get();
            }

            return $this->queryService->query(new FindDirectoryById($data));
        }

        Assert::isArray($data);
        Assert::true(array_diff(['id', 'title', 'parent'], array_keys($data)) === []);
        Assert::uuid($data['id']);

        if (isset($this->cachedEntries[$data['id']])) {
            return $this->cachedEntries[$data['id']];
        }

        $parent = $data['parent'] ?? RootDirectory::get();
        if (! $parent instanceof Directory) {
            $parent = $this->denormalizer->denormalize($parent, Directory::class, $format, $context);
        }

        $directory = new Directory($data['id'], $data['title'], $parent);

        $this->cachedEntries[$directory->getId()] = $directory;

        return $directory;
    }

    /** @inheritDoc */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        string|null $format = null,
        array $context = [],
    ): bool {
        return $type === Directory::class;
    }

    /** @inheritDoc */
    public function getSupportedTypes(string|null $format): array
    {
        return [Directory::class => true];
    }
}
