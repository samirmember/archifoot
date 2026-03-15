import { Component, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { catchError, of } from 'rxjs';
import { Country } from '../../../../models/country.model';
import { NumberService } from '../../../../../shared/number.service';
import { MatchFiltersComponent } from 'src/app/components/match-filters/match-filters.component';
import { MatchResultsSectionComponent } from 'src/app/components/match-results-section/match-results-section.component';
import { CompetitionService } from 'src/app/services/competition.service';
import {
  MatchResult,
  MatchResultsSummary,
  ResultFilters,
  ResultService,
  SeniorMatchesPageResponse,
} from 'src/app/services/result.service';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [
    MatchFiltersComponent,
    MatchResultsSectionComponent,
  ],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent {
  private static readonly PAGE_SIZE = 20;

  private readonly numberService = inject(NumberService);
  private readonly competitionService = inject(CompetitionService);
  private readonly resultService = inject(ResultService);

  selectedCountry: Country | null = null;
  selectedYear: number | null = null;
  selectedCompetitionId: number | null = null;

  private readonly query = signal<{ filters: ResultFilters; page: number }>({
    filters: {},
    page: 1,
  });

  readonly summaryPanelId = 'match-summary-panel';
  readonly results = signal<MatchResult[]>([]);
  readonly isLoading = signal(false);
  readonly hasMoreResults = signal(true);
  readonly summary = signal<MatchResultsSummary>(this.emptySummary());
  readonly isSummaryLoading = signal(false);
  readonly isSummaryExpanded = signal(false);
  readonly hasUserToggledSummary = signal(false);

  years = this.numberService.generateAllYears();
  competitions = toSignal(
    this.competitionService.getCompetitions().pipe(catchError(() => of([]))),
    {
      initialValue: [],
    },
  );

  constructor() {
    effect((onCleanup) => {
      const { filters, page } = this.query();
      const includeSummary = page === 1;

      this.isLoading.set(true);
      this.isSummaryLoading.set(includeSummary);

      const subscription = this.resultService
        .getSeniorMatchesPage(
          {
          ...filters,
          page,
          itemsPerPage: SeniorNationalTeamMatchsComponent.PAGE_SIZE,
          },
          includeSummary,
        )
        .pipe(catchError(() => of(this.emptyPageResponse())))
        .subscribe((response) => {
          this.results.update((currentResults) =>
            page === 1 ? response.items : [...currentResults, ...response.items],
          );
          this.hasMoreResults.set(response.meta.page < response.meta.totalPages);

          if (page === 1) {
            this.summary.set(response.summary ?? this.emptySummary());
          }

          this.isLoading.set(false);
          this.isSummaryLoading.set(false);
        });

      onCleanup(() => subscription.unsubscribe());
    });
  }

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

  getActiveFilterLabels(): string[] {
    const labels: string[] = [];

    const competitionName = this.getSelectedCompetitionName();
    if (competitionName) {
      labels.push(competitionName);
    }

    if (this.selectedYear !== null) {
      labels.push(String(this.selectedYear));
    }

    if (this.selectedCountry?.name?.trim()) {
      labels.push(this.selectedCountry.name.trim());
    }

    return labels;
  }

  getSearchContextLabel(): string {
    const labels = this.getActiveFilterLabels();
    return labels.length > 0 ? labels.join(' • ') : 'Toutes les rencontres';
  }

  getSignedValue(value: number): string {
    return value > 0 ? `+${value}` : `${value}`;
  }

  getOfficialContextNote(): string {
    if (this.summary().totalMatches === 0) {
      return 'Aucune rencontre ne correspond aux filtres actuels.';
    }

    return "Part des matchs officiels dans l'echantillon actuellement affiché.";
  }

  toggleSummaryAccordion(): void {
    this.hasUserToggledSummary.set(true);
    this.isSummaryExpanded.update((isExpanded) => !isExpanded);
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

    this.query.set({ filters, page: 1 });
  }

  loadMore(): void {
    if (this.isLoading() || !this.hasMoreResults()) {
      return;
    }

    this.query.update((current) => ({ ...current, page: current.page + 1 }));
  }

  private getSelectedCompetitionName(): string | null {
    return (
      this.competitions()
        .find((competition) => competition.id === this.selectedCompetitionId)
        ?.name?.trim() ?? null
    );
  }

  private emptySummary(): MatchResultsSummary {
    return {
      totalMatches: 0,
      wins: 0,
      draws: 0,
      losses: 0,
      winRate: 0,
      goalsFor: 0,
      goalsAgainst: 0,
      goalDifference: 0,
      cleanSheets: 0,
      uniqueOpponents: 0,
      uniqueHostCountries: 0,
      officialMatches: 0,
      officialRate: 0,
    };
  }

  private emptyPageResponse(): SeniorMatchesPageResponse {
    return {
      items: [],
      meta: {
        page: this.query().page,
        itemsPerPage: SeniorNationalTeamMatchsComponent.PAGE_SIZE,
        total: 0,
        totalPages: 1,
      },
      summary: this.query().page === 1 ? this.emptySummary() : null,
    };
  }
}

