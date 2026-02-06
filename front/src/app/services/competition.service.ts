import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiClientService } from '../../shared/api/api-client.service';

export interface Competition {
  id: number;
  name: string | null;
  category?: string | null;
  [key: string]: unknown;
}

@Injectable({
  providedIn: 'root',
})
export class CompetitionService {
  constructor(private readonly apiClient: ApiClientService) {}

  public getCompetitions(): Observable<Competition[]> {
    return this.apiClient.getCollection<Competition>('competitions', { pagination: 'false' }).pipe(
      map((response) => {
        if (Array.isArray(response)) {
          return response as Competition[];
        }

        return Array.isArray(response['hydra:member']) ? response['hydra:member'] : [];
      }),
    );
  }
}
