import { DatePipe } from '@angular/common';
import { Component, computed, input } from '@angular/core';
import { MatchResult } from 'src/app/services/result.service';

@Component({
  selector: 'app-result',
  imports: [DatePipe],
  templateUrl: './result.component.html',
  styleUrl: './result.component.scss',
})
export class ResultComponent {
  result = input.required<MatchResult>();
  readonly iso2A = computed(() => this.result().countryCodeA?.toLowerCase() ?? '');
  readonly iso2B = computed(() => this.result().countryCodeB?.toLowerCase() ?? '');
}
