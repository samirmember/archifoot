import { CommonModule } from '@angular/common';
import { Component, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { catchError, of } from 'rxjs';
import {
  PlayerProfile,
  PlayerService,
  StatPlaceholder,
} from '../../../../services/player.service';

@Component({
  selector: 'app-senior-national-team-player-profile',
  imports: [CommonModule, RouterLink],
  templateUrl: './senior-national-team-player-profile.component.html',
  styleUrl: './senior-national-team-player-profile.component.scss',
})
export class SeniorNationalTeamPlayerProfileComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly playerService = inject(PlayerService);

  readonly isLoading = signal(false);
  readonly profile = signal<PlayerProfile | null>(null);
  private readonly slugParam = toSignal(this.route.paramMap, { initialValue: this.route.snapshot.paramMap });

  readonly pageTitle = computed(() => this.profile()?.fullName ?? 'Fiche joueur');

  readonly highlightStats = computed(() => {
    const playerProfile = this.profile();
    if (!playerProfile) {
      return [];
    }

    return [
      { label: 'Sélections', value: playerProfile.stats.caps, accent: 'primary' },
      { label: 'Buts', value: playerProfile.stats.goals, accent: 'goal' },
      { label: 'Titularisations', value: playerProfile.stats.starts, accent: 'neutral' },
      { label: 'Entrées en jeu', value: playerProfile.stats.subIn, accent: 'neutral' },
      { label: 'Capitanat', value: playerProfile.stats.captainMatches, accent: 'neutral' },
      { label: 'Dernière sélection', value: playerProfile.stats.lastCapDate ?? '—', accent: 'neutral' },
    ];
  });

  readonly disciplineStats = computed(() => {
    const playerProfile = this.profile();
    if (!playerProfile) {
      return [];
    }

    return [
      { label: 'Cartons jaunes', value: playerProfile.stats.yellowCards, accent: 'yellow' },
      { label: 'Cartons rouges', value: playerProfile.stats.redCards, accent: 'red' },
    ];
  });

  readonly identityFacts = computed(() => {
    const playerProfile = this.profile();
    if (!playerProfile) {
      return [];
    }

    return [
      { label: 'Poste', value: this.asBadgeLabel(playerProfile.position) },
      { label: 'Nationalité', value: this.asBadgeLabel(playerProfile.nationality) },
      { label: 'Date de naissance', value: this.asBadgeLabel(playerProfile.birthDateLabel, 'Date inconnue') },
      { label: 'Lieu de naissance', value: this.asBadgeLabel(playerProfile.birthPlace, 'Lieu inconnu') },
      { label: 'Club actuel', value: this.asBadgeLabel(playerProfile.currentClub, 'Club inconnu') },
      { label: 'Numéro', value: this.asBadgeLabel(playerProfile.shirtNumber, 'N/A') },
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
    return placeholder.dynamic ? 'ready' : 'missing';
  }
}
