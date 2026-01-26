import { Routes } from '@angular/router';
import { LayoutComponent } from './layout/layout.component';
import { HomeComponent } from './page/home/home.component';
import { SeniorNationalTeamComponent } from './page/national-team/senior/senior-national-team.component';
import { SeniorNationalTeamMatchsComponent } from './page/national-team/senior/matchs/senior-national-team-matchs.component';
import { SeniorNationalTeamHistoryComponent } from './page/national-team/senior/history/senior-national-team-history.component';
import { SeniorNationalTeamCoachsComponent } from './page/national-team/senior/coachs/senior-national-team-coachs.component';
import { SeniorNationalTeamPlayersComponent } from './page/national-team/senior/players/senior-national-team-players.component';

export const routes: Routes = [
  {
    path: '',
    component: LayoutComponent,
    children: [
      { path: '', component: HomeComponent },
      {
        path: 'equipe-nationale/senior',
        component: SeniorNationalTeamComponent,
        children: [
          { path: 'home', pathMatch: 'full', redirectTo: 'home' },
          { path: 'home', component: SeniorNationalTeamHistoryComponent },
          { path: 'matchs', component: SeniorNationalTeamMatchsComponent },
          { path: 'joueurs', component: SeniorNationalTeamPlayersComponent },
          { path: 'entraineurs', component: SeniorNationalTeamCoachsComponent },
        ],
      },
    ],
  },
  { path: '**', redirectTo: '' },
];
