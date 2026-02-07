import { Component, computed, input } from '@angular/core';

@Component({
  selector: 'app-flag',
  imports: [],
  templateUrl: './flag.component.html',
  styleUrl: './flag.component.scss',
})
export class FlagComponent {
  countryName = input.required<string>();
  iso2 = input.required<string>();
  position = input.required<'A' | 'B'>();
  readonly flagUrls = computed<string[]>(() => {
    const iso2 = this.iso2().toLowerCase() ?? '';
    return ['https://flagcdn.com/w80/' + iso2, 'https://flagcdn.com/w160/' + iso2];
  });
}
