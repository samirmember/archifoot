import { CommonModule, DatePipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { catchError, of, switchMap } from 'rxjs';
import { PlayerService, SeniorPlayerDetail } from '../../../../services/player.service';

@Component({
  selector: 'app-senior-national-team-player-detail',
  imports: [CommonModule, RouterLink, DatePipe],
  templateUrl: './senior-national-team-player-detail.component.html',
  styleUrl: './senior-national-team-player-detail.component.scss',
})
export class SeniorNationalTeamPlayerDetailComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly playerService = inject(PlayerService);

  readonly isLoading = signal(true);
  readonly player = signal<SeniorPlayerDetail | null>(null);

  readonly keyStats = computed(() => {
    const current = this.player();
    if (!current) {
      return [];
    }

    return [
      { label: 'Sélections', value: current.stats.caps },
      { label: 'Buts', value: current.stats.goals },
      { label: 'Titularisations', value: current.stats.starts },
      { label: 'Matchs capitaine', value: current.stats.captaincies },
      { label: 'Buts (événements)', value: current.stats.scoredGoalsFromMatchEvents },
      { label: 'Cartons jaunes', value: current.stats.yellowCards },
      { label: 'Cartons rouges', value: current.stats.redCards },
    ];
  });

  constructor() {
    this.route.paramMap
      .pipe(
        switchMap((params) => {
          const slug = params.get('slug') ?? '';
          this.isLoading.set(true);
          return this.playerService.getSeniorNationalTeamPlayerDetails(slug).pipe(catchError(() => of(null)));
        }),
      )
      .subscribe((player) => {
        this.player.set(player);
        this.isLoading.set(false);
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

  getBirthLocation(player: SeniorPlayerDetail): string {
    return [player.profile.birthCity, player.profile.birthRegion, player.profile.birthCountry].filter(Boolean).join(', ');
  }

  getMembershipLabel(membership: SeniorPlayerDetail['memberships'][number]): string {
    return membership.teamDisplayName ?? membership.clubName ?? membership.nationalTeamName ?? 'Équipe inconnue';
  }
}
