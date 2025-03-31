<?php

namespace App\Controller\Admin;

use App\Entity\Sequence;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SequenceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sequence::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('etape')
            ->setLabel('Étape')
            ->setFormTypeOptions([
                'choice_label' => 'nom',
            ]);

        yield TextField::new('nom')->setLabel('Nom');
        yield TextField::new('video_url')->setLabel('URL de la Vidéo');
    }



}
