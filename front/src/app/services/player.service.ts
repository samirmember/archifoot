import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';

export interface SeniorPlayer {
  id: number;
  fullName: string;
  photoUrl: string | null;
}

export interface SeniorPlayerDetail {
  id: number;
  slug: string;
  fullName: string;
  photoUrl: string | null;
  profile: {
    birthDate: string | null;
    birthCity: string | null;
    birthRegion: string | null;
    birthCountry: string | null;
    nationalityCountry: string | null;
    primaryPositionCode: string | null;
    primaryPositionLabel: string | null;
  };
  memberships: Array<{
    id: number;
    fromDate: string | null;
    toDate: string | null;
    isCurrent: boolean;
    sourceNote: string | null;
    teamDisplayName: string | null;
    teamType: string | null;
    clubName: string | null;
    countryName: string | null;
    nationalTeamName: string | null;
  }>;
  nationalStats: {
    totals: {
      caps: number;
      goals: number;
    };
    records: Array<{
      id: number;
      caps: number | null;
      goals: number | null;
      fromDate: string | null;
      toDate: string | null;
      sourceNote: string | null;
      teamDisplayName: string | null;
    }>;
  };
  stats: {
    caps: number;
    goals: number;
    starts: number;
    benchAppearances: number;
    captaincies: number;
    scoredGoalsFromMatchEvents: number;
    yellowCards: number;
    redCards: number;
  };
  timeline: {
    memberships: SeniorPlayerDetail['memberships'];
    nationalStatRecords: SeniorPlayerDetail['nationalStats']['records'];
  };
  futureDataPlaceholders: Array<{
    label: string;
    value: string | number | null;
  }>;
}

export interface SeniorPlayersResponse {
  items: SeniorPlayer[];
  meta: {
    page: number;
    perPage: number;
    total: number;
    totalPages: number;
  };
}

@Injectable({
  providedIn: 'root',
})
export class PlayerService {
  constructor(private readonly apiClient: ApiClientService) {}

  public getSeniorNationalTeamPlayers(
    page: number,
    perPage: 10 | 20,
    query = '',
  ): Observable<SeniorPlayersResponse> {
    return this.apiClient.get<SeniorPlayersResponse>('senior-national-team/players', {
      page,
      perPage,
      q: query,
    });
  }

  public getSeniorNationalTeamPlayerDetails(slug: string): Observable<SeniorPlayerDetail> {
    return this.apiClient.get<SeniorPlayerDetail>(`senior-national-team/players/${slug}`);
  }
}
