<?php

declare(strict_types=1);

namespace ChronicleKeeper\Test\ImageGenerator\Domain\Entity;

use ChronicleKeeper\Image\Domain\Entity\Image;
use ChronicleKeeper\ImageGenerator\Domain\Entity\GeneratorResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(GeneratorResult::class)]
#[Small]
class GeneratorResultTest extends TestCase
{
    #[Test]
    public function objectIsCreatable(): void
    {
        $generatorResult = new GeneratorResult('foo');

        self::assertTrue(Uuid::isValid($generatorResult->id));
        self::assertSame('foo', $generatorResult->encodedImage);
        self::assertNull($generatorResult->image);
        self::assertSame('image/png', $generatorResult->mimeType);
    }

    #[Test]
    public function imageDataUrlGenerationIsCorrect(): void
    {
        $generatorResult = new GeneratorResult('foo');

        self::assertSame('data:image/png;base64,foo', $generatorResult->getImageUrl());
    }

    #[Test]
    public function jsonSerializationIsCorrect(): void
    {
        $generatorResult = new GeneratorResult('foo', 'A fancy image!');

        self::assertSame([
            'id' => $generatorResult->id,
            'encodedImage' => 'foo',
            'revisedPrompt' => 'A fancy image!',
            'mimeType' => 'image/png',
            'image' => null,
        ], $generatorResult->jsonSerialize());
    }

    #[Test]
    public function objectIsCreatableWithImage(): void
    {
        $image           = self::createStub(Image::class);
        $generatorResult = new GeneratorResult(encodedImage: 'foo', image: $image);

        self::assertTrue(Uuid::isValid($generatorResult->id));
        self::assertSame('foo', $generatorResult->encodedImage);
        self::assertSame($image, $generatorResult->image);
        self::assertSame('image/png', $generatorResult->mimeType);
    }
}
