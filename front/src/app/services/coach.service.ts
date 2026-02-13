import { Injectable } from '@angular/core';
import { Observable, catchError, of } from 'rxjs';
import { ApiClientService } from '../core/http/api-client.service';

export interface SeniorCoachListItem {
  id: number;
  fullName: string;
  role: string | null;
  nationality: string | null;
}

export interface SeniorCoachesResponse {
  items: SeniorCoachListItem[];
  meta: {
    page: number;
    perPage: number;
    total: number;
    totalPages: number;
  };
}

export interface SeniorCoachHighlights {
  trophies: number;
  matchCount: number;
  wins: number;
  draws: number;
  losses: number;
  goalsFor: number;
  goalsAgainst: number;
  cleanSheets: number;
  debutMatch: string;
  lastMatch: string;
}

export interface SeniorCoachTeamPeriod {
  id: string;
  teamName: string;
  role: string;
  startDate: string;
  endDate?: string;
  isCurrent?: boolean;
}

export interface SeniorCoachCompetitionStat {
  id: string;
  competition: string;
  matches: number;
  wins: number;
  draws: number;
  losses: number;
  goalsFor: number;
  goalsAgainst: number;
}

export interface SeniorCoachMilestone {
  id: string;
  date?: string;
  label: string;
  value?: string;
}

export interface SeniorCoachFutureDataBlock {
  label: string;
  value?: string;
}

export interface SeniorCoach {
  id: string;
  slug: string;
  fullName: string;
  role: string;
  nationality: string;
  birthDate?: string;
  birthPlace?: string;
  portraitUrl?: string;
  contractUntil?: string;
  preferredSystem?: string;
  badges: string[];
  highlights: SeniorCoachHighlights;
  biography: string;
  careerPath: SeniorCoachTeamPeriod[];
  competitionStats: SeniorCoachCompetitionStat[];
  milestones: SeniorCoachMilestone[];
  staff: string[];
  futureDataPlaceholders: SeniorCoachFutureDataBlock[];
}

@Injectable({ providedIn: 'root' })
export class CoachService {
  constructor(private readonly apiClient: ApiClientService) {}

  getSeniorNationalTeamCoaches(
    page: number,
    perPage: 12 | 24,
    query = '',
  ): Observable<SeniorCoachesResponse> {
    return this.apiClient.get<SeniorCoachesResponse>('senior-national-team/coaches', {
      page,
      perPage,
      q: query,
    });
  }

  getSeniorNationalTeamCoachBySlug(slug: string): Observable<SeniorCoach | null> {
    return this.apiClient
      .get<SeniorCoach>(`senior-national-team/coaches/${encodeURIComponent(slug)}`)
      .pipe(catchError(() => of(null)));
  }
}
