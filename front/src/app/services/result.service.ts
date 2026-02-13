import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';
import { ApiFixture, ApiFixtureStageCompetition } from '../models/api-fixture.model';

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
  editions: string[] | null;
  stages: string[] | null;
  competitions?: ApiFixtureStageCompetition[];
  scoreA: number | null;
  scoreB: number | null;
  categoryA: string;
  categoryB: string;
  date: string | null;
  season: string | null;
  isOfficial: boolean | null;
  played: boolean | null;
  city: string | null;
  stadium: string | null;
  countryStadiumName: string | null;
  notes: string | null;
  competitionLabel?: string;
}

export interface FixturesStats {
  totalMatches: number;
  totalWins: number;
  totalGoals: number;
  trophyWins: number;
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
    const isString = (v: unknown): v is string => typeof v === 'string';
    const stages = fixture.stages?.map((stage) => stage.name).filter(isString);
    const editions = fixture.stages?.map((stage) => stage.edition?.name).filter(isString);

    return {
      fixtureId: fixture.id ?? null,
      externalMatchNo: fixture.externalMatchNo ?? null,
      countryA: fixture.teamA?.name ?? 'Équipe A',
      countryCodeA: fixture.teamA?.iso2,
      countryCodeB: fixture.teamB?.iso2,
      countryB: fixture.teamB?.name ?? 'Équipe B',
      stages: stages ?? null,
      editions: editions ?? null,
      competitions: fixture.competitions,
      scoreA: fixture.scoreA ?? null,
      scoreB: fixture.scoreB ?? null,
      date: fixture.matchDate ?? null,
      season: fixture.seasonName ?? null,
      isOfficial: fixture.isOfficial ?? null,
      played: fixture.played ?? null,
      city: fixture.cityName ?? null,
      stadium: fixture.stadiumName ?? null,
      countryStadiumName: fixture.countryStadiumName,
      notes: fixture.notes ?? null,
      competitionLabel: this.getCompetitionLabels(fixture).join(' | '),
      categoryA: fixture.categories[0].name ?? '',
      categoryB: fixture.categories[1].name ?? '',
    };
  }

  /**
   * Construit les libellés "Stage Compétition Édition" par compétition.
   * - Si stages/editions absents -> ["Nom compétition", ...]
   * - Sinon -> ["Stage Nom compétition Édition", ...]
   */
  private getCompetitionLabels(fixture: ApiFixture): string[] {
    const competitions = fixture.competitions ?? [];
    const stages = fixture.stages ?? [];

    // Si pas de compétition => rien à construire
    if (competitions.length === 0) return [];

    // Contrôle de cohérence (si stages fournis)
    if (stages.length > 0 && stages.length !== competitions.length) {
      console.warn(
        `[getCompetitionContextLabels] Incohérence: competitions=${competitions.length}, stages=${stages.length} (externalMatchNo=${fixture.externalMatchNo ?? 'n/a'})`,
      );
    }

    return competitions
      .map((comp, i) => {
        const compName = (comp?.name ?? '').trim();
        const stage = stages[i];

        const stageName = (stage?.name ?? '').trim();
        const editionName = (stage?.edition?.name ?? '').trim();

        // Si on veut être strict sur "édition doit correspondre à la compétition",
        // on peut vérifier stage.edition?.competition?.id === comp.id (si ids présents).
        // Ici, on reste best-effort.

        // Cas "match amical" : pas de stage + pas d'édition => juste le nom compétition
        if (!stageName && !editionName) return compName;

        // Concat propre (sans doubles espaces)
        return [stageName.charAt(0).toUpperCase() + stageName.slice(1), compName, editionName]
          .filter(Boolean)
          .join(' ');
      })
      .filter(Boolean); // au cas où compName est vide/null
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

  public buildFixturesStats(): Observable<FixturesStats> {
    return this.apiClient.get<FixturesStats>('senior-national-team/matchs/totals');
  }
}
