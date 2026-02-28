<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
// use App\Entity\Category;
// use App\Entity\City;
// use App\Entity\Club;
use App\Entity\Coach;
// use App\Entity\Competition;
// use App\Entity\Country;
// use App\Entity\Division;
// use App\Entity\Edition;
// use App\Entity\Fixture;
// use App\Entity\FixtureParticipant;
// use App\Entity\ImportBatch;
// use App\Entity\MatchCard;
// use App\Entity\MatchGoal;
// use App\Entity\Matchday;
// use App\Entity\NameAlias;
// use App\Entity\NationalTeam;
// use App\Entity\Person;
// use App\Entity\PersonClubHistory;
// use App\Entity\Player;
// use App\Entity\PlayerNationalStats;
// use App\Entity\Position;
// use App\Entity\Referee;
// use App\Entity\Region;
// use App\Entity\Scoresheet;
// use App\Entity\ScoresheetLineup;
// use App\Entity\ScoresheetOfficial;
// use App\Entity\ScoresheetSubstitution;
// use App\Entity\Season;
// use App\Entity\SourceFile;
// use App\Entity\Stadium;
// use App\Entity\Stage;
// use App\Entity\Standing;
// use App\Entity\Team;
// use App\Entity\Trophy;
// use App\Entity\TrophyAward;
// use App\Entity\TrophyAwardPerson;
// use App\Entity\User;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class AdminDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Archifoot Admin');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('admin/layout.css')
            ->addJsFile('admin/location-toggle.js')
            ->addJsFile('admin/min3-select-filter.js');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Données');
        yield MenuItem::linkToCrud('Catégories', 'fas fa-tags', Category::class)->setController(CategoryCrudController::class);
        yield MenuItem::linkToCrud('Villes', 'fas fa-city', City::class)->setController(CityCrudController::class);
    //     yield MenuItem::linkToCrud('Clubs', 'fas fa-shield-alt', Club::class);
        yield MenuItem::linkToCrud('Compétitions', 'fas fa-trophy', Competition::class)->setController(CompetitionCrudController::class);
        yield MenuItem::linkToCrud('Pays', 'fas fa-flag', Country::class)->setController(CountryCrudController::class);
    //     yield MenuItem::linkToCrud('Divisions', 'fas fa-sitemap', Division::class)->setController(DivisionCrudController::class);
        // yield MenuItem::linkToCrud('Éditions', 'fas fa-calendar-alt', Edition::class)->setController(EditionCrudController::class);
        yield MenuItem::linkToCrud('Matchs', 'fas fa-futbol', Fixture::class)->setController(FixtureCrudController::class);
    //     yield MenuItem::linkToCrud('Participants de match', 'fas fa-users', FixtureParticipant::class)->setController(FixtureParticipantCrudController::class);
    //     yield MenuItem::linkToCrud('Imports', 'fas fa-file-import', ImportBatch::class)->setController(ImportBatchCrudController::class);
    //     yield MenuItem::linkToCrud('Cartons', 'fas fa-clipboard', MatchCard::class)->setController(MatchCardCrudController::class);
        yield MenuItem::linkToCrud('Buts', 'fas fa-bullseye', MatchGoal::class)->setController(MatchGoalCrudController::class);
    //     yield MenuItem::linkToCrud('Journées', 'fas fa-calendar-day', Matchday::class)->setController(MatchdayCrudController::class);
    //     yield MenuItem::linkToCrud('Alias', 'fas fa-user-tag', NameAlias::class)->setController(NameAliasCrudController::class);
    //     yield MenuItem::linkToCrud('Sélections', 'fas fa-flag-checkered', NationalTeam::class)->setController(NationalTeamCrudController::class);
    //     yield MenuItem::linkToCrud('Personnes', 'fas fa-user', Person::class)->setController(PersonCrudController::class);
    //     yield MenuItem::linkToCrud('Historique clubs', 'fas fa-history', PersonClubHistory::class)->setController(PersonClubHistoryCrudController::class);
        yield MenuItem::linkToCrud('Joueurs', 'fas fa-running', Player::class)->setController(PlayerCrudController::class);
        yield MenuItem::linkToCrud('Coachs', 'fas fa-chalkboard-teacher', Coach::class);
    //     yield MenuItem::linkToCrud('Stats sélections', 'fas fa-chart-line', PlayerNationalStats::class)->setController(PlayerNationalStatsCrudController::class);
        yield MenuItem::linkToCrud('Postes', 'fas fa-map-pin', Position::class)->setController(PositionCrudController::class);
        // yield MenuItem::linkToCrud('Arbitres', 'fas fa-whistle', Referee::class)->setController(RefereeCrudController::class);
    //     yield MenuItem::linkToCrud('Régions', 'fas fa-map', Region::class)->setController(RegionCrudController::class);
    //     yield MenuItem::linkToCrud('Feuilles de match', 'fas fa-file-alt', Scoresheet::class)->setController(ScoresheetCrudController::class);
    //     yield MenuItem::linkToCrud('Compositions', 'fas fa-list-ol', ScoresheetLineup::class)->setController(ScoresheetLineupCrudController::class);
        yield MenuItem::linkToCrud('Officiels', 'fas fa-user-tie', ScoresheetOfficial::class)->setController(ScoresheetOfficialCrudController::class);
    //     yield MenuItem::linkToCrud('Remplacements', 'fas fa-exchange-alt', ScoresheetSubstitution::class)->setController(ScoresheetSubstitutionCrudController::class);
    //     yield MenuItem::linkToCrud('Saisons', 'fas fa-calendar', Season::class)->setController(SeasonCrudController::class);
    //     yield MenuItem::linkToCrud('Fichiers sources', 'fas fa-file', SourceFile::class)->setController(SourceFileCrudController::class);
        yield MenuItem::linkToCrud('Stades', 'fas fa-building', Stadium::class)->setController(StadiumCrudController::class);
    //     yield MenuItem::linkToCrud('Phases', 'fas fa-layer-group', Stage::class)->setController(StageCrudController::class);
    //     yield MenuItem::linkToCrud('Classements', 'fas fa-table', Standing::class)->setController(StandingCrudController::class);
    //     yield MenuItem::linkToCrud('Équipes', 'fas fa-users', Team::class)->setController(TeamCrudController::class);
    //     yield MenuItem::linkToCrud('Trophées', 'fas fa-award', Trophy::class)->setController(TrophyCrudController::class);
    //     yield MenuItem::linkToCrud('Attributions trophées', 'fas fa-medal', TrophyAward::class)->setController(TrophyAwardCrudController::class);
    //     yield MenuItem::linkToCrud('Attributions personnes', 'fas fa-id-badge', TrophyAwardPerson::class)->setController(TrophyAwardPersonCrudController::class);
        yield MenuItem::section('Sytème');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user-shield', User::class)->setController(UserCrudController::class);
    }
}
