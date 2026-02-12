import { Component, computed, input } from '@angular/core';

@Component({
  selector: 'app-persons-skeleton',
  templateUrl: './persons-skeleton.component.html',
  styleUrl: './persons-skeleton.component.scss',
})
export class PersonsSkeletonComponent {
  count = input<number>(12);

  readonly placeholders = computed(() => Array.from({ length: this.count() }));
}
