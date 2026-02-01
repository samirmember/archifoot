import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AutoCompleteCompleteEvent, AutoCompleteModule } from 'primeng/autocomplete';
import { finalize } from 'rxjs';
import { Country } from '../../../../models/country.model';
import { CountriesService } from '../../../../services/countries.service';
import { NumberService } from '../../../../../shared/number.service';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [AutoCompleteModule, FormsModule],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent implements OnInit {
  private numberService = inject(NumberService);
  private countriesService = inject(CountriesService);
  countries: Country[] = [];
  filteredCountries: Country[] = [];
  selectedCountry: Country | null = null;
  isLoadingCountries = false;
  years = this.numberService.generateAllYears();

  ngOnInit(): void {
    // this.isLoadingCountries = true;
    // this.countriesService
    //   .getCountries()
    //   .pipe(finalize(() => (this.isLoadingCountries = false)))
    //   .subscribe({
    //     next: (countries) => {
    //       this.countries = countries;
    //       this.filteredCountries = countries;
    //     },
    //     error: () => {
    //       this.countries = [];
    //       this.filteredCountries = [];
    //     },
    //   });
  }

  filterCountries(event: AutoCompleteCompleteEvent): void {
    const query = event.query?.toLowerCase().trim();
    console.log(query);

    if (!query || query.length < 2) {
      // this.filteredCountries = [...this.countries];
      return;
    }

    this.isLoadingCountries = true;
    this.countriesService
      .getCountries(query)
      .pipe(finalize(() => (this.isLoadingCountries = false)))
      .subscribe({
        next: (countries) => {
          this.countries = countries;
          this.filteredCountries = countries;
        },
        error: () => {
          this.countries = [];
          this.filteredCountries = [];
        },
      });

    // this.filteredCountries = this.countries.filter((country) => {
    //   const name = country.name?.toLowerCase() ?? '';
    //   const iso2 = country.iso2?.toLowerCase() ?? '';
    //   const iso3 = country.iso3?.toLowerCase() ?? '';
    //   const fifa = country.fifaCode?.toLowerCase() ?? '';
    //   return (
    //     name.includes(query) || iso2.includes(query) || iso3.includes(query) || fifa.includes(query)
    //   );
    // });
  }

  getCountryFlag(country: Country): string | null {
    if (!country.iso2) {
      return null;
    }

    return `https://flagcdn.com/24x18/${country.iso2.toLowerCase()}.png`;
  }
}
