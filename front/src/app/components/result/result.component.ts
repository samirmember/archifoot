import { DatePipe } from '@angular/common';
import { AfterViewInit, Component, computed, input, ElementRef } from '@angular/core';
import { FlagComponent } from 'src/app/layouts/flag/flag.component';
import { MatchResult } from 'src/app/services/result.service';
import { Tooltip } from 'bootstrap';
import { TooltipModule } from 'primeng/tooltip';

@Component({
  selector: 'app-result',
  imports: [FlagComponent, DatePipe, TooltipModule],
  templateUrl: './result.component.html',
  styleUrl: './result.component.scss',
})
export class ResultComponent implements AfterViewInit {
  result = input.required<MatchResult>();
  readonly iso2A = computed(() => this.result().countryCodeA?.toLowerCase() ?? '');
  readonly iso2B = computed(() => this.result().countryCodeB?.toLowerCase() ?? '');

  constructor(private readonly elRef: ElementRef<HTMLElement>) {}

  ngAfterViewInit(): void {
    this.initTooltip();
  }

  private initTooltip(): void {
    const root = this.elRef.nativeElement;

    root.querySelectorAll<HTMLElement>('[data-bs-toggle="tooltip"]').forEach((el) => {
      Tooltip.getOrCreateInstance(el, {
        container: 'body',
        boundary: 'window',
        placement: 'top',
        popperConfig: {
          strategy: 'fixed',
        },
      });
    });
  }
}
