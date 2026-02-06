import { Injectable } from '@angular/core';
import { catchError, forkJoin, map, Observable, of, switchMap } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';

interface ApiFixture {
  id?: number;
  '@id'?: string;
  externalMatchNo?: number | null;
  matchDate?: string | null;
  season?: string | null;
  editions?: string[];
  stages?: string[];
  competitions?: string[];
  country?: string | null;
  city?: string | null;
  stadium?: string | null;
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
  team?: string | null;
  venueRole?: string | null;
}

interface ApiNamedResource {
  '@id'?: string;
  name?: string | null;
  displayName?: string | null;
  label?: string | null;
  code?: string | null;
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
  private readonly resourceNameCache = new Map<string, string | null>();

  constructor(private readonly apiClient: ApiClientService) {}

  public getResults(): Observable<MatchResult[]> {
    return this.apiClient
      .getCollection<ApiFixture>('fixtures', {
        pagination: 'false',
        'order[matchDate]': 'desc',
      })
      .pipe(
        map((response) => {
          if (Array.isArray(response)) {
            return response as ApiFixture[];
          }

          return Array.isArray(response['hydra:member']) ? response['hydra:member'] : [];
        }),
        switchMap((fixtures) => {
          if (fixtures.length === 0) {
            return of([]);
          }

          return forkJoin(fixtures.map((fixture) => this.mapFixtureToResult(fixture)));
        }),
      );
  }

  private mapFixtureToResult(fixture: ApiFixture): Observable<MatchResult> {
    const fixtureIri = this.extractIriFromResource(fixture);

    const participants$ = fixtureIri
      ? this.apiClient
          .getCollection<ApiFixtureParticipant>('fixture_participants', {
            pagination: 'false',
            fixture: fixtureIri,
          })
          .pipe(
            map((response) =>
              Array.isArray(response) ? response : (response['hydra:member'] ?? []),
            ),
            catchError(() => of([])),
          )
      : of([]);

    return participants$.pipe(
      switchMap((participants) => {
        const sortedParticipants = [...participants].sort(
          (a, b) => (a.role === 'A' ? -1 : 1) - (b.role === 'A' ? -1 : 1),
        );
        const participantA = sortedParticipants[0];
        const participantB = sortedParticipants[1];

        return forkJoin({
          countryA: this.resolveResourceName(participantA?.team),
          countryB: this.resolveResourceName(participantB?.team),
          edition: this.resolveResourceName(fixture.editions?.[0]),
          stage: this.resolveResourceName(fixture.stages?.[0]),
          competition: this.resolveResourceName(fixture.competitions?.[0]),
          season: this.resolveResourceName(fixture.season),
          city: this.resolveResourceName(fixture.city),
          stadium: this.resolveResourceName(fixture.stadium),
        }).pipe(
          map((resolved) => ({
            fixtureId: fixture.id ?? this.extractIdFromIri(fixtureIri),
            externalMatchNo: fixture.externalMatchNo ?? null,
            countryA: resolved.countryA ?? 'Équipe A',
            countryB: resolved.countryB ?? 'Équipe B',
            edition: resolved.edition,
            stage: resolved.stage,
            competition: resolved.competition,
            scoreA: participantA?.score ?? null,
            scoreB: participantB?.score ?? null,
            date: fixture.matchDate ?? null,
            season: resolved.season,
            venueRoleA: participantA?.venueRole ?? null,
            venueRoleB: participantB?.venueRole ?? null,
            isOfficial: fixture.isOfficial ?? null,
            played: fixture.played ?? null,
            city: resolved.city,
            stadium: resolved.stadium,
            notes: fixture.notes ?? null,
          })),
        );
      }),
    );
  }

  private resolveResourceName(resourceIri?: string | null): Observable<string | null> {
    if (!resourceIri) {
      return of(null);
    }

    const cachedValue = this.resourceNameCache.get(resourceIri);
    if (cachedValue !== undefined) {
      return of(cachedValue);
    }

    const parsed = this.parseResourceIri(resourceIri);
    if (!parsed) {
      this.resourceNameCache.set(resourceIri, null);
      return of(null);
    }

    return this.apiClient.getItem<ApiNamedResource>(parsed.resource, parsed.id).pipe(
      map((item) => {
        const name = item.displayName ?? item.name ?? item.label ?? item.code ?? null;
        this.resourceNameCache.set(resourceIri, name);
        return name;
      }),
      catchError(() => {
        this.resourceNameCache.set(resourceIri, null);
        return of(null);
      }),
    );
  }

  private parseResourceIri(resourceIri: string): { resource: string; id: string } | null {
    const cleaned = resourceIri.trim();
    if (!cleaned.startsWith('/api/')) {
      return null;
    }

    const segments = cleaned.split('/').filter(Boolean);
    if (segments.length < 3) {
      return null;
    }

    const id = segments[segments.length - 1];
    const resource = segments[segments.length - 2];

    return { resource, id };
  }

  private extractIriFromResource(resource: ApiFixture): string | null {
    return resource['@id'] ?? (resource.id ? `/api/fixtures/${resource.id}` : null);
  }

  private extractIdFromIri(resourceIri: string | null): number | null {
    if (!resourceIri) {
      return null;
    }

    const idAsString = resourceIri.split('/').filter(Boolean).pop();
    const id = Number(idAsString);

    return Number.isNaN(id) ? null : id;
  }
}
