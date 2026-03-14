import { CommonModule } from '@angular/common';
import { AfterViewInit, Component, OnDestroy, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ResultComponent } from 'src/app/components/result/result.component';
import { catchError, of } from 'rxjs';
import GLightbox from 'glightbox';
import {
  PlayerService,
  SeniorPlayerDetail,
  StatPlaceholder,
} from '../../../../services/player.service';

interface SelectionPeriodStat {
  label: string;
  value: string;
}

@Component({
  selector: 'app-senior-national-team-player-profile',
  imports: [CommonModule, RouterLink, ResultComponent],
  templateUrl: './senior-national-team-player-profile.component.html',
  styleUrl: './senior-national-team-player-profile.component.scss',
})
export class SeniorNationalTeamPlayerProfileComponent implements OnDestroy {
  private readonly route = inject(ActivatedRoute);
  private readonly playerService = inject(PlayerService);
  private lightbox: any;

  readonly isLoading = signal(false);
  readonly profile = signal<SeniorPlayerDetail | null>(null);
  private readonly slugParam = toSignal(this.route.paramMap, {
    initialValue: this.route.snapshot.paramMap,
  });

  readonly pageTitle = computed(() => this.profile()?.fullName ?? 'Fiche joueur');
  readonly selectionPeriodStats = computed<SelectionPeriodStat[]>(() => {
    const profile = this.profile();
    if (!profile) {
      return [];
    }

    const years = profile.appearances
      .map((appearance) => this.extractYear(appearance.date))
      .filter((year): year is number => year !== null);

    if (years.length === 0) {
      const fallbackYear = this.extractYear(profile.stats.lastCapDate);
      if (fallbackYear === null) {
        return [];
      }

      return [
        {
          label: 'Période en sélection',
          value: String(fallbackYear),
        },
      ];
    }

    const firstYear = Math.min(...years);
    const lastYear = Math.max(...years);

    if (firstYear === lastYear) {
      return [
        {
          label: 'Période en sélection',
          value: String(firstYear),
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

  constructor() {
    effect((onCleanup) => {
      const slug = this.slugParam().get('slug')?.trim() ?? '';
      if (!slug) {
        this.profile.set(null);
        this.isLoading.set(false);
        return;
      }

      this.isLoading.set(true);

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
}
