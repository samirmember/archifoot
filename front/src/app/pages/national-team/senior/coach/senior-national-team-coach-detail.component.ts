import { CommonModule, DatePipe } from '@angular/common';
import { Component, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { catchError, of } from 'rxjs';
import { MatchFiltersComponent } from 'src/app/components/match-filters/match-filters.component';
import { MatchResultsSectionComponent } from 'src/app/components/match-results-section/match-results-section.component';
import { Country } from 'src/app/models/country.model';
import { ApiFixtureStageCompetition } from 'src/app/models/api-fixture.model';
import {
  CoachService,
  SeniorCoach,
  SeniorCoachAppearancesResponse,
} from '../../../../services/coach.service';

@Component({
  selector: 'app-senior-national-team-coach-detail',
  imports: [CommonModule, RouterLink, DatePipe, MatchFiltersComponent, MatchResultsSectionComponent],
  templateUrl: './senior-national-team-coach-detail.component.html',
  styleUrl: './senior-national-team-coach-detail.component.scss',
})
export class SeniorNationalTeamCoachDetailComponent {
  private static readonly PAGE_SIZE = 20;

  private readonly route = inject(ActivatedRoute);
  private readonly coachService = inject(CoachService);
  private readonly slugParam = toSignal(this.route.paramMap, {
    initialValue: this.route.snapshot.paramMap,
  });

  readonly isLoading = signal(true);
  readonly areAppearancesLoading = signal(false);
  readonly coach = signal<SeniorCoach | null>(null);
  readonly appearances = signal<SeniorCoachAppearancesResponse['items']>([]);
  readonly appearancesTotal = signal(0);
  readonly hasMoreResults = signal(false);
  readonly selectedCountry = signal<Country | null>(null);
  readonly selectedYear = signal<number | null>(null);
  readonly selectedCompetitionId = signal<number | null>(null);
  readonly currentPage = signal(1);

  readonly keyStats = computed(() => {
    const current = this.coach();
    if (!current) {
      return [];
    }

    return [
      { label: 'Matchs', value: current.highlights.matchCount },
      { label: 'Victoires', value: current.highlights.wins },
      { label: 'Nuls', value: current.highlights.draws },
      { label: 'Défaites', value: current.highlights.losses },
      { label: 'Buts marqués', value: current.highlights.goalsFor },
      { label: 'Buts encaissés', value: current.highlights.goalsAgainst },
      { label: 'Clean sheets', value: current.highlights.cleanSheets, tooltip: 'Matchs sans encaisser de but' },
      { label: 'Titres', value: current.highlights.trophies },
    ];
  });
  readonly totalAppearances = computed(() => this.coach()?.appearancesMeta.total ?? 0);
  readonly years = computed(() => this.coach()?.appearanceOptions.years ?? []);
  readonly competitions = computed<ApiFixtureStageCompetition[]>(
    () => this.coach()?.appearanceOptions.competitions ?? [],
  );
  readonly shouldShowAppearanceFilters = computed(() => this.totalAppearances() > 10);
  readonly hasActiveAppearanceFilters = computed(
    () =>
      this.selectedCountry() !== null ||
      this.selectedYear() !== null ||
      this.selectedCompetitionId() !== null,
  );

  constructor() {
    effect((onCleanup) => {
      const slug = this.slugParam().get('slug')?.trim() ?? '';
      if (!slug) {
        this.coach.set(null);
        this.appearances.set([]);
        this.appearancesTotal.set(0);
        this.hasMoreResults.set(false);
        this.isLoading.set(false);
        return;
      }

      this.isLoading.set(true);
      this.selectedCountry.set(null);
      this.selectedYear.set(null);
      this.selectedCompetitionId.set(null);
      this.currentPage.set(1);
      this.appearances.set([]);
      this.appearancesTotal.set(0);
      this.hasMoreResults.set(false);

      const subscription = this.coachService
        .getSeniorNationalTeamCoachBySlug(slug)
        .pipe(catchError(() => of(null)))
        .subscribe((coach) => {
          this.coach.set(coach);
          this.isLoading.set(false);
        });

      onCleanup(() => subscription.unsubscribe());
    });

    effect((onCleanup) => {
      const slug = this.slugParam().get('slug')?.trim() ?? '';
      if (!slug) {
        this.appearances.set([]);
        this.appearancesTotal.set(0);
        this.hasMoreResults.set(false);
        this.areAppearancesLoading.set(false);
        return;
      }

      const page = this.currentPage();
      const filters = {
        page,
        itemsPerPage: SeniorNationalTeamCoachDetailComponent.PAGE_SIZE,
        seasonName: this.selectedYear() !== null ? String(this.selectedYear()) : undefined,
        teamIso3: this.selectedCountry()?.iso3 ?? undefined,
        competitionId: this.selectedCompetitionId() ?? undefined,
      };

      this.areAppearancesLoading.set(true);

      const subscription = this.coachService
        .getSeniorNationalTeamCoachAppearances(slug, filters)
        .pipe(catchError(() => of(this.emptyAppearancesResponse(page))))
        .subscribe((response) => {
          this.appearances.update((currentAppearances) =>
            page === 1 ? response.items : [...currentAppearances, ...response.items],
          );
          this.appearancesTotal.set(response.meta.total);
          this.hasMoreResults.set(
            response.meta.page < response.meta.totalPages &&
              response.items.length === SeniorNationalTeamCoachDetailComponent.PAGE_SIZE,
          );
          this.areAppearancesLoading.set(false);
        });

      onCleanup(() => subscription.unsubscribe());
    });
  }

  onCountryChange(country: Country | null): void {
    this.selectedCountry.set(country);
    this.currentPage.set(1);
  }

  onYearChange(year: number | null): void {
    this.selectedYear.set(year);
    this.currentPage.set(1);
  }

  onCompetitionChange(competitionId: number | null): void {
    this.selectedCompetitionId.set(competitionId);
    this.currentPage.set(1);
  }

  loadMore(): void {
    if (this.areAppearancesLoading() || !this.hasMoreResults()) {
      return;
    }

    this.currentPage.update((page) => page + 1);
  }

  private emptyAppearancesResponse(page: number): SeniorCoachAppearancesResponse {
    return {
      items: [],
      meta: {
        page,
        itemsPerPage: SeniorNationalTeamCoachDetailComponent.PAGE_SIZE,
        total: 0,
        totalPages: 1,
      },
    };
  }

  getCoachInitials(fullName: string): string {
    return fullName
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }

  getWinRate(coach: SeniorCoach): number {
    if (coach.highlights.matchCount === 0) {
      return 0;
    }

    return Math.round((coach.highlights.wins / coach.highlights.matchCount) * 100);
  }
}
