import { CommonModule, DatePipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { switchMap } from 'rxjs';
import { CoachService, SeniorCoach } from '../../../../services/coach.service';

@Component({
  selector: 'app-senior-national-team-coach-detail',
  imports: [CommonModule, RouterLink, DatePipe],
  templateUrl: './senior-national-team-coach-detail.component.html',
  styleUrl: './senior-national-team-coach-detail.component.scss',
})
export class SeniorNationalTeamCoachDetailComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly coachService = inject(CoachService);

  readonly isLoading = signal(true);
  readonly coach = signal<SeniorCoach | null>(null);

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

  constructor() {
    this.route.paramMap
      .pipe(
        switchMap((params) => {
          const slug = params.get('slug') ?? '';
          this.isLoading.set(true);
          return this.coachService.getSeniorNationalTeamCoachBySlug(slug);
        }),
      )
      .subscribe((coach) => {
        this.coach.set(coach);
        this.isLoading.set(false);
      });
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
