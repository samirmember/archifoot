import { Routes } from '@angular/router';

export const routes: Routes = [
  { 
    path: '',
    loadComponent: () => import('./page/home/home.component.js').then(m => m.HomeComponent)
},
{
    path: 'equipes/algerie/seniors',
    loadComponent: () => import('./page/en-senior/en-senior.component.js').then(m => m.EnSeniorComponent),
  },
  { path: '**', redirectTo: '' },
];
