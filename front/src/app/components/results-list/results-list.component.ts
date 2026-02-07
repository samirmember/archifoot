import { Component, computed, inject, input, OnInit, signal } from '@angular/core';
import { Country } from 'src/app/models/country.model';
import { Competition } from 'src/app/services/competition.service';
import { ResultService } from 'src/app/services/result.service';
import type { ResultFilters, MatchResult } from 'src/app/services/result.service';
import { ResultComponent } from '../result/result.component';

@Component({
  selector: 'app-results-list',
  imports: [ResultComponent],
  templateUrl: './results-list.component.html',
  styleUrl: './results-list.component.scss',
})
export class ResultsListComponent {
  // country = input<Country>();
  // year = input<number>();
  // competition = input<Competition>();
  // resultService = inject(ResultService);
  // resultList = signal<MatchResult[]>([]);

  list = input<MatchResult[]>([]);
}
