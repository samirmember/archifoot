import { Injectable } from '@angular/core';
import { Observable, of } from 'rxjs';
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
    const coach = SENIOR_COACHES.find((entry) => entry.slug === slug) ?? null;
    return of(coach);
  }
}

const SENIOR_COACHES: SeniorCoach[] = [
  {
    id: 'coach-petkovic',
    slug: 'vladimir-petkovic',
    fullName: 'Vladimir Petković',
    role: 'Sélectionneur principal',
    nationality: 'Bosnie-Herzégovine / Suisse',
    birthDate: '1963-08-15',
    birthPlace: 'Sarajevo, Bosnie-Herzégovine',
    contractUntil: '2028-06-30',
    preferredSystem: '4-3-3 / 3-4-2-1',
    badges: ['Actuel staff EN', 'Expérience internationale', 'Orientation pressing'],
    highlights: {
      trophies: 1,
      matchCount: 22,
      wins: 14,
      draws: 5,
      losses: 3,
      goalsFor: 38,
      goalsAgainst: 17,
      cleanSheets: 9,
      debutMatch: '2024-03-22',
      lastMatch: '2025-11-18',
    },
    biography:
      'Coach orienté sur la transition rapide et le pressing médian. Travail fort sur la discipline tactique, avec adaptation du bloc en fonction de l’adversaire.',
    careerPath: [
      {
        id: 'petkovic-en',
        teamName: 'Algérie (A)',
        role: 'Sélectionneur',
        startDate: '2024-03-01',
        isCurrent: true,
      },
      {
        id: 'petkovic-bordeaux',
        teamName: 'Girondins de Bordeaux',
        role: 'Entraîneur principal',
        startDate: '2021-07-01',
        endDate: '2022-02-10',
      },
      {
        id: 'petkovic-suisse',
        teamName: 'Suisse (A)',
        role: 'Sélectionneur',
        startDate: '2014-07-01',
        endDate: '2021-07-01',
      },
    ],
    competitionStats: [
      {
        id: 'petkovic-canq',
        competition: 'Éliminatoires CAN',
        matches: 8,
        wins: 6,
        draws: 1,
        losses: 1,
        goalsFor: 17,
        goalsAgainst: 6,
      },
      {
        id: 'petkovic-cdmq',
        competition: 'Éliminatoires Mondial',
        matches: 8,
        wins: 5,
        draws: 2,
        losses: 1,
        goalsFor: 13,
        goalsAgainst: 5,
      },
      {
        id: 'petkovic-amicaux',
        competition: 'Matchs amicaux',
        matches: 6,
        wins: 3,
        draws: 2,
        losses: 1,
        goalsFor: 8,
        goalsAgainst: 6,
      },
    ],
    milestones: [
      {
        id: 'petkovic-first-win',
        date: '2024-03-26',
        label: 'Première victoire officielle',
        value: 'Algérie 3-0 Afrique du Sud',
      },
      {
        id: 'petkovic-run',
        label: 'Série d’invincibilité',
        value: '9 matchs sans défaite',
      },
      {
        id: 'petkovic-biggest-win',
        label: 'Plus large succès',
        value: '5-0 en amical',
      },
    ],
    staff: ['Adjoint tactique', 'Préparateur physique', 'Analyste vidéo', 'Coach des gardiens'],
    futureDataPlaceholders: [
      { label: 'xG créé / match', value: 'À connecter via data provider' },
      { label: 'PPDA défensif', value: 'À connecter via data provider' },
      { label: 'Moyenne d’âge XI type', value: 'À connecter via base joueurs' },
      { label: 'Indice de rotation', value: 'À connecter via feuille de match' },
    ],
  },
  {
    id: 'coach-belmadi',
    slug: 'djamel-belmadi',
    fullName: 'Djamel Belmadi',
    role: 'Ancien sélectionneur',
    nationality: 'Algérie',
    birthDate: '1976-03-25',
    birthPlace: 'Champigny-sur-Marne, France',
    preferredSystem: '4-2-3-1 / 4-3-3',
    badges: ['Champion d’Afrique', 'Cycle 2018-2024', 'Leadership vestiaire'],
    highlights: {
      trophies: 1,
      matchCount: 67,
      wins: 42,
      draws: 14,
      losses: 11,
      goalsFor: 117,
      goalsAgainst: 46,
      cleanSheets: 30,
      debutMatch: '2018-09-08',
      lastMatch: '2024-01-23',
    },
    biography:
      'Période marquée par la CAN 2019 et une longue série d’invincibilité. Projet de jeu basé sur la maîtrise technique, l’intensité et l’automatismes des couloirs.',
    careerPath: [
      {
        id: 'belmadi-en',
        teamName: 'Algérie (A)',
        role: 'Sélectionneur',
        startDate: '2018-08-01',
        endDate: '2024-01-24',
      },
      {
        id: 'belmadi-qatar',
        teamName: 'Qatar (A)',
        role: 'Sélectionneur',
        startDate: '2014-03-01',
        endDate: '2018-07-01',
      },
    ],
    competitionStats: [
      {
        id: 'belmadi-can',
        competition: 'CAN',
        matches: 15,
        wins: 10,
        draws: 3,
        losses: 2,
        goalsFor: 24,
        goalsAgainst: 9,
      },
      {
        id: 'belmadi-cdmq',
        competition: 'Éliminatoires Mondial',
        matches: 18,
        wins: 12,
        draws: 4,
        losses: 2,
        goalsFor: 34,
        goalsAgainst: 12,
      },
      {
        id: 'belmadi-amicaux',
        competition: 'Matchs amicaux',
        matches: 20,
        wins: 13,
        draws: 4,
        losses: 3,
        goalsFor: 37,
        goalsAgainst: 18,
      },
    ],
    milestones: [
      {
        id: 'belmadi-can',
        date: '2019-07-19',
        label: 'Titre majeur',
        value: 'Champion d’Afrique 2019',
      },
      {
        id: 'belmadi-record',
        label: 'Série record',
        value: '35 matchs sans défaite',
      },
      {
        id: 'belmadi-goals',
        label: 'Moyenne buts marqués',
        value: '1.74 but/match',
      },
    ],
    staff: ['Adjoint principal', 'Préparateur performance', 'Cellule médicale', 'Analyste scout'],
    futureDataPlaceholders: [
      { label: 'Progression FIFA ranking', value: 'À brancher via API historique' },
      { label: 'Couloir offensif préféré', value: 'À dériver de tracking événements' },
      { label: 'Répartition buts sur CPA', value: 'À dériver des matchs détaillés' },
      { label: 'Temps moyen du 1er changement', value: 'À dériver des feuilles de match' },
    ],
  },
  {
    id: 'coach-renard',
    slug: 'herve-renard',
    fullName: 'Hervé Renard',
    role: 'Profil cible (bloc démo)',
    nationality: 'France',
    birthDate: '1968-09-30',
    birthPlace: 'Aix-les-Bains, France',
    preferredSystem: '4-4-2 / 4-2-3-1',
    badges: ['Bloc de projection UX', 'Données simulées', 'Comparatif possible'],
    highlights: {
      trophies: 2,
      matchCount: 18,
      wins: 11,
      draws: 3,
      losses: 4,
      goalsFor: 29,
      goalsAgainst: 16,
      cleanSheets: 7,
      debutMatch: '2011-02-09',
      lastMatch: '2011-11-15',
    },
    biography:
      'Fiche volontairement démonstrative pour visualiser une variante de rendu avec un profil international reconnu et des blocs de stats enrichis.',
    careerPath: [
      {
        id: 'renard-zambie',
        teamName: 'Zambie (A)',
        role: 'Sélectionneur',
        startDate: '2011-01-01',
        endDate: '2013-01-01',
      },
      {
        id: 'renard-maroc',
        teamName: 'Maroc (A)',
        role: 'Sélectionneur',
        startDate: '2016-02-01',
        endDate: '2019-07-01',
      },
    ],
    competitionStats: [
      {
        id: 'renard-can',
        competition: 'CAN',
        matches: 7,
        wins: 4,
        draws: 2,
        losses: 1,
        goalsFor: 9,
        goalsAgainst: 5,
      },
      {
        id: 'renard-amicaux',
        competition: 'Matchs amicaux',
        matches: 11,
        wins: 7,
        draws: 1,
        losses: 3,
        goalsFor: 20,
        goalsAgainst: 11,
      },
    ],
    milestones: [
      {
        id: 'renard-title',
        label: 'Titres continentaux',
        value: '2 (avec 2 sélections différentes)',
      },
      {
        id: 'renard-style',
        label: 'Indice style',
        value: 'Transition rapide + efficacité CPA',
      },
    ],
    staff: ['Adjoint défensif', 'Coach mental', 'Analyste data', 'Cellule scouting locale'],
    futureDataPlaceholders: [
      { label: 'Danger sur phases arrêtées', value: 'Bloc statique en attente des events' },
      { label: 'Distance moyenne bloc-équipe', value: 'Bloc statique en attente des tracking' },
      { label: 'Pression haute récupérations', value: 'Bloc statique en attente des events' },
      { label: 'Indice de stabilité XI', value: 'Bloc statique en attente des feuilles' },
    ],
  },
];
