import { Injectable } from '@angular/core';
import { catchError, forkJoin, map, Observable, of, switchMap } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';

interface ApiFixture {
  id?: number;
  externalMatchNo?: number | null;
  matchDate?: string | null;
  seasonName?: string | null;
  editionNames?: string[];
  stageNames?: string[];
  competitionNames?: string[];
  cityName?: string | null;
  stadiumName?: string | null;
  played?: boolean | null;
  isOfficial?: boolean | null;
  notes?: string | null;
}

interface ApiFixtureParticipant {
  id?: number;
  role?: string | null;
  score?: number | null;
  scoreExtra?: number | null;
  scorePenalty?: number | null;
  teamName?: string | null;
  venueRole?: string | null;
}

export interface MatchResult {
  fixtureId: number | null;
  externalMatchNo: number | null;
  countryA: string;
  countryB: string;
  edition: string | null;
  stage: string | null;
  competition: string | null;
  scoreA: number | null;
  scoreB: number | null;
  date: string | null;
  season: string | null;
  venueRoleA: string | null;
  venueRoleB: string | null;
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

  public getResults(): Observable<MatchResult[]> {
    return this.apiClient
      .getCollection<ApiFixture>('fixtures', {
        pagination: 'false',
        'order[matchDate]': 'desc',
      })
      .pipe(
        map((response) => (Array.isArray(response) ? response : [])),
        switchMap((fixtures) => {
          if (fixtures.length === 0) {
            return of([]);
          }

          return forkJoin(fixtures.map((fixture) => this.mapFixtureToResult(fixture)));
        }),
      );
  }

  private mapFixtureToResult(fixture: ApiFixture): Observable<MatchResult> {
    const fixtureId = fixture.id ?? null;

    const participants$ = fixtureId
      ? this.apiClient
          .getCollection<ApiFixtureParticipant>('fixture_participants', {
            pagination: 'false',
            fixtureId: String(fixtureId),
          })
          .pipe(
            map((response) => (Array.isArray(response) ? response : [])),
            catchError(() => of([])),
          )
      : of([]);

    return participants$.pipe(
      map((participants) => {
        const sortedParticipants = [...participants].sort(
          (a, b) => (a.role === 'A' ? -1 : 1) - (b.role === 'A' ? -1 : 1),
        );
        const participantA = sortedParticipants[0];
        const participantB = sortedParticipants[1];

        return {
          fixtureId,
          externalMatchNo: fixture.externalMatchNo ?? null,
          countryA: participantA?.teamName ?? 'Équipe A',
          countryB: participantB?.teamName ?? 'Équipe B',
          edition: fixture.editionNames?.[0] ?? null,
          stage: fixture.stageNames?.[0] ?? null,
          competition: fixture.competitionNames?.[0] ?? null,
          scoreA: participantA?.score ?? null,
          scoreB: participantB?.score ?? null,
          date: fixture.matchDate ?? null,
          season: fixture.seasonName ?? null,
          venueRoleA: participantA?.venueRole ?? null,
          venueRoleB: participantB?.venueRole ?? null,
          isOfficial: fixture.isOfficial ?? null,
          played: fixture.played ?? null,
          city: fixture.cityName ?? null,
          stadium: fixture.stadiumName ?? null,
          notes: fixture.notes ?? null,
        };
      }),
    );
  }
}
