<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Category;
use App\Entity\City;
use App\Entity\Club;
use App\Entity\Coach;
use App\Entity\Competition;
use App\Entity\Country;
use App\Entity\Division;
use App\Entity\Edition;
use App\Entity\Fixture;
use App\Entity\FixtureParticipant;
use App\Entity\ImportBatch;
use App\Entity\MatchCard;
use App\Entity\MatchGoal;
use App\Entity\Matchday;
use App\Entity\NameAlias;
use App\Entity\NationalTeam;
use App\Entity\Person;
use App\Entity\PersonClubHistory;
use App\Entity\Player;
use App\Entity\PlayerNationalStats;
use App\Entity\Position;
use App\Entity\Referee;
use App\Entity\Region;
use App\Entity\Scoresheet;
use App\Entity\ScoresheetLineup;
use App\Entity\ScoresheetOfficial;
use App\Entity\ScoresheetSubstitution;
use App\Entity\Season;
use App\Entity\SourceFile;
use App\Entity\Stadium;
use App\Entity\Stage;
use App\Entity\Standing;
use App\Entity\Team;
use App\Entity\Trophy;
use App\Entity\TrophyAward;
use App\Entity\TrophyAwardPerson;
use App\Entity\User;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class AdminDashboardController extends AbstractDashboardController
{
    #[Route('', name: 'admin')]
    public function index(): Response
    {
        return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        // return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Archifoot Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Données');
        yield MenuItem::linkToCrud('Catégories', 'fas fa-tags', Category::class);
        yield MenuItem::linkToCrud('Villes', 'fas fa-city', City::class);
        yield MenuItem::linkToCrud('Clubs', 'fas fa-shield-alt', Club::class);
        yield MenuItem::linkToCrud('Coachs', 'fas fa-chalkboard-teacher', Coach::class);
        yield MenuItem::linkToCrud('Compétitions', 'fas fa-trophy', Competition::class);
        yield MenuItem::linkToCrud('Pays', 'fas fa-flag', Country::class);
        yield MenuItem::linkToCrud('Divisions', 'fas fa-sitemap', Division::class);
        yield MenuItem::linkToCrud('Éditions', 'fas fa-calendar-alt', Edition::class);
        yield MenuItem::linkToCrud('Matchs', 'fas fa-futbol', Fixture::class);
        yield MenuItem::linkToCrud('Participants de match', 'fas fa-users', FixtureParticipant::class);
        yield MenuItem::linkToCrud('Imports', 'fas fa-file-import', ImportBatch::class);
        yield MenuItem::linkToCrud('Cartons', 'fas fa-clipboard', MatchCard::class);
        yield MenuItem::linkToCrud('Buts', 'fas fa-bullseye', MatchGoal::class);
        yield MenuItem::linkToCrud('Journées', 'fas fa-calendar-day', Matchday::class);
        yield MenuItem::linkToCrud('Alias', 'fas fa-user-tag', NameAlias::class);
        yield MenuItem::linkToCrud('Sélections', 'fas fa-flag-checkered', NationalTeam::class);
        yield MenuItem::linkToCrud('Personnes', 'fas fa-user', Person::class);
        yield MenuItem::linkToCrud('Historique clubs', 'fas fa-history', PersonClubHistory::class);
        yield MenuItem::linkToCrud('Joueurs', 'fas fa-running', Player::class);
        yield MenuItem::linkToCrud('Stats sélections', 'fas fa-chart-line', PlayerNationalStats::class);
        yield MenuItem::linkToCrud('Postes', 'fas fa-map-pin', Position::class);
        yield MenuItem::linkToCrud('Arbitres', 'fas fa-whistle', Referee::class);
        yield MenuItem::linkToCrud('Régions', 'fas fa-map', Region::class);
        yield MenuItem::linkToCrud('Feuilles de match', 'fas fa-file-alt', Scoresheet::class);
        yield MenuItem::linkToCrud('Compositions', 'fas fa-list-ol', ScoresheetLineup::class);
        yield MenuItem::linkToCrud('Officiels', 'fas fa-user-tie', ScoresheetOfficial::class);
        yield MenuItem::linkToCrud('Remplacements', 'fas fa-exchange-alt', ScoresheetSubstitution::class);
        yield MenuItem::linkToCrud('Saisons', 'fas fa-calendar', Season::class);
        yield MenuItem::linkToCrud('Fichiers sources', 'fas fa-file', SourceFile::class);
        yield MenuItem::linkToCrud('Stades', 'fas fa-building', Stadium::class);
        yield MenuItem::linkToCrud('Phases', 'fas fa-layer-group', Stage::class);
        yield MenuItem::linkToCrud('Classements', 'fas fa-table', Standing::class);
        yield MenuItem::linkToCrud('Équipes', 'fas fa-users', Team::class);
        yield MenuItem::linkToCrud('Trophées', 'fas fa-award', Trophy::class);
        yield MenuItem::linkToCrud('Attributions trophées', 'fas fa-medal', TrophyAward::class);
        yield MenuItem::linkToCrud('Attributions personnes', 'fas fa-id-badge', TrophyAwardPerson::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user-shield', User::class);
    }
}
