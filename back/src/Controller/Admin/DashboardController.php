<?php

namespace App\Controller\Admin;

use App\Controller\Admin\ContenuCrudController;
use App\Controller\Admin\SequenceCrudController;
use App\Entity\Contenu;
use App\Controller\GlobalExportController; // Ajoutez cette importation
use App\Controller\GlobalImportController;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private GlobalExportController $globalExportController,
        private GlobalImportController $globalImportController
    )
    {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(ContenuCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Lettres en lumière')
            ->setFaviconPath('/images/logo/brainLogo.png');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-gauge');

        yield MenuItem::linkToCrud('Etape', 'fa fa-stairs', Contenu::class)
            ->setController(EtapeCrudController::class);

        yield MenuItem::linkToCrud('Contenus', 'fa fa-folder-open', Contenu::class)
            ->setController(ContenuCrudController::class);

        yield MenuItem::linkToCrud('Séquence', 'fa fa-list-ol', Contenu::class)
            ->setController(SequenceCrudController::class);

        yield MenuItem::linkToCrud('Exercice', 'fa fa-dumbbell', Contenu::class)
            ->setController(ExerciceCrudController::class);

        yield MenuItem::linkToCrud('Couleur', 'fa fa-palette', Contenu::class)
            ->setController(CouleurCrudController::class);

        // Ajoutez un nouvel élément de menu pour l'exportation globale
        yield MenuItem::linkToRoute('Export Global', 'fa fa-download', 'admin_global_export');

        // Dans la méthode configureMenuItems()
        yield MenuItem::linkToRoute('Import Global', 'fa fa-upload', 'admin_global_import');

        yield MenuItem::linkToUrl('Retour au site', 'fa fa-arrow-left', '/');
    }
}