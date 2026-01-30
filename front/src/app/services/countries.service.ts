import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiClientService } from '../../shared/api/api-client.service';
import { Country } from '../models/country.model';

@Injectable({
  providedIn: 'root',
})
export class CountriesService {
  constructor(private readonly apiClient: ApiClientService) {}

  public getCountries(): Observable<Country[]> {
    return this.apiClient
      .getCollection<Country>('countries', {
        pagination: 'true',
        'order[name]': 'asc',
      })
      .pipe(map((response) => response['hydra:member']));
  }
}
