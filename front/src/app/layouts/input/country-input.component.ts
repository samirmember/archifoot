import { Component, inject, input, output, viewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AutoComplete, AutoCompleteModule } from 'primeng/autocomplete';
import { finalize } from 'rxjs';
import { CountryService } from '../../services/country.service';
import { Country } from '../../models/country.model';

@Component({
  selector: 'app-country-input',
  standalone: true,
  imports: [CommonModule, FormsModule, AutoCompleteModule],
  templateUrl: './country-input.component.html',
  styleUrl: './country-input.component.scss',
})
export class CountryInputComponent {
  id = input.required<string>();
  name = input.required<string>();
  placeholder = input<string>('Tous les pays');
  countryService = inject(CountryService);
  autoComplete = viewChild<AutoComplete>(AutoComplete);
  selectedCountry: Country | null = null;
  selectedCountryChange = output<Country | null>();
  suggestions: Country[] = [];
  loading = false;

  onSelectedCountryChange(country: Country | null): void {
    this.selectedCountry = country;
    this.selectedCountryChange.emit(country);
  }

  search(event: { query: string }): void {
    const q = (event.query ?? '').trim();

    // minLength côté PrimeNG + garde-fou
    if (q.length < 3) {
      this.suggestions = [];
      this.loading = false;
      return;
    }

    this.loading = true;
    this.countryService
      .getCountries(q)
      .pipe(
        finalize(() => {
          this.loading = false;
        }),
      )
      .subscribe({
        next: (items) => {
          this.suggestions = items;
          queueMicrotask(() => this.autoComplete()?.show());
        },
        error: () => {
          this.suggestions = [];
        },
      });
  }

  flagUrl(iso2?: string): string | null {
    if (!iso2) return null;
    iso2 = iso2.toLowerCase();
    if (iso2 === 'su') {
      return `assets/img/urss-flag.png`;
    }
    return `https://flagcdn.com/24x18/${iso2}.png`;
  }
}
