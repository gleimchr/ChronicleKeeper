<?php

declare(strict_types=1);

namespace ChronicleKeeper\Library\Presentation\Controller\Directory;

use ChronicleKeeper\Library\Application\Command\StoreDirectory;
use ChronicleKeeper\Library\Domain\Entity\Directory;
use ChronicleKeeper\Library\Domain\RootDirectory;
use ChronicleKeeper\Library\Presentation\Form\DirectoryType;
use ChronicleKeeper\Shared\Presentation\FlashMessages\Alert;
use ChronicleKeeper\Shared\Presentation\FlashMessages\HandleFlashMessages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

use function array_key_exists;
use function assert;
use function is_array;

#[Route(
    '/library/directory/{directory}/edit',
    name: 'library_directory_edit',
    requirements: ['directory' => Requirement::UUID],
)]
class DirectoryEdit extends AbstractController
{
    use HandleFlashMessages;

    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(Request $request, Directory $directory): Response
    {
        if ($directory->getId() === RootDirectory::ID) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(
            DirectoryType::class,
            ['title' => $directory->getTitle(), 'parent' => $directory->getParent()],
            ['exclude_directories' => [$directory]],
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $directoryArray = $form->getData();
            assert(is_array($directoryArray) && array_key_exists('title', $directoryArray));

            $directory->rename($directoryArray['title']);
            $directory->moveToDirectory($directoryArray['parent']);

            $this->bus->dispatch(new StoreDirectory($directory));

            $this->addFlashMessage(
                $request,
                Alert::SUCCESS,
                'Das Verzeichnis "' . $directory->getTitle() . '" wurde erfolgreich bearbeitet.',
            );

            return $this->redirectToRoute('library', ['directory' => $directory->getId()]);
        }

        return $this->render(
            'library/directory_edit.html.twig',
            ['directory' => $directory, 'form' => $form->createView()],
        );
    }
}
