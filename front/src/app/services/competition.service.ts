import { Injectable } from '@angular/core';
import { catchError, map, Observable, of } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';

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
    return this.apiClient.getCollection<Competition>('competitions', {
      pagination: false, // Angular => "false"
      'order[name]': 'asc', // sera encodé en order%5Bname%5D
    });
  }
}
