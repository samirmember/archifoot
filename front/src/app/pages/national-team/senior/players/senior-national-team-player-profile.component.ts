import { CommonModule } from '@angular/common';
import { Component, OnDestroy, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatchFiltersComponent } from 'src/app/components/match-filters/match-filters.component';
import { MatchResultsSectionComponent } from 'src/app/components/match-results-section/match-results-section.component';
import { Country } from 'src/app/models/country.model';
import { catchError, of } from 'rxjs';
import GLightbox from 'glightbox';
import {
  SeniorPlayerAppearancesResponse,
  PlayerService,
  SeniorPlayerDetail,
  StatPlaceholder,
} from '../../../../services/player.service';
import { MatchResult } from 'src/app/services/result.service';
import { ApiFixtureStageCompetition } from 'src/app/models/api-fixture.model';

interface SelectionPeriodStat {
  label: string;
  value: string;
}

@Component({
  selector: 'app-senior-national-team-player-profile',
  imports: [
    CommonModule,
    RouterLink,
    MatchFiltersComponent,
    MatchResultsSectionComponent,
  ],
  templateUrl: './senior-national-team-player-profile.component.html',
  styleUrl: './senior-national-team-player-profile.component.scss',
})
export class SeniorNationalTeamPlayerProfileComponent implements OnDestroy {
  private static readonly PAGE_SIZE = 20;

  private readonly route = inject(ActivatedRoute);
  private readonly playerService = inject(PlayerService);
  private lightbox: any;

  readonly isLoading = signal(false);
  readonly areAppearancesLoading = signal(false);
  readonly profile = signal<SeniorPlayerDetail | null>(null);
  readonly appearances = signal<MatchResult[]>([]);
  readonly appearancesTotal = signal(0);
  readonly hasMoreResults = signal(false);
  readonly selectedCountry = signal<Country | null>(null);
  readonly selectedYear = signal<number | null>(null);
  readonly selectedCompetitionId = signal<number | null>(null);
  readonly currentPage = signal(1);
  private readonly slugParam = toSignal(this.route.paramMap, {
    initialValue: this.route.snapshot.paramMap,
  });

  readonly pageTitle = computed(() => this.profile()?.fullName ?? 'Fiche joueur');
  readonly totalAppearances = computed(() => this.profile()?.appearancesMeta.total ?? 0);
  readonly years = computed(() => this.profile()?.appearanceOptions.years ?? []);
  readonly competitions = computed<ApiFixtureStageCompetition[]>(
    () => this.profile()?.appearanceOptions.competitions ?? [],
  );
  readonly birthPlace = computed(() => this.buildBirthPlace(this.profile()?.profile));
  readonly shouldShowAppearanceFilters = computed(() => this.totalAppearances() > 10);
  readonly hasActiveAppearanceFilters = computed(
    () =>
      this.selectedCountry() !== null ||
      this.selectedYear() !== null ||
      this.selectedCompetitionId() !== null,
  );
  readonly selectionPeriodStats = computed<SelectionPeriodStat[]>(() => {
    const profile = this.profile();
    if (!profile) {
      return [];
    }

    const firstYear = this.extractYear(profile.stats.firstCapDate);
    const lastYear = this.extractYear(profile.stats.lastCapDate);

    if (firstYear === null && lastYear === null) {
      return [];
    }

    if (firstYear === null || lastYear === null || firstYear === lastYear) {
      return [
        {
          label: 'Période en sélection',
          value: String(lastYear ?? firstYear),
        },
      ];
    }

    return [
      {
        label: 'Premier match',
        value: String(firstYear),
      },
      {
        label: 'Dernier match',
        value: String(lastYear),
      },
    ];
  });
  readonly shouldShowLoadMoreAppearances = computed(() => this.hasMoreResults());

  constructor() {
    effect((onCleanup) => {
      const slug = this.slugParam().get('slug')?.trim() ?? '';
      if (!slug) {
        this.profile.set(null);
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

      const subscription = this.playerService
        .getSeniorNationalTeamPlayerProfile(slug)
        .pipe(catchError(() => of(null)))
        .subscribe((response) => {
          this.profile.set(response);
          this.isLoading.set(false);
          this.initLightbox();
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
        itemsPerPage: SeniorNationalTeamPlayerProfileComponent.PAGE_SIZE,
        seasonName: this.selectedYear() !== null ? String(this.selectedYear()) : undefined,
        teamIso3: this.selectedCountry()?.iso3 ?? undefined,
        competitionId: this.selectedCompetitionId() ?? undefined,
      };

      this.areAppearancesLoading.set(true);

      const subscription = this.playerService
        .getSeniorNationalTeamPlayerAppearances(slug, filters)
        .pipe(catchError(() => of(this.emptyAppearancesResponse(page))))
        .subscribe((response) => {
          this.appearances.update((currentAppearances) =>
            page === 1 ? response.items : [...currentAppearances, ...response.items],
          );
          this.appearancesTotal.set(response.meta.total);
          this.hasMoreResults.set(
            response.meta.page < response.meta.totalPages &&
              response.items.length === SeniorNationalTeamPlayerProfileComponent.PAGE_SIZE,
          );
          this.areAppearancesLoading.set(false);
        });

      onCleanup(() => subscription.unsubscribe());
    });
  }

  getPlayerInitials(fullName: string): string {
    return fullName
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }

  asBadgeLabel(value: string | null | undefined, fallback = 'Non renseigné'): string {
    return value?.trim() || fallback;
  }

  getPlaceholderState(placeholder: StatPlaceholder): 'missing' | 'ready' {
    return placeholder.value ? 'ready' : 'missing';
  }

  hasGalleryPhotos(): boolean {
    return (this.profile()?.galleryPhotos?.length ?? 0) > 0;
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

  private extractYear(value: string | null | undefined): number | null {
    if (!value) {
      return null;
    }

    const match = value.match(/\b(\d{4})\b/);
    if (!match) {
      return null;
    }

    const year = Number(match[1]);
    return Number.isInteger(year) ? year : null;
  }

  private buildBirthPlace(profile: SeniorPlayerDetail['profile'] | null | undefined): string | null {
    if (!profile) {
      return null;
    }

    const localParts = this.uniqueLocationParts([profile.birthCity, profile.birthRegion]);
    if (localParts.length === 0) {
      return null;
    }

    return localParts.join(', ');
  }

  private uniqueLocationParts(values: Array<string | null | undefined>): string[] {
    const seen = new Set<string>();
    const uniqueValues: string[] = [];

    for (const value of values) {
      const trimmedValue = value?.trim();
      if (!trimmedValue) {
        continue;
      }

      const normalizedValue = trimmedValue.toLocaleLowerCase();
      if (seen.has(normalizedValue)) {
        continue;
      }

      seen.add(normalizedValue);
      uniqueValues.push(trimmedValue);
    }

    return uniqueValues;
  }

  private initLightbox(): void {
    setTimeout(() => {
      this.lightbox = GLightbox({
        selector: '[data-glightbox]',
      });
    }, 100);
  }

  ngOnDestroy(): void {
    this.lightbox?.destroy();
  }

  private emptyAppearancesResponse(page: number): SeniorPlayerAppearancesResponse {
    return {
      items: [],
      meta: {
        page,
        itemsPerPage: SeniorNationalTeamPlayerProfileComponent.PAGE_SIZE,
        total: 0,
        totalPages: 1,
      },
    };
  }
}
