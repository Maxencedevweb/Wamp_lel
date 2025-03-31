<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option;

enum AlertVariant: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';
    case Light = 'light';
    case Dark = 'dark';

    public function asBootstrapCssClass(): string
    {
        return 'alert-'.$this->value;
    }
}
