import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';
import { environment } from '../../environments/environment';

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
    subIn: number;
    captaincies: number;
    scoredGoalsFromMatchEvents: number;
    yellowCards: number;
    redCards: number;
    lastCapDate: string;
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
  label: string;
  value: string | number | null;
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
  private readonly apiOrigin = new URL(environment.api.baseUrl).origin;

  constructor(private readonly apiClient: ApiClientService) {}

  public getSeniorNationalTeamPlayers(
    page: number,
    perPage: 12 | 24,
    query = '',
  ): Observable<SeniorPlayersResponse> {
    return this.apiClient
      .get<SeniorPlayersResponse>('senior-national-team/players', {
        page,
        perPage,
        q: query,
      })
      .pipe(
        map((response) => ({
          ...response,
          items: response.items.map((player) => ({
            ...player,
            photoUrl: this.toPlayerPhotoUrl(player.photoUrl),
          })),
        })),
      );
  }

  public getSeniorNationalTeamPlayerProfile(slug: string): Observable<SeniorPlayerDetail> {
    return this.apiClient
      .get<SeniorPlayerDetail>(`senior-national-team/players/${encodeURIComponent(slug)}`)
      .pipe(
        map((profile) => ({
          ...profile,
          photoUrl: this.toPlayerPhotoUrl(profile.photoUrl),
        })),
      );
  }

  private toPlayerPhotoUrl(photoUrl: string | null): string | null {
    if (!photoUrl) {
      return null;
    }

    if (/^(https?:)?\/\//i.test(photoUrl) || photoUrl.startsWith('data:')) {
      return photoUrl;
    }

    const normalizedPath = photoUrl.replace(/^\/+/, '');

    if (normalizedPath.startsWith('uploads/')) {
      return `${this.apiOrigin}/${normalizedPath}`;
    }

    const encodedPath = normalizedPath
      .split('/')
      .filter((segment) => segment.length > 0)
      .map((segment) => encodeURIComponent(segment))
      .join('/');

    return `${this.apiOrigin}/api/person-photo/players/${encodedPath}`;
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
