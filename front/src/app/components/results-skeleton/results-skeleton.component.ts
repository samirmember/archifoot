import { Component, computed, input } from '@angular/core';

@Component({
  selector: 'app-results-skeleton',
  templateUrl: './results-skeleton.component.html',
  styleUrl: './results-skeleton.component.scss',
})
export class ResultsSkeletonComponent {
  count = input<number>(3);

  readonly placeholders = computed(() => Array.from({ length: this.count() }));
}

