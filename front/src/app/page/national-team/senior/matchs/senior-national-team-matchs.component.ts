import { Component, inject } from '@angular/core';
import { NumberService } from '../../../../../shared/number.service';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent {
  countries = ['France', 'Germany', 'Italy', 'Spain', 'Portugal'];
  private numberService = inject(NumberService);
  years = this.numberService.generateAllYears();
}
