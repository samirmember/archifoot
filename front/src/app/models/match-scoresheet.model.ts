export interface MatchScoresheetParticipant {
  role: string | null;
  score: number | null;
  teamId: number | null;
  teamName: string | null;
  teamIso2: string | null;
  categoryName: string | null;
}

export interface MatchScoresheetFixture {
  id: number;
  externalMatchNo: number | null;
  matchDate: string | null;
  played: boolean | null;
  isOfficial: boolean | null;
  notes: string | null;
  seasonName: string | null;
  cityName: string | null;
  stadiumName: string | null;
  countryStadiumName: string | null;
  teamA: MatchScoresheetParticipant | null;
  teamB: MatchScoresheetParticipant | null;
}

export interface MatchScoresheet {
  id: number;
  attendance: number | null;
  fixedTime: string | null;
  kickoffTime: string | null;
  halfTime: string | null;
  secondHalfStart: string | null;
  fullTime: string | null;
  stoppageTime: string | null;
  matchStopTime: string | null;
  reservations: string | null;
  report: string | null;
  signedPlace: string | null;
  signedOn: string | null;
  status: string | null;
  coachName: string | null;
}

export interface MatchLineupItem {
  id: number;
  lineupRole: string | null;
  shirtNumber: number | null;
  sortOrder: number | null;
  isCaptain: boolean | null;
  playerNameText: string | null;
  teamName: string | null;
  positionName: string | null;
  playerName: string | null;
}

export interface MatchSubstitution {
  id: number;
  minute: string | null;
  playerOutText: string | null;
  playerInText: string | null;
  teamName: string | null;
  playerOutName: string | null;
  playerInName: string | null;
}

export interface MatchOfficial {
  id: number;
  role: string | null;
  nameText: string | null;
  personName: string | null;
}

export interface MatchGoal {
  id: number;
  minute: string | null;
  goalType: string | null;
  scorerText: string | null;
  teamName: string | null;
  scorerName: string | null;
}

export interface MatchScoresheetDetailsResponse {
  fixture: MatchScoresheetFixture;
  scoresheet: MatchScoresheet | null;
  lineups: MatchLineupItem[];
  substitutions: MatchSubstitution[];
  officials: MatchOfficial[];
  goals: MatchGoal[];
}
