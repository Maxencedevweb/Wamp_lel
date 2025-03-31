EasyAdmin
=========

`EasyAdmin`_ creates beautiful administration backends for your Symfony
applications. It's free, fast and fully documented.

If you already used previous EasyAdmin versions, beware that EasyAdmin 3 uses a
brand new architecture and it's incompatible with previous versions. However,
there's a command to :doc:`upgrade from EasyAdmin 2 to EasyAdmin 3 automatically </upgrade>`.

.. admonition:: Screencast
    :class: screencast

    Like video tutorials? Check out the `EasyAdmin Screencast on SymfonyCasts`_.

Table of Contents
-----------------

.. toctree::
    :maxdepth: 1

    dashboards
    crud
    design
    fields
    filters
    actions
    security
    events
    tests
    upgrade

Technical Requirements
----------------------

EasyAdmin requires the following:

* PHP 8.0.2 or higher;
* Symfony 5.4 or higher;
* Doctrine ORM entities (Doctrine ODM is not supported).

Installation
------------

Run the following command to install EasyAdmin in your application:

.. code-block:: terminal

    $ composer require easycorp/easyadmin-bundle

If you use `Symfony Flex`_ in your application, you are ready to :doc:`create your first Dashboard </dashboards>`.
Otherwise, keep reading because you need to perform some manual configuration.

Manual Configuration for Applications Not Using Symfony Flex
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In most Symfony applications **you don't need to make any of the following changes**.
These steps are only required for applications that have opted not to use Symfony Flex.

First, register two bundles in your application. Edit the ``config/bundles.php``
file and add the following::

    return [
        // ...
        EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle::class => ['all' => true],
        Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    ];

The ``TwigComponentBundle`` is also required because EasyAdmin uses `Twig Components`_
to build its interface. Next, create a configuration file for Twig Components
by adding the following file::

    # config/packages/twig_component.yaml
    twig_component:
        anonymous_template_directory: 'components/'
        defaults:
            # Namespace & directory for components
            App\Twig\Components\: 'components/'

The initial contents of this file should match those defined in the latest
`configuration recipe of Symfony UX Twig Component`_.

That's all! You are now ready to use EasyAdmin in your application. Start by
:doc:`creating your first Dashboard </dashboards>`.

.. _`EasyAdmin`: https://github.com/EasyCorp/EasyAdminBundle
.. _`EasyAdmin Screencast on SymfonyCasts`: https://symfonycasts.com/screencast/easyadminbundle
.. _`Symfony Flex`: https://symfony.com/doc/current/setup/flex.html
.. _`Twig Components`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`configuration recipe of Symfony UX Twig Component`: https://github.com/symfony/recipes/tree/main/symfony/ux-twig-component/
