import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiClientService } from '../../shared/api/api-client.service';
import { Country } from '../models/country.model';

@Injectable({
  providedIn: 'root',
})
export class CountriesService {
  constructor(private readonly apiClient: ApiClientService) {}

  public getCountries(filterName?: string): Observable<Country[]> {
    const params: Record<string, string> = {
      pagination: 'true',
      'order[name]': 'asc',
    };

    const q = (filterName ?? '').trim();
    if (q.length > 0) {
      params['name'] = q;
    }

    return this.apiClient
      .getCollection<Country>('countries', params)
      .pipe(map((response) => response['hydra:member'] as Country[]));
  }
}
