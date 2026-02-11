import { Component, effect, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { catchError, of } from 'rxjs';
import {
  PlayerService,
  SeniorPlayer,
  SeniorPlayersResponse,
} from '../../../../services/player.service';

@Component({
  selector: 'app-senior-national-team-players',
  imports: [FormsModule, RouterLink],
  templateUrl: './senior-national-team-players.component.html',
  styleUrl: './senior-national-team-players.component.scss',
})
export class SeniorNationalTeamPlayersComponent {
  private readonly playerService = inject(PlayerService);

  readonly isLoading = signal(false);
  readonly players = signal<SeniorPlayer[]>([]);
  readonly total = signal(0);
  readonly totalPages = signal(1);

  readonly page = signal(1);
  readonly perPage = signal<10 | 20>(10);
  readonly searchTerm = signal('');

  searchModel = '';

  constructor() {
    effect((onCleanup) => {
      const currentPage = this.page();
      const pageSize = this.perPage();
      const query = this.searchTerm();
      const emptyResponse: SeniorPlayersResponse = {
        items: [],
        meta: { page: 1, perPage: pageSize, total: 0, totalPages: 1 },
      };

      this.isLoading.set(true);

      const subscription = this.playerService
        .getSeniorNationalTeamPlayers(currentPage, pageSize, query)
        .pipe(
          catchError(() => of(emptyResponse)),
        )
        .subscribe((response: SeniorPlayersResponse) => {
          this.players.set(this.randomizePlayers(response.items));
          this.total.set(response.meta.total);
          this.totalPages.set(response.meta.totalPages);
          this.isLoading.set(false);
        });

      onCleanup(() => subscription.unsubscribe());
    });
  }

  onSearch(): void {
    this.page.set(1);
    this.searchTerm.set(this.searchModel.trim());
  }

  onPerPageChange(value: string): void {
    const nextPerPage: 10 | 20 = value === '20' ? 20 : 10;
    this.perPage.set(nextPerPage);
    this.page.set(1);
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.totalPages() || page === this.page()) {
      return;
    }

    this.page.set(page);
  }

  getPlayerInitials(fullName: string): string {
    return fullName
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }


  getPlayerSlug(fullName: string): string {
    return this.playerService.toSlug(fullName);
  }
  private randomizePlayers(players: SeniorPlayer[]): SeniorPlayer[] {
    const shuffled = [...players];
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }

    return shuffled;
  }
}
