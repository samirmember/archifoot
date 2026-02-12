import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';
import { MatchScoresheetDetailsResponse } from '../models/match-scoresheet.model';

@Injectable({
  providedIn: 'root',
})
export class MatchScoresheetService {
  constructor(private readonly apiClient: ApiClientService) {}

  public getMatchScoresheetDetails(fixtureId: number): Observable<MatchScoresheetDetailsResponse> {
    return this.apiClient.get<MatchScoresheetDetailsResponse>(
      `senior-national-team/matchs/${fixtureId}/scoresheet`,
    );
  }
}
