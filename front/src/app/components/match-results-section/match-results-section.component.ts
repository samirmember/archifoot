import { CommonModule } from '@angular/common';
import { Component, input, output } from '@angular/core';
import { ResultComponent } from 'src/app/components/result/result.component';
import { ResultsSkeletonComponent } from 'src/app/components/results-skeleton/results-skeleton.component';
import { MatchResult } from 'src/app/services/result.service';

@Component({
  selector: 'app-match-results-section',
  imports: [CommonModule, ResultComponent, ResultsSkeletonComponent],
  templateUrl: './match-results-section.component.html',
  styleUrl: './match-results-section.component.scss',
})
export class MatchResultsSectionComponent {
  title = input<string | null>(null);
  results = input<MatchResult[]>([]);
  isLoading = input(false);
  hasActiveFilters = input(false);
  emptyMessage = input('Aucune rencontre trouvée.');
  emptyFilteredMessage = input('Aucune rencontre ne correspond aux filtres sélectionnés.');
  showLoadMore = input(false);
  loadMoreLabel = input('Charger des rencontres supplémentaires');

  readonly loadMore = output<void>();

  onLoadMore(): void {
    this.loadMore.emit();
  }
}
