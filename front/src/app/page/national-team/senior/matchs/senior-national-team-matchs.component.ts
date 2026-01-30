import { Component, OnInit, inject } from '@angular/core';
import { finalize } from 'rxjs';
import { Country } from '../../../../models/country.model';
import { CountriesService } from '../../../../services/countries.service';
import { NumberService } from '../../../../../shared/number.service';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent implements OnInit {
  private numberService = inject(NumberService);
  private countriesService = inject(CountriesService);
  countries: Country[] = [];
  isLoadingCountries = false;
  years = this.numberService.generateAllYears();

  ngOnInit(): void {
    this.isLoadingCountries = true;
    this.countriesService
      .getCountries()
      .pipe(finalize(() => (this.isLoadingCountries = false)))
      .subscribe({
        next: (countries) => {
          this.countries = countries;
        },
        error: () => {
          this.countries = [];
        },
      });
  }
}
