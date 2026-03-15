import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';
import { environment } from '../../environments/environment';
import { MatchResult } from './result.service';

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
  featurePhotoUrl: string | null;
  mainClubs: string[] | null;
  galleryPhotos: Array<{
    id: number;
    imageUrl: string;
    caption: string | null;
    sortOrder: number;
  }>;
  profile: {
    birthDate: string | null;
    deathDate: string | null;
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
  appearanceOptions: {
    years: number[];
    competitions: Array<{
      id: number;
      name: string;
    }>;
  };
  appearancesMeta: {
    total: number;
  };
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
    duelsWon: number | null;
    firstCapDate: string | null;
    lastCapDate: string | null;
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

interface SeniorPlayerDetailApiResponse {
  data: SeniorPlayerDetail;
}

export interface SeniorPlayerAppearancesResponse {
  items: MatchResult[];
  meta: {
    page: number;
    itemsPerPage: number;
    total: number;
    totalPages: number;
  };
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
      .get<SeniorPlayerDetail | SeniorPlayerDetailApiResponse>(
        `senior-national-team/players/${encodeURIComponent(slug)}`,
      )
      .pipe(
        map((response) => ('data' in response ? response.data : response)),
        map((profile) => ({
          ...profile,
          photoUrl: this.toPlayerPhotoUrl(profile.photoUrl),
          featurePhotoUrl: this.toPlayerPhotoUrl(profile.featurePhotoUrl, 'feature'),
          mainClubs: this.normalizeStringList(profile.mainClubs),
          galleryPhotos: (profile.galleryPhotos ?? []).map((photo) => ({
            ...photo,
            imageUrl: this.toPlayerPhotoUrl(photo.imageUrl, 'gallery') ?? photo.imageUrl,
          })),
          stats: {
            ...profile.stats,
            duelsWon: profile.stats.duelsWon ?? this.extractDuelsWon(profile.futureDataPlaceholders),
          },
        })),
      );
  }

  public getSeniorNationalTeamPlayerAppearances(
    slug: string,
    filters: {
      page?: number;
      itemsPerPage?: number;
      seasonName?: string;
      teamIso3?: string;
      competitionId?: number;
    } = {},
  ): Observable<SeniorPlayerAppearancesResponse> {
    return this.apiClient.get<SeniorPlayerAppearancesResponse>(
      `senior-national-team/players/${encodeURIComponent(slug)}/appearances`,
      filters,
    );
  }

  private normalizeStringList(values: unknown): string[] | null {
    if (!Array.isArray(values)) {
      return null;
    }

    const uniqueValues: string[] = [];
    const seen = new Set<string>();

    for (const value of values) {
      const trimmedValue =
        typeof value === 'string' || typeof value === 'number' ? String(value).trim() : '';
      if (!trimmedValue) {
        continue;
      }

      const normalizedValue = trimmedValue.toLocaleLowerCase();
      if (seen.has(normalizedValue)) {
        continue;
      }

      seen.add(normalizedValue);
      uniqueValues.push(trimmedValue);
    }

    return uniqueValues.length > 0 ? uniqueValues : null;
  }

  private extractDuelsWon(placeholders: StatPlaceholder[] | undefined): number | null {
    if (!placeholders?.length) {
      return null;
    }

    const duelsPlaceholder = placeholders.find(
      (placeholder) => placeholder.label.trim().toLowerCase() === 'duels gagnés',
    );

    if (!duelsPlaceholder || duelsPlaceholder.value === null || duelsPlaceholder.value === '') {
      return null;
    }

    const parsedValue = Number(duelsPlaceholder.value);
    return Number.isFinite(parsedValue) ? parsedValue : null;
  }

  private toPlayerPhotoUrl(photoUrl: string | null, subfolder: string = ''): string | null {
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

    return `${this.apiOrigin}/api/person-photo/players/${subfolder ? subfolder + '/' : ''}${encodedPath}`;
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
