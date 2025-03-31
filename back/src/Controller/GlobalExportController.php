<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use ZipArchive;

class GlobalExportController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('global_export/index.html.twig', [
            'controller_name' => 'GlobalExportController',
        ]);
    }
    #[Route('/admin/global-export', name: 'admin_global_export', methods: ['GET'])]

    public function globalExport(EntityManagerInterface $em): StreamedResponse
    {
        $entities = [
            'Contenu'         => 'App\Entity\Contenu',
            'ContenuFormat'   => 'App\Entity\ContenuFormat',
            'Couleur'         => 'App\Entity\Couleur',
            'Etape'           => 'App\Entity\Etape',
            'Exercice'        => 'App\Entity\Exercice',
            'Sequence'        => 'App\Entity\Sequence',
        ];

        $tempDir = sys_get_temp_dir() . '/global_export_' . uniqid();
        mkdir($tempDir);

        foreach ($entities as $name => $class) {
            if ($name === 'Contenu') {
                $csvData = $this->generateCsvDataForContenu($em);
            } elseif ($name === 'ContenuFormat') {
                $csvData = $this->generateCsvDataForContenuFormat($em);
            } elseif ($name === 'Couleur'){
                $csvData = $this->generateCsvDataForCouleur($em);
            } elseif ($name === 'Etape'){
                $csvData = $this->generateCsvDataForEtape($em);
            } elseif ($name === 'Exercice'){
                $csvData = $this->generateCsvDataForExercice($em);
            } elseif ($name === 'Sequence'){
                $csvData = $this->generateCsvDataForSequence($em);
            }
            // Vous pouvez ajouter d'autres conditions pour les autres entités
            file_put_contents($tempDir . '/' . $name . '.csv', $csvData);
        }

        $imagesFolder = $this->getParameter('kernel.project_dir') . '/public/images';
        $audiosFolder = $this->getParameter('kernel.project_dir') . '/public/audios';
        $sequenceVideosFolder = $this->getParameter('kernel.project_dir') . '/public/sequencevideos';

        $zipFile = tempnam(sys_get_temp_dir(), 'global_export_') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            throw new \Exception('Impossible de créer le fichier ZIP');
        }

        foreach ($entities as $name => $class) {
            $csvPath = $tempDir . '/' . $name . '.csv';
            $zip->addFile($csvPath, $name . '.csv');
        }

        if (is_dir($imagesFolder)) {
            $this->addFolderToZip($imagesFolder, 'images', $zip);
        }

        if (is_dir($audiosFolder)) {
            $this->addFolderToZip($audiosFolder, 'audios', $zip);
        }

        if (is_dir($sequenceVideosFolder)) {
            $this->addFolderToZip($sequenceVideosFolder, 'sequencevideos', $zip);
        }



        $zip->close();

        $this->deleteDirectory($tempDir);

        $response = new StreamedResponse(function () use ($zipFile) {
            readfile($zipFile);
            unlink($zipFile); // nettoyage après envoi
        });
        $disposition = $response->headers->makeDisposition('attachment', 'global_export.zip');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/zip');

        return $response;
    }

    private function generateCsvDataForContenu(EntityManagerInterface $em): string
    {
        $records = $em->getRepository('App\Entity\Contenu')->findAll();
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['id', 'contenu', 'image_url', 'audio_url', 'exercices', 'sequence', 'syllabes', 'contenuFormats']);

        foreach ($records as $record) {
            // Pour la collection ManyToMany Exercices, on va récupérer par exemple les ids
            $exercices = $record->getExercices()->map(function($exercice) {
                return $exercice->getId(); // ou une autre propriété comme getNom()
            })->toArray();
            $exercicesString = implode(', ', $exercices);

            // Pour la relation ManyToOne Sequence, on récupère par exemple l'id
            $sequence = $record->getSequence();
            $sequenceString = $sequence ? $sequence->getId() : '';

            // Pour la collection OneToMany ContenuFormats, on peut par exemple récupérer les lettres
            $contenuFormats = $record->getContenuFormats()->map(function($contenuFormat) {
                return $contenuFormat->getLettres();
            })->toArray();
            $contenuFormatsString = implode(', ', $contenuFormats);

            fputcsv($output,[
                    $record->getId(),
                    $record->getContenu(),
                    $record->getImageUrl(),
                    $record->getAudioUrl(),
                    $exercicesString,
                    $sequenceString,
                    $record->getSyllabes(),
                    $contenuFormatsString,
                ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    public function generateCsvDataForContenuFormat(EntityManagerInterface $em): string{
        $records = $em->getRepository('App\Entity\ContenuFormat')->findAll();
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['id', 'lettres', 'couleur', 'contenu', 'bold']);

        foreach ($records as $record) {

            $couleur = $record->getCouleur();
            $couleurString = $couleur ? $couleur->getId() : '';

            $contenu = $record->getContenu();
            $contenuString = $contenu ? $contenu->getId() : '';

            fputcsv($output, [
                $record->getId(),
                $record->getLettres(),
                $couleurString,
                $contenuString,
                $record->isBold(),
            ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    public function generateCsvDataForCouleur(EntityManagerInterface $em): string{
        $records = $em->getRepository('App\Entity\Couleur')->findAll();
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['id', 'code', 'contenuFormats']);
        foreach ($records as $record) {

            $contenuFormats = $record->getContenuFormats()->map(function($contenuFormat) {
                return $contenuFormat->getLettres();
            })->toArray();
            $contenuFormatsString = implode(', ', $contenuFormats);

            fputcsv($output, [
                $record->getId(),
                $record->getCode(),
                $contenuFormatsString,
            ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    public function generateCsvDataForEtape(EntityManagerInterface $em): string{
        $records = $em->getRepository('App\Entity\Etape')->findAll();
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['id', 'nom']);
        foreach ($records as $record) {
            fputcsv($output, [
                $record->getId(),
                $record->getNom(),
            ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;

    }

    public function generateCsvDataForExercice(EntityManagerInterface $em): string{
        $records = $em->getRepository('App\Entity\Exercice')->findAll();
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['id', 'type_exercice', 'sequence', 'contenus', 'consigne']);
        foreach ($records as $record) {
            $sequence = $record->getSequence();
            $sequenceString = $sequence ? $sequence->getId() : '';

            $contenus = $record->getContenus()->map(function($contenu) {
                return $contenu->getId(); // ou une autre propriété comme getNom()
            })->toArray();
            $contenusString = implode(', ', $contenus);

            fputcsv($output, [
                $record->getId(),
                $record->getTypeExercice(),
                $sequenceString,
                $contenusString,
                $record->getConsigne(),

            ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    public function generateCsvDataForSequence(EntityManagerInterface $em): string{
        $records = $em->getRepository('App\Entity\Sequence')->findAll();
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['id', 'nom', 'etape', 'exercices', 'contenus', 'video_url']);
        foreach ($records as $record) {
            $etape = $record->getEtape();
            $etapeString = $etape ? $etape->getId() : '';

            $exercices = $record->getExercices()->map(function($exercice) {
                return $exercice->getId();
            })->toArray();
            $exercicesString = implode(', ', $exercices);

            $contenus = $record->getContenus()->map(function($contenu) {
                return $contenu->getId();
            })->toArray();
            $contenuString = implode(', ', $contenus);

            fputcsv($output, [
                $record->getId(),
                $record->getNom(),
                $etapeString,
                $exercicesString,
                $contenuString,
                $record->getVideoUrl(),
            ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    private function addFolderToZip(string $folder, string $zipFolder, ZipArchive $zip): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipFolder . '/' . substr($filePath, strlen($folder) + 1);
                $zip->addFile($filePath, $relativePath);
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
