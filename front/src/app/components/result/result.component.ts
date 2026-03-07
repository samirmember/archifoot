import { DatePipe } from '@angular/common';
import { Component, computed, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { FlagComponent } from 'src/app/layouts/flag/flag.component';
import { MatchResult } from 'src/app/services/result.service';
import { TooltipModule } from 'primeng/tooltip';

@Component({
  selector: 'app-result',
  imports: [FlagComponent, DatePipe, TooltipModule, RouterLink],
  templateUrl: './result.component.html',
  styleUrl: './result.component.scss',
  host: {
    '[class.result-sm]': "size() === 'small'",
  },
})
export class ResultComponent {
  result = input.required<MatchResult>();
  size = input<'normal' | 'small'>('normal');
  readonly iso2A = computed(() => this.result().countryCodeA?.toLowerCase() ?? '');
  readonly iso2B = computed(() => this.result().countryCodeB?.toLowerCase() ?? '');
}
