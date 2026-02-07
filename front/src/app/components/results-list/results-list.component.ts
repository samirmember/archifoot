import { Component, input } from '@angular/core';
import type { MatchResult } from 'src/app/services/result.service';
import { ResultComponent } from '../result/result.component';

@Component({
  selector: 'app-results-list',
  imports: [ResultComponent],
  templateUrl: './results-list.component.html',
  styleUrl: './results-list.component.scss',
})
export class ResultsListComponent {
  list = input<MatchResult[]>([]);
}
