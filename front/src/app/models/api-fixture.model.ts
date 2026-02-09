export interface ApiFixtureTeam {
  id: number;
  name: string | null;
  iso2: string | null;
}

export interface ApiFixtureStageCompetition {
  id: number | null;
  name: string | null;
}

export interface ApiCategory {
  id: number | null;
  name: string | null;
}

export interface ApiFixtureStageEdition {
  id: number | null;
  name: string | null;
  competition: ApiFixtureStageCompetition | null;
}

export interface ApiFixtureStage {
  id: number | null;
  name: string | null;
  edition: ApiFixtureStageEdition | null;
}

export interface ApiFixture {
  id?: number;
  externalMatchNo?: number | null;
  matchDate?: string | null;
  played?: boolean | null;
  isOfficial?: boolean | null;
  notes?: string | null;
  seasonName?: string | null;
  stages?: ApiFixtureStage[];
  teamA?: ApiFixtureTeam | null;
  teamB?: ApiFixtureTeam | null;
  scoreA?: number | null;
  scoreB?: number | null;
  countryStadiumName: string | null;
  cityName?: string | null;
  stadiumName?: string | null;
  competitions?: ApiFixtureStageCompetition[];
  categories: ApiCategory[];
}
