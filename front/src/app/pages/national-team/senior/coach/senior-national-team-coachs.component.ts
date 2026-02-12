import { Component, effect, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { catchError, of } from 'rxjs';
import {
  CoachService,
  SeniorCoachListItem,
  SeniorCoachesResponse,
} from '../../../../services/coach.service';
import { PersonsSkeletonComponent } from '../../../../components/persons-skeleton/persons-skeleton.component';

@Component({
  selector: 'app-senior-national-team-coachs',
  imports: [FormsModule, PersonsSkeletonComponent],
  templateUrl: './senior-national-team-coachs.component.html',
  styleUrl: './senior-national-team-coachs.component.scss',
})
export class SeniorNationalTeamCoachsComponent {
  private readonly coachService = inject(CoachService);

  readonly isLoading = signal(false);
  readonly coaches = signal<SeniorCoachListItem[]>([]);
  readonly total = signal(0);
  readonly totalPages = signal(1);

  readonly page = signal(1);
  readonly perPage = signal<12 | 24>(12);
  readonly searchTerm = signal('');

  searchModel = '';

  constructor() {
    effect((onCleanup) => {
      const currentPage = this.page();
      const pageSize = this.perPage();
      const query = this.searchTerm();
      const emptyResponse: SeniorCoachesResponse = {
        items: [],
        meta: { page: 1, perPage: pageSize, total: 0, totalPages: 1 },
      };

      this.isLoading.set(true);

      const subscription = this.coachService
        .getSeniorNationalTeamCoaches(currentPage, pageSize, query)
        .pipe(catchError(() => of(emptyResponse)))
        .subscribe((response: SeniorCoachesResponse) => {
          this.coaches.set(response.items);
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
    const nextPerPage: 12 | 24 = value === '24' ? 24 : 12;
    this.perPage.set(nextPerPage);
    this.page.set(1);
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.totalPages() || page === this.page()) {
      return;
    }

    this.page.set(page);
  }

  getCoachInitials(fullName: string): string {
    return fullName
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }
}
