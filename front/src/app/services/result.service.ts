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

export interface MatchResultsSummary {
  totalMatches: number;
  wins: number;
  draws: number;
  losses: number;
  winRate: number;
  goalsFor: number;
  goalsAgainst: number;
  goalDifference: number;
  cleanSheets: number;
  uniqueOpponents: number;
  uniqueHostCountries: number;
  officialMatches: number;
  officialRate: number;
}

type CompetitionContextFixture = Pick<ApiFixture, 'competitions' | 'stages' | 'externalMatchNo'>;

export function buildCompetitionLabels(fixture: CompetitionContextFixture): string[] {
  const competitions = fixture.competitions ?? [];
  const stages = fixture.stages ?? [];

  if (competitions.length === 0) return [];

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

      if (!stageName && !editionName) return compName;

      return [stageName.charAt(0).toUpperCase() + stageName.slice(1), compName, editionName]
        .filter(Boolean)
        .join(' ');
    })
    .filter(Boolean);
}

@Injectable({
  providedIn: 'root',
})
export class ResultService {
  private static readonly SUMMARY_PAGE_SIZE = 2000;

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
      competitionLabel: buildCompetitionLabels(fixture).join(' | '),
      categoryA: fixture.categories[0].name ?? '',
      categoryB: fixture.categories[1].name ?? '',
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

  public buildFixturesStats(): Observable<FixturesStats> {
    return this.apiClient.get<FixturesStats>('senior-national-team/matchs/totals');
  }

  public getResultsSummary(filters?: ResultFilters): Observable<MatchResultsSummary> {
    return this.getResults({
      ...filters,
      page: 1,
      itemsPerPage: ResultService.SUMMARY_PAGE_SIZE,
    }).pipe(map((results) => this.buildResultsSummary(results)));
  }

  private buildResultsSummary(results: MatchResult[]): MatchResultsSummary {
    let wins = 0;
    let draws = 0;
    let losses = 0;
    let goalsFor = 0;
    let goalsAgainst = 0;
    let cleanSheets = 0;
    let officialMatches = 0;

    const opponentKeys = new Set<string>();
    const hostCountryKeys = new Set<string>();

    for (const result of results) {
      const perspective = this.getAlgeriaPerspective(result);

      if (result.isOfficial) {
        officialMatches++;
      }

      if (result.countryStadiumName?.trim()) {
        hostCountryKeys.add(this.toStatKey(result.countryStadiumName));
      }

      if (perspective.opponentName?.trim()) {
        opponentKeys.add(this.toStatKey(perspective.opponentName));
      }

      if (perspective.scoreFor === null || perspective.scoreAgainst === null) {
        continue;
      }

      goalsFor += perspective.scoreFor;
      goalsAgainst += perspective.scoreAgainst;

      if (perspective.scoreAgainst === 0) {
        cleanSheets++;
      }

      if (perspective.scoreFor > perspective.scoreAgainst) {
        wins++;
      } else if (perspective.scoreFor === perspective.scoreAgainst) {
        draws++;
      } else {
        losses++;
      }
    }

    const totalMatches = results.length;

    return {
      totalMatches,
      wins,
      draws,
      losses,
      winRate: this.toPercentage(wins, totalMatches),
      goalsFor,
      goalsAgainst,
      goalDifference: goalsFor - goalsAgainst,
      cleanSheets,
      uniqueOpponents: opponentKeys.size,
      uniqueHostCountries: hostCountryKeys.size,
      officialMatches,
      officialRate: this.toPercentage(officialMatches, totalMatches),
    };
  }

  private getAlgeriaPerspective(result: MatchResult): {
    scoreFor: number | null;
    scoreAgainst: number | null;
    opponentName: string | null;
  } {
    const algeriaSide = this.detectAlgeriaSide(result);

    if (algeriaSide === 'B') {
      return {
        scoreFor: result.scoreB,
        scoreAgainst: result.scoreA,
        opponentName: result.countryA,
      };
    }

    return {
      scoreFor: result.scoreA,
      scoreAgainst: result.scoreB,
      opponentName: result.countryB,
    };
  }

  private detectAlgeriaSide(result: MatchResult): 'A' | 'B' {
    const teamAIsAlgeria = this.isAlgeriaTeam(result.countryA, result.countryCodeA);
    const teamBIsAlgeria = this.isAlgeriaTeam(result.countryB, result.countryCodeB);

    if (teamAIsAlgeria && !teamBIsAlgeria) {
      return 'A';
    }

    if (teamBIsAlgeria && !teamAIsAlgeria) {
      return 'B';
    }

    return 'A';
  }

  private isAlgeriaTeam(name: string | null | undefined, iso2: string | null | undefined): boolean {
    const normalizedIso2 = iso2?.trim().toLowerCase();
    if (normalizedIso2 === 'dz') {
      return true;
    }

    const normalizedName = this.toStatKey(name);
    return normalizedName === 'algérie' || normalizedName === 'algerie' || normalizedName === 'algeria';
  }

  private toStatKey(value: string | null | undefined): string {
    return value?.trim().toLocaleLowerCase() ?? '';
  }

  private toPercentage(value: number, total: number): number {
    if (total <= 0) {
      return 0;
    }

    return Math.round((value / total) * 100);
  }
}
