import { Component, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { BehaviorSubject, catchError, of, switchMap } from 'rxjs';
import { Country } from '../../../../models/country.model';
import { NumberService } from '../../../../../shared/number.service';
import { CountryInputComponent } from 'src/app/layouts/input/country-input.component';
import { CompetitionService } from 'src/app/services/competition.service';
import { ResultFilters, ResultService } from 'src/app/services/result.service';
import { ResultsListComponent } from 'src/app/components/results-list/results-list.component';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [AutoCompleteModule, FormsModule, CountryInputComponent, ResultsListComponent],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent {
  private numberService = inject(NumberService);
  private competitionService = inject(CompetitionService);
  private resultService = inject(ResultService);

  selectedCountry: Country | null = null;
  selectedYear: number | null = null;
  selectedCompetitionId: number | null = null;

  private readonly filters$ = new BehaviorSubject<ResultFilters>({});

  years = this.numberService.generateAllYears();
  competitions = toSignal(
    this.competitionService.getCompetitions().pipe(catchError(() => of([]))),
    {
      initialValue: [],
    },
  );
  results = toSignal(
    this.filters$.pipe(
      switchMap((filters) => this.resultService.getResults(filters).pipe(catchError(() => of([])))),
    ),
    {
      initialValue: [],
    },
  );

  onCountryChange(country: Country | null): void {
    this.selectedCountry = country;
    this.refreshResults();
  }

  onYearChange(year: number | null): void {
    this.selectedYear = year;
    this.refreshResults();
  }

  onCompetitionChange(competitionId: number | null): void {
    this.selectedCompetitionId = competitionId;
    this.refreshResults();
  }

  private refreshResults(): void {
    const filters: ResultFilters = {};

    if (this.selectedCountry?.iso3) {
      filters.teamIso3 = this.selectedCountry.iso3;
    }

    if (this.selectedYear) {
      filters.seasonName = String(this.selectedYear);
    }

    if (this.selectedCompetitionId) {
      filters.competitionId = this.selectedCompetitionId;
    }

    this.filters$.next(filters);
  }
}
