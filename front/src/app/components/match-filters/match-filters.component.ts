import { CommonModule } from '@angular/common';
import { Component, input, output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ApiFixtureStageCompetition } from 'src/app/models/api-fixture.model';
import { Country } from 'src/app/models/country.model';
import { CountryInputComponent } from 'src/app/layouts/input/country-input.component';

@Component({
  selector: 'app-match-filters',
  imports: [CommonModule, FormsModule, CountryInputComponent],
  templateUrl: './match-filters.component.html',
  styleUrl: './match-filters.component.scss',
})
export class MatchFiltersComponent {
  countryInputId = input.required<string>();
  countryInputName = input.required<string>();
  yearSelectId = input.required<string>();
  yearSelectName = input.required<string>();
  competitionSelectId = input.required<string>();
  competitionSelectName = input.required<string>();
  selectedCountry = input<Country | null>(null);
  selectedYear = input<number | null>(null);
  selectedCompetitionId = input<number | null>(null);
  years = input<number[]>([]);
  competitions = input<ApiFixtureStageCompetition[]>([]);
  variant = input<'soft' | 'card'>('soft');

  readonly countryChange = output<Country | null>();
  readonly yearChange = output<number | null>();
  readonly competitionChange = output<number | null>();

  onCountryChange(country: Country | null): void {
    this.countryChange.emit(country);
  }

  onYearChange(year: number | null): void {
    this.yearChange.emit(year);
  }

  onCompetitionChange(competitionId: number | null): void {
    this.competitionChange.emit(competitionId);
  }
}
