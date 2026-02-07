import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';
import { ApiFixture } from '../models/api-fixture.model';

export interface ResultFilters {
  seasonName?: string;
  teamName?: string;
  teamIso3?: string;
  competitionName?: string;
  competitionId?: number;
  page?: number;
  itemsPerPage?: number;
}

export interface MatchResult {
  fixtureId: number | null;
  externalMatchNo: number | null;
  countryA: string;
  countryB: string;
  countryCodeA: string | null | undefined;
  countryCodeB: string | null | undefined;
  edition: string | null;
  stage: string | null;
  competition: string | null;
  scoreA: number | null;
  scoreB: number | null;
  date: string | null;
  season: string | null;
  isOfficial: boolean | null;
  played: boolean | null;
  city: string | null;
  stadium: string | null;
  notes: string | null;
}

@Injectable({
  providedIn: 'root',
})
export class ResultService {
  constructor(private readonly apiClient: ApiClientService) {}

  public getResults(filters?: ResultFilters): Observable<MatchResult[]> {
    return this.apiClient
      .getCollection<ApiFixture>('fixtures', this.buildFixtureFilters(filters))
      .pipe(
        map((response) => (Array.isArray(response) ? response : [])),
        map((fixtures) => fixtures.map((fixture) => this.mapFixtureToResult(fixture))),
      );
  }

  private mapFixtureToResult(fixture: ApiFixture): MatchResult {
    const firstStage = fixture.stages?.[0];

    return {
      fixtureId: fixture.id ?? null,
      externalMatchNo: fixture.externalMatchNo ?? null,
      countryA: fixture.teamA?.name ?? 'Équipe A',
      countryCodeA: fixture.teamA?.iso2,
      countryCodeB: fixture.teamB?.iso2,
      countryB: fixture.teamB?.name ?? 'Équipe B',
      edition: firstStage?.edition?.name ?? null,
      stage: firstStage?.name ?? null,
      competition: firstStage?.edition?.competition?.name ?? null,
      scoreA: fixture.scoreA ?? null,
      scoreB: fixture.scoreB ?? null,
      date: fixture.matchDate ?? null,
      season: fixture.seasonName ?? null,
      isOfficial: fixture.isOfficial ?? null,
      played: fixture.played ?? null,
      city: fixture.cityName ?? null,
      stadium: fixture.stadiumName ?? null,
      notes: fixture.notes ?? null,
    };
  }

  private buildFixtureFilters(filters?: ResultFilters): Record<string, string> {
    const params: Record<string, string> = {
      page: String(filters?.page ?? 1),
      itemsPerPage: String(filters?.itemsPerPage ?? 20),
      'order[matchDate]': 'desc',
    };

    if (!filters) {
      return params;
    }

    if (filters.seasonName) {
      params['season.name'] = filters.seasonName;
    }

    if (filters.competitionName) {
      params['competitions.name'] = filters.competitionName;
    }

    if (filters.competitionId) {
      params['competitions.id'] = String(filters.competitionId);
    }

    if (filters.teamName) {
      params['participants.team.displayName'] = filters.teamName;
      params['participants.team.nationalTeam.name'] = filters.teamName;
      params['participants.team.club.name'] = filters.teamName;
    }

    if (filters.teamIso3) {
      params['participants.team.nationalTeam.country.iso3'] = filters.teamIso3;
      params['participants.team.club.country.iso3'] = filters.teamIso3;
    }

    return params;
  }
}
