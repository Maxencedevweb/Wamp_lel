<?php

namespace App\Controller;

use App\Form\GlobalImportType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class GlobalImportController extends AbstractController
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    #[Route('/admin/global-import', name: 'admin_global_import')]
    public function importGlobal(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GlobalImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $importFile */
            $importFile = $form->get('importFile')->getData();

            try {
                $this->processImportFile($importFile, $entityManager);
                $this->addFlash('success', 'Import réussi avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'import : ' . $e->getMessage());
            }

            return $this->redirectToRoute('admin_global_import');
        }

        return $this->render('admin/global_import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function processImportFile(UploadedFile $file, EntityManagerInterface $entityManager): void
    {
        $zipFile = $file->getPathname();
        $tempDir = sys_get_temp_dir() . '/global_import_' . uniqid();
        mkdir($tempDir);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {
            $zip->extractTo($tempDir);
            $zip->close();

            $entities = [
                'Contenu.csv'         => 'importContenu',
                'ContenuFormat.csv'   => 'importContenuFormat',
                'Couleur.csv'         => 'importCouleur',
                'Etape.csv'           => 'importEtape',
                'Exercice.csv'        => 'importExercice',
                'Sequence.csv'        => 'importSequence',
            ];

            foreach ($entities as $filename => $method) {
                $filepath = $tempDir . '/' . $filename;
                if (file_exists($filepath)) {
                    $this->$method($filepath, $entityManager);
                }
            }

            $imagesFolder = $tempDir . '/images';
            if (is_dir($imagesFolder)) {
                $this->importImages($imagesFolder);
            }

            $audiosFolder = $tempDir . '/audios';
            if (is_dir($audiosFolder)) {
                $this->importAudios($audiosFolder);
            }

            $sequenceVideosFolder = $tempDir . '/sequencevideos';
            if (is_dir($sequenceVideosFolder)) {
                $this->importSequencevideos($sequenceVideosFolder);
            }

            $this->deleteDirectory($tempDir);
        } else {
            throw new \Exception('Impossible d\'ouvrir le fichier ZIP');
        }
    }

    private function importExercice(string $filepath, EntityManagerInterface $entityManager): void {
        $file = fopen($filepath, 'r');
        $headers = fgetcsv($file);

        while (($data = fgetcsv($file)) !== false) {
            $exercice = $entityManager->getRepository(\App\Entity\Exercice::class)->find($data[0]);

            if (!$exercice) {
                $exercice = new \App\Entity\Exercice();
            } else {
                foreach ($exercice->getContenus() as $contenu) {
                    $exercice->removeContenu($contenu);
                }
                $exercice->setSequence(null);
            }

            $exercice->setTypeExercice($data[1] ?? '');
            $exercice->setConsigne($data[4] ?? '');

            if (!empty($data[2])) {
                $sequence = $entityManager->getRepository(\App\Entity\Sequence::class)->find(trim($data[2]));
                if ($sequence) {
                    $exercice->setSequence($sequence);
                }
            }

            if (!empty($data[3])) {
                $contenuIds = explode(',', $data[3]);
                foreach ($contenuIds as $contenuId) {
                    $contenu = $entityManager->getRepository(\App\Entity\Contenu::class)->find(trim($contenuId));
                    if ($contenu) {
                        $exercice->addContenu($contenu);
                    }
                }
            }

            $entityManager->persist($exercice);
        }

        fclose($file);
        $entityManager->flush();
    }



    private function importCouleur(string $filepath, EntityManagerInterface $entityManager): void {
        $file = fopen($filepath, 'r');
        $headers = fgetcsv($file);

        while (($data = fgetcsv($file)) !== false) {
            $couleur = $entityManager->getRepository(\App\Entity\Couleur::class)->find($data[0]);

            if (!$couleur) {
                $couleur = new \App\Entity\Couleur();
            }

            $couleur->setCode($data[1] ?? '');

            // Gestion des relations avec ContenuFormat
            if (!empty($data[2])) {
                $contenuFormatLettres = explode(',', $data[2]);

                foreach ($contenuFormatLettres as $lettres) {
                    $contenuFormat = $entityManager->getRepository(\App\Entity\ContenuFormat::class)
                        ->findOneBy(['lettres' => trim($lettres)]);

                    if ($contenuFormat) {
                        $contenuFormat->setCouleur($couleur);
                        $entityManager->persist($contenuFormat);
                    }
                }
            }

            $entityManager->persist($couleur);
        }

        fclose($file);
        $entityManager->flush();
    }



    private function importEtape(string $filepath, EntityManagerInterface $entityManager): void {
        $file = fopen($filepath, 'r');
        $headers = fgetcsv($file);

        while (($data = fgetcsv($file)) !== false) {
            $etape = $entityManager->getRepository(\App\Entity\Etape::class)->find($data[0]);

            if (!$etape) {
                $etape = new \App\Entity\Etape();
            }

            $etape->setNom($data[1] ?? '');

            $entityManager->persist($etape);
        }

        fclose($file);
        $entityManager->flush();
    }



    private function importContenu(string $filepath, EntityManagerInterface $entityManager): void
    {
        $file = fopen($filepath, 'r');
        $headers = fgetcsv($file);

        while (($data = fgetcsv($file)) !== false) {
            $contenu = $entityManager->getRepository(\App\Entity\Contenu::class)->findOneBy(['id' => $data[0]]);

            if (!$contenu) {
                $contenu = new \App\Entity\Contenu();
            } else {
                foreach ($contenu->getExercices() as $exercice) {
                    $contenu->removeExercice($exercice);
                }
                $contenu->setSequence(null);
                foreach ($contenu->getContenuFormats() as $format) {
                    $contenu->removeContenuFormat($format);
                }
            }

            $contenu->setContenu($data[1] ?? null);
            $contenu->setImageUrl($data[2] ?? null);
            $contenu->setAudioUrl($data[3] ?? null);
            $contenu->setSyllabes($data[6] ?? null);

            if (!empty($data[4])) {
                $exerciceIds = explode(',', $data[4]);
                foreach ($exerciceIds as $exerciceId) {
                    $exercice = $entityManager->getRepository(\App\Entity\Exercice::class)->find(trim($exerciceId));
                    if ($exercice) {
                        $contenu->addExercice($exercice);
                    }
                }
            }

            if (!empty($data[5])) {
                $sequence = $entityManager->getRepository(\App\Entity\Sequence::class)->find(trim($data[5]));
                if ($sequence) {
                    $contenu->setSequence($sequence);
                }
            }

            if (!empty($data[7])) {
                $contenuFormatIds = explode(',', $data[7]);
                foreach ($contenuFormatIds as $contenuFormatId) {
                    $contenuFormat = $entityManager->getRepository(\App\Entity\ContenuFormat::class)->find(trim($contenuFormatId));
                    if ($contenuFormat) {
                        $contenu->addContenuFormat($contenuFormat);
                    }
                }
            }

            $entityManager->persist($contenu);
        }

        fclose($file);
        $entityManager->flush();
    }


    private function importContenuFormat(string $filepath, EntityManagerInterface $entityManager): void
    {
        $file = fopen($filepath, 'r');
        $headers = fgetcsv($file);

        while (($data = fgetcsv($file)) !== false) {
            $contenuFormat = $entityManager->getRepository(\App\Entity\ContenuFormat::class)->find($data[0]);

            if (!$contenuFormat) {
                $contenuFormat = new \App\Entity\ContenuFormat();
            } else {
                $contenuFormat->setCouleur(null);
                $contenuFormat->setContenu(null);
            }

            $contenuFormat->setLettres($data[1] ?? null);
            $contenuFormat->setBold($data[4] ?? null);

            if (!empty($data[2])) {
                $couleurIds = explode(',', $data[2]);
                foreach ($couleurIds as $couleurId) {
                    $couleur = $entityManager->getRepository(\App\Entity\Couleur::class)->find(trim($couleurId));
                    if ($couleur) {
                        $contenuFormat->setCouleur($couleur);
                    }
                }
            }

            if (!empty($data[3])) {
                $contenuIds = explode(',', $data[3]);
                foreach ($contenuIds as $contenuId) {
                    $contenu = $entityManager->getRepository(\App\Entity\Contenu::class)->find(trim($contenuId));
                    if ($contenu) {
                        $contenuFormat->setContenu($contenu);
                    }
                }
            }

            $entityManager->persist($contenuFormat);
        }

        fclose($file);
        $entityManager->flush();
    }



    private function importImages(string $imagesFolder): void
    {
        $destinationFolder = $this->getParameter('kernel.project_dir') . '/public/images';

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($imagesFolder),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $source = $file->getPathname();
                $destination = $destinationFolder . '/' . $file->getFilename();

                if ($this->filesystem->exists($destination)) {
                    $this->filesystem->remove($destination);
                }

                $this->filesystem->copy($source, $destination, true);
            }
        }
    }

    private function importAudios(string $audioFolder): void
    {
        $destinationFolder = $this->getParameter('kernel.project_dir') . '/public/audios';

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($audioFolder),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $source = $file->getPathname();
                $destination = $destinationFolder . '/' . $file->getFilename();

                if ($this->filesystem->exists($destination)) {
                    $this->filesystem->remove($destination);
                }

                $this->filesystem->copy($source, $destination, true);
            }
        }
    }

    private function importSequencevideos(string $sequencevideosFolder): void
    {
        $destinationFolder = $this->getParameter('kernel.project_dir') . '/public/sequencevideos';

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sequencevideosFolder),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $source = $file->getPathname();
                $destination = $destinationFolder . '/' . $file->getFilename();

                if ($this->filesystem->exists($destination)) {
                    $this->filesystem->remove($destination);
                }

                $this->filesystem->copy($source, $destination, true);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = "$dir/$file";
            (is_dir($filePath)) ? $this->deleteDirectory($filePath) : unlink($filePath);
        }
        rmdir($dir);
    }



}