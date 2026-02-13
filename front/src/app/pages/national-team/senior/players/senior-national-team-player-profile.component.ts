import { CommonModule } from '@angular/common';
import { Component, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ResultComponent } from 'src/app/components/result/result.component';
import { catchError, of } from 'rxjs';
import {
  PlayerService,
  SeniorPlayerDetail,
  StatPlaceholder,
} from '../../../../services/player.service';

@Component({
  selector: 'app-senior-national-team-player-profile',
  imports: [CommonModule, RouterLink, ResultComponent],
  templateUrl: './senior-national-team-player-profile.component.html',
  styleUrl: './senior-national-team-player-profile.component.scss',
})
export class SeniorNationalTeamPlayerProfileComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly playerService = inject(PlayerService);

  readonly isLoading = signal(false);
  readonly profile = signal<SeniorPlayerDetail | null>(null);
  private readonly slugParam = toSignal(this.route.paramMap, {
    initialValue: this.route.snapshot.paramMap,
  });

  readonly pageTitle = computed(() => this.profile()?.fullName ?? 'Fiche joueur');

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
    return placeholder.value ? 'ready' : 'missing';
  }

}
