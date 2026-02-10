import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';

export interface SeniorPlayer {
  id: number;
  fullName: string;
  photoUrl: string | null;
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

export interface PlayerStats {
  caps: number;
  goals: number;
  starts: number;
  subIn: number;
  yellowCards: number;
  redCards: number;
  captainMatches: number;
  lastCapDate: string | null;
}

export interface MembershipHistoryItem {
  teamName: string;
  periodLabel: string;
  isCurrent: boolean;
}

export interface StatPlaceholder {
  key: string;
  title: string;
  description: string;
  dynamic: boolean;
}

export interface PlayerProfile {
  id: number;
  slug: string;
  fullName: string;
  photoUrl: string | null;
  position: string | null;
  nationality: string | null;
  birthDateLabel: string | null;
  birthPlace: string | null;
  currentClub: string | null;
  shirtNumber: string | null;
  stats: PlayerStats;
  clubHistory: MembershipHistoryItem[];
  futureStats: StatPlaceholder[];
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

  public getSeniorNationalTeamPlayerProfile(slug: string): Observable<PlayerProfile> {
    return this.apiClient.get<PlayerProfile>(`senior-national-team/players/${encodeURIComponent(slug)}`);
  }

  public toSlug(value: string): string {
    return value
      .normalize('NFD')
      .replace(/\p{Diacritic}/gu, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .replace(/-{2,}/g, '-');
  }
}
