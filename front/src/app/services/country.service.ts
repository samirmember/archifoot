import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiClientService } from '../../shared/api/api-client.service';
import { Country } from '../models/country.model';

type HydraCollection<T> = { 'hydra:member': T[] };

@Injectable({ providedIn: 'root' })
export class CountryService {
  constructor(private readonly apiClient: ApiClientService) {}

  public getCountries(filterName?: string): Observable<Country[]> {
    const params: Record<string, string> = {
      pagination: 'false',
      'order[name]': 'asc',
    };

    const q = (filterName ?? '').trim();
    if (q.length > 0) {
      params['name'] = q;
    }

    return this.apiClient.getCollection<Country>('countries', params).pipe(
      map((response: unknown) => {
        // 1) Si l'API renvoie un tableau directement
        if (Array.isArray(response)) return response as Country[];

        // 2) Si l'API renvoie Hydra
        const hydra = response as HydraCollection<Country>;
        if (hydra && Array.isArray(hydra['hydra:member'])) return hydra['hydra:member'];

        // 3) Fallback safe
        return [];
      }),
    );
  }
}
