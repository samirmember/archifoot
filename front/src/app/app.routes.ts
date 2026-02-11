import { Routes } from '@angular/router';
import { LayoutComponent } from './layouts/layout.component';
import { HomeComponent } from './pages/home/home.component';
import { SeniorPage } from './pages/national-team/senior/senior.page';
import { SeniorNationalTeamMatchsComponent } from './pages/national-team/senior/matchs/senior-national-team-matchs.component';
import { SeniorNationalTeamHistoryComponent } from './pages/national-team/senior/history/senior-national-team-history.component';
import { SeniorNationalTeamCoachsComponent } from './pages/national-team/senior/coach/senior-national-team-coachs.component';
import { SeniorNationalTeamCoachDetailComponent } from './pages/national-team/senior/coach/senior-national-team-coach-detail.component';
import { SeniorNationalTeamPlayersComponent } from './pages/national-team/senior/players/senior-national-team-players.component';
import { SeniorNationalTeamPlayerDetailComponent } from './pages/national-team/senior/players/senior-national-team-player-detail.component';

export const routes: Routes = [
  {
    path: '',
    component: LayoutComponent,
    children: [
      { path: '', component: HomeComponent },
      {
        path: 'equipe-nationale/senior',
        component: SeniorPage,
        children: [
          { path: 'home', pathMatch: 'full', redirectTo: 'home' },
          { path: 'home', component: SeniorNationalTeamHistoryComponent },
          { path: 'matchs', component: SeniorNationalTeamMatchsComponent },
          { path: 'joueurs', component: SeniorNationalTeamPlayersComponent },
          { path: 'joueurs/:slug', component: SeniorNationalTeamPlayerDetailComponent },
          { path: 'entraineurs', component: SeniorNationalTeamCoachsComponent },
          { path: 'entraineurs/:slug', component: SeniorNationalTeamCoachDetailComponent },
        ],
      },
    ],
  },
  { path: '**', redirectTo: '' },
];
