import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CoachService, SeniorCoach } from '../../../../services/coach.service';

@Component({
  selector: 'app-senior-national-team-coachs',
  imports: [RouterLink],
  templateUrl: './senior-national-team-coachs.component.html',
  styleUrl: './senior-national-team-coachs.component.scss',
})
export class SeniorNationalTeamCoachsComponent {
  private readonly coachService = inject(CoachService);

  readonly coaches = signal<SeniorCoach[]>([]);
  readonly isLoading = signal(true);

  constructor() {
    this.coachService.getSeniorNationalTeamCoaches().subscribe((coaches) => {
      this.coaches.set(coaches);
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

  getCoachPath(slug: string): string[] {
    return ['/equipe-nationale/senior/entraineurs', slug];
  }

  getWinRate(coach: SeniorCoach): number {
    if (coach.highlights.matchCount === 0) {
      return 0;
    }

    return Math.round((coach.highlights.wins / coach.highlights.matchCount) * 100);
  }
}
