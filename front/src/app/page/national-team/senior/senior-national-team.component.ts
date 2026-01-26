import { Component } from '@angular/core';
import { RouterModule, RouterOutlet } from '@angular/router';
import { TitleComponent } from '../../../layout/title/title.component';
import { SubmenuComponent } from '../../../layout/submenu/submenu.component';

@Component({
  selector: 'app-senior-national-team',
  imports: [RouterOutlet, RouterModule, TitleComponent, SubmenuComponent],
  templateUrl: './senior-national-team.component.html',
  styleUrl: './senior-national-team.component.scss',
})
export class SeniorNationalTeamComponent {}
