<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FilesController extends AbstractController
{

    private Finder $finder;

    public function __construct()
    {
        $this->finder = new Finder();
    }

    #[Route('/api/documents ', name: 'app_docs')]
    /**
     * dictionary which contains the list of all the documents,
     * their length and their version.
     */
    public function index(): JsonResponse
    {
        $values = array_values(
            iterator_to_array(
                $this->readDir()
                    ->name('dictionary.json')
                    ->getIterator()
            )
        );

        if($values){
            return $this->json(
                json_decode(
                    array_values(
                        iterator_to_array(
                            $this->readDir()
                                ->name('dictionary.json')
                                ->getIterator()
                        )
                    )[0]->getContents()
                )
            );
        }

        return $this->json([]);

    }

    #[Route('/api/document ', name: 'app_doc_create', methods: ['POST'])]
    /**
     * dictionary which contains the list of all the documents,
     * their length and their version.
     */
    public function create(Request $request): JsonResponse
    {
        /** @var UploadedFile $uploadedFile */
        foreach ($request->files->getIterator() as $uploadedFile) {
            $size = $uploadedFile->getSize();
            $content = json_decode($uploadedFile->openFile()->fread($size), true);
            if ($content['name'] ?? false) {
                $values = $this->readDir()
                    ->contains($content['name'])
                    ->getIterator();
                $latestVersion = 0;

                /** @var SplFileInfo $file */
                foreach ($values as $file) {
                    preg_match('/\d/', $file->getFilename(), $matches);
                    if($matches[0] ?? false){
                        if((int) $matches[0] >= $latestVersion){
                            $latestVersion = ((int) $matches[0]) + 1;
                        }
                    }
                }
                $uploadedFile->move($this->getAbsolutePath(), $content['name'].'_'.$latestVersion.'.json');
                return $this->json('success');
            }

            return $this->json('');

        }
        return $this->json('', 422);

    }

    #[Route('/api/document/{id} ', name: 'app_doc_per_name')]
    /**
     * dictionary which contains the list of all the documents,
     * their length and their version.
     */
    public function read(string $id): JsonResponse
    {
        $values = array_values(iterator_to_array($this->readDir()
            ->name($id.'.json')
            ->getIterator()));
        if($values){
            return $this->json(json_decode($values[0]?->getContents()));
        }
        return $this->json([]);
    }

    /**
     * @return Finder
     */
    private function readDir(): Finder
    {
        $absolutePath = $this->getAbsolutePath();
        return $this->finder->files()->in($absolutePath);
    }

    private function getAbsolutePath(): string
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        return $projectDir . '/public/uploads';
    }
}