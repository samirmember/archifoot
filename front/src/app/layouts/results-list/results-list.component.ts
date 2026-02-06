import { Component, input } from '@angular/core';
import { Country } from 'src/app/models/country.model';
import { Competition } from 'src/app/services/competition.service';

@Component({
  selector: 'app-results-list',
  imports: [],
  templateUrl: './results-list.component.html',
  styleUrl: './results-list.component.scss',
})
export class ResultsListComponent {
  country = input<Country>();
  year = input<number>();
  competition = input<Competition>();
}
