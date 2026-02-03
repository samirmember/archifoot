import { Component, viewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AutoComplete, AutoCompleteModule } from 'primeng/autocomplete';
import { finalize } from 'rxjs';
import { CountriesService } from '../../services/countries.service';
import { Country } from '../../models/country.model';

@Component({
  selector: 'app-country-input',
  standalone: true,
  imports: [CommonModule, FormsModule, AutoCompleteModule],
  templateUrl: './country-input.component.html',
  styleUrl: './country-input.component.scss',
})
export class CountryInputComponent {
  autoComplete = viewChild<AutoComplete>(AutoComplete);
  selectedCountry: Country | null = null;
  suggestions: Country[] = [];
  loading = false;

  constructor(private readonly countriesService: CountriesService) {}

  search(event: { query: string }): void {
    const q = (event.query ?? '').trim();

    // minLength côté PrimeNG + garde-fou
    if (q.length < 3) {
      this.suggestions = [];
      this.loading = false;
      return;
    }

    this.loading = true;
    this.countriesService
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
    return `https://flagcdn.com/24x18/${iso2.toLowerCase()}.png`;
  }
}
