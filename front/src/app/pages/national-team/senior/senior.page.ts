import { Component } from '@angular/core';
import { RouterModule, RouterOutlet } from '@angular/router';
import { TitleComponent } from '../../../layouts/title/title.component';
import { SubmenuComponent } from '../../../layouts/submenu/submenu.component';

@Component({
  selector: 'app-senior-national-team',
  imports: [RouterOutlet, RouterModule, TitleComponent, SubmenuComponent],
  templateUrl: './senior.page.html',
  styleUrl: './senior.page.scss',
})
export class SeniorPage {}
