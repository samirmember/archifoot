import { Component } from '@angular/core';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent {
  countries = ['France', 'Germany', 'Italy', 'Spain', 'Portugal'];
  // years = Array.from({ length: 30 }, (_, i) => new Date().getFullYear() - i);

  years = this.populateYears();

  private populateYears(): number[] {
    const startYear = 1962;
    const currentYear = new Date().getFullYear();
    const years: number[] = [];

    for (let year = startYear; year <= currentYear; year++) {
      years.push(year);
    }

    return years;
  }
}
