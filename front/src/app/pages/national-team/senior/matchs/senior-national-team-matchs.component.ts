import { Component, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { BehaviorSubject, catchError, of } from 'rxjs';
import { Country } from '../../../../models/country.model';
import { NumberService } from '../../../../../shared/number.service';
import { CountryInputComponent } from 'src/app/layouts/input/country-input.component';
import { CompetitionService } from 'src/app/services/competition.service';
import { MatchResult, ResultFilters, ResultService } from 'src/app/services/result.service';
import { ResultsListComponent } from 'src/app/components/results-list/results-list.component';
import { ResultsSkeletonComponent } from 'src/app/components/results-skeleton/results-skeleton.component';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [
    AutoCompleteModule,
    FormsModule,
    CountryInputComponent,
    ResultsListComponent,
    ResultsSkeletonComponent,
  ],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent {
  private static readonly PAGE_SIZE = 20;

  private numberService = inject(NumberService);
  private competitionService = inject(CompetitionService);
  private resultService = inject(ResultService);

  selectedCountry: Country | null = null;
  selectedYear: number | null = null;
  selectedCompetitionId: number | null = null;

  private readonly filters$ = new BehaviorSubject<ResultFilters>({});
  private readonly currentPage = signal(1);

  readonly results = signal<MatchResult[]>([]);
  readonly isLoading = signal(false);
  readonly hasMoreResults = signal(true);

  years = this.numberService.generateAllYears();
  competitions = toSignal(
    this.competitionService.getCompetitions().pipe(catchError(() => of([]))),
    {
      initialValue: [],
    },
  );
  private readonly currentFilters = toSignal(this.filters$, { initialValue: {} });

  constructor() {
    effect((onCleanup) => {
      const filters = this.currentFilters();
      const page = this.currentPage();

      this.isLoading.set(true);

      const subscription = this.resultService
        .getResults({
          ...filters,
          page,
          itemsPerPage: SeniorNationalTeamMatchsComponent.PAGE_SIZE,
        })
        .pipe(catchError(() => of([])))
        .subscribe((nextResults) => {
          this.results.update((currentResults) =>
            page === 1 ? nextResults : [...currentResults, ...nextResults],
          );
          this.hasMoreResults.set(
            nextResults.length === SeniorNationalTeamMatchsComponent.PAGE_SIZE,
          );
          this.isLoading.set(false);
        });

      onCleanup(() => subscription.unsubscribe());
    });
  }

  onCountryChange(country: Country | null): void {
    this.selectedCountry = country;
    console.log(country);
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

    this.currentPage.set(1);
    this.filters$.next(filters);
  }

  loadMore(): void {
    if (this.isLoading() || !this.hasMoreResults()) {
      return;
    }

    this.currentPage.update((page) => page + 1);
  }
}
