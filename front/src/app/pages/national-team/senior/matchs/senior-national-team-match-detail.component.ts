import { DatePipe } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatchScoresheetDetailsResponse } from 'src/app/models/match-scoresheet.model';
import { MatchScoresheetService } from 'src/app/services/match-scoresheet.service';

@Component({
  selector: 'app-senior-national-team-match-detail',
  imports: [DatePipe, RouterLink],
  templateUrl: './senior-national-team-match-detail.component.html',
  styleUrl: './senior-national-team-match-detail.component.scss',
})
export class SeniorNationalTeamMatchDetailComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly service = inject(MatchScoresheetService);

  readonly isLoading = signal(true);
  readonly error = signal<string | null>(null);
  readonly details = signal<MatchScoresheetDetailsResponse | null>(null);

  readonly starters = computed(
    () => this.details()?.lineups.filter((item) => item.lineupRole === 'starter') ?? [],
  );
  readonly substitutes = computed(
    () => this.details()?.lineups.filter((item) => item.lineupRole === 'substitute') ?? [],
  );

  ngOnInit(): void {
    const externalMatchNoParam = this.route.snapshot.paramMap.get('externalMatchNo');
    const externalMatchNo: number = externalMatchNoParam ? Number(externalMatchNoParam) : NaN;

    if (Number.isNaN(externalMatchNo) || externalMatchNo <= 0) {
      this.error.set('Identifiant de match invalide.');
      this.isLoading.set(false);
      return;
    }

    this.service.getMatchScoresheetDetails(externalMatchNo).subscribe({
      next: (response) => {
        this.details.set(response);
        this.isLoading.set(false);
      },
      error: () => {
        this.error.set('Impossible de charger la fiche technique.');
        this.isLoading.set(false);
      },
    });
  }
}
