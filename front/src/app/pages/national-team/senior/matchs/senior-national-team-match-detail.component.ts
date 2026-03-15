import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatchScoresheetDetailsResponse, MatchLineupItem, MatchScoresheetParticipant } from 'src/app/models/match-scoresheet.model';
import { buildCompetitionLabels, MatchResult } from 'src/app/services/result.service';
import { MatchScoresheetService } from 'src/app/services/match-scoresheet.service';
import { FlagComponent } from 'src/app/layouts/flag/flag.component';
import { StaffCardComponent } from 'src/app/components/staff-card/staff-card.component';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-senior-national-team-match-detail',
  imports: [RouterLink, DatePipe, FlagComponent, StaffCardComponent],
  templateUrl: './senior-national-team-match-detail.component.html',
  styleUrl: './senior-national-team-match-detail.component.scss',
})
export class SeniorNationalTeamMatchDetailComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly service = inject(MatchScoresheetService);

  readonly isLoading = signal(true);
  readonly error = signal<string | null>(null);
  readonly details = signal<MatchScoresheetDetailsResponse | null>(null);

  readonly headerResult = computed<MatchResult>(() => {
    const fixture = this.details()?.fixture;

    return {
      fixtureId: fixture?.id ?? null,
      externalMatchNo: fixture?.externalMatchNo ?? null,
      countryA: fixture?.teamA?.teamName ?? '',
      countryB: fixture?.teamB?.teamName ?? '',
      countryCodeA: fixture?.teamA?.teamIso2 ?? '',
      countryCodeB: fixture?.teamB?.teamIso2 ?? '',
      editions: fixture?.stages?.map((stage) => stage.edition?.name).filter(this.isNonEmptyString) ?? null,
      stages: fixture?.stages?.map((stage) => stage.name).filter(this.isNonEmptyString) ?? null,
      competitions: fixture?.competitions ?? [],
      scoreA: fixture?.teamA?.score ?? null,
      scoreB: fixture?.teamB?.score ?? null,
      categoryA: fixture?.teamA?.categoryName ?? '',
      categoryB: fixture?.teamB?.categoryName ?? '',
      date: fixture?.matchDate ?? null,
      season: fixture?.seasonName ?? null,
      isOfficial: fixture?.isOfficial ?? null,
      played: fixture?.played ?? null,
      city: fixture?.cityName ?? null,
      stadium: fixture?.stadiumName ?? null,
      countryStadiumName: fixture?.countryStadiumName ?? null,
      notes: fixture?.notes ?? null,
      competitionLabel: fixture
        ? buildCompetitionLabels({
            competitions: fixture.competitions,
            stages: fixture.stages,
            externalMatchNo: fixture.externalMatchNo,
          }).join(' | ')
        : '',
    };
  });

  readonly competitionLabel = computed(() => this.headerResult().competitionLabel?.trim() ?? '');
  readonly locationLabel = computed(() =>
    [this.headerResult().stadium, this.headerResult().city, this.headerResult().countryStadiumName]
      .filter(this.isNonEmptyString)
      .join(', '),
  );

  readonly iso2A = computed(() => (this.headerResult().countryCodeA ?? '').toLowerCase());
  readonly iso2B = computed(() => (this.headerResult().countryCodeB ?? '').toLowerCase());

  readonly matchMeta = computed(() => {
    const fixture = this.details()?.fixture;
    return [
      { label: 'N° du match', value: fixture?.externalMatchNo ? String(fixture.externalMatchNo) : null },
      { label: 'Compétition', value: fixture?.competitions?.map((competition) => competition.name).filter(this.isNonEmptyString).join(' | ') || null },
      { label: 'Édition', value: fixture?.stages?.map((stage) => stage.edition?.name).filter(this.isNonEmptyString).join(' | ') || null },
      { label: 'Saison', value: fixture?.seasonName ?? null },
      { label: 'Stage', value: fixture?.stages?.map((stage) => stage.name).filter(this.isNonEmptyString).join(' | ') || null },
      { label: 'Stade', value: fixture?.stadiumName ?? null },
      { label: 'Ville - Stade', value: fixture?.cityName ?? null },
      { label: 'Pays - Stade', value: fixture?.countryStadiumName ?? null },
      {
        label: 'Date',
        value: fixture?.matchDate ? new Date(fixture.matchDate).toLocaleString('fr-FR') : null,
      },
      { label: 'Catégorie A', value: fixture?.teamA?.categoryName ?? null },
      { label: 'Pays A', value: fixture?.teamA?.teamName ?? null },
      {
        label: 'Score',
        value: fixture?.teamA?.score !== undefined && fixture?.teamA?.score !== null && fixture?.teamB?.score !== undefined && fixture?.teamB?.score !== null
          ? `${fixture.teamA.score} - ${fixture.teamB.score}`
          : null,
      },
      { label: 'Pays B', value: fixture?.teamB?.teamName ?? null },
      { label: 'Catégorie B', value: fixture?.teamB?.categoryName ?? null },
    ].filter((item) => this.isNonEmptyString(item.value));
  });

  readonly lineupAlgeria = computed(() => this.buildLineupForTeam(this.details()?.fixture?.teamA?.teamName));
  readonly lineupOpponent = computed(() => this.buildLineupForTeam(this.details()?.fixture?.teamB?.teamName));

  readonly isTeamAAlgeria = computed(() => {
    const teamA = this.details()?.fixture?.teamA;
    return this.isAlgeriaTeam(teamA?.teamIso2, teamA?.teamName);
  });

  readonly teamAChanges = computed(() => {
    const teamName = this.details()?.fixture?.teamA?.teamName;
    const fromApi = (this.details()?.substitutions ?? [])
      .filter((sub) => sub.teamName === teamName)
      .map((sub) => ({
        minute: sub.minute ? sub.minute + "'" : null,
        playerOut: sub.playerOutName || sub.playerOutText || '?',
        playerIn: sub.playerInName || sub.playerInText || '?',
      }));

    return fromApi;
  });

  readonly teamBChanges = computed(() => {
    const teamName = this.details()?.fixture?.teamB?.teamName;
    const fromApi = (this.details()?.substitutions ?? [])
      .filter((sub) => sub.teamName === teamName)
      .map((sub) => ({
        minute: sub.minute ? sub.minute + "'" : null,
        playerOut: sub.playerOutName || sub.playerOutText || '?',
        playerIn: sub.playerInName || sub.playerInText || '?',
      }));

    return fromApi;
  });

  readonly teamAChangesTitle = computed(() => this.isTeamAAlgeria() ? 'Les changements (Algérie)' : "Changements de l'équipe adverse");
  readonly teamBChangesTitle = computed(() => this.isTeamAAlgeria() ? "Changements de l'équipe adverse" : 'Les changements (Algérie)');

  readonly timelineEvents = computed(() => {
    const goals = this.details()?.goals ?? [];
    if (goals.length > 0) {
      return goals.map((goal) => ({
        minute: goal.minute ? goal.minute + "'" : null,
        tag: 'GOAL',
        title: goal.scorerName || goal.scorerText || 'But',
        subtitle: goal.teamName || goal.goalType || 'Action de jeu',
        score: '',
        type: 'goal',
      }));
    }

    return [];

    // return [
    //   { minute: 15, tag: 'GOAL', title: 'R. Mahrez', subtitle: 'Assisted by I. Bennacer', score: '1 - 0', type: 'goal' },
    //   { minute: 46, tag: 'SUBSTITUTION', title: 'Tactical Change (Algérie)', subtitle: 'A. Mandi ↔ Y. Atal', score: '', type: 'sub' },
    //   { minute: 60, tag: 'GOAL', title: 'I. Slimani', subtitle: 'Header from corner', score: '2 - 0', type: 'goal' },
    //   { minute: 82, tag: 'GOAL', title: 'V. Osimhen', subtitle: 'Penalty kick', score: '2 - 1', type: 'card' },
    // ];
  });

  readonly staffsByTeam = computed(() => {
    const details = this.details();
    if (!details) {
      return null;
    }
    const coach = details?.scoresheet?.coachName;
    const fixture = details.fixture;
    const teamA = fixture.teamA;
    const teamB = fixture.teamB;
    const fromApi = details?.staffs ?? [];

    const buildStaffsForTeam = (team: MatchScoresheetParticipant) => {
      const teamStaffs = fromApi.filter((staff) => staff.teamName === team.teamName);
      const coachStaff = teamStaffs.find((staff) => staff.roleCode === 'HEAD_COACH');
      const assistantStaffs = teamStaffs.filter((staff) => staff.roleCode === 'ASSISTANT_COACH');

      const coachItem = {
        role: 'Entraîneur principal',
        name: coachStaff?.personName,
        nation: coachStaff?.nationality,
        iso2: (coachStaff?.teamIso2 ?? '').toLowerCase(),
      };

      const assistantItems = assistantStaffs.map((staff, index) => ({
        role: `Assistant ${index + 1}`,
        name: staff.personName,
        nation: staff.nationality,
        iso2: (staff.teamIso2 ?? '').toLowerCase(),
      }));

      return [
        coachItem,
        ...assistantItems,
        {
          role: 'Capitaine',
          name: this.findCaptain(team.teamName),
          nation: team.teamIso2,
          iso2: team.teamIso2.toLowerCase(),
        },
      ];
    };

    const isTeamAAlgeria = this.isTeamAAlgeria();
    const algeriaTeam = isTeamAAlgeria ? teamA : teamB;
    const opponentTeam = isTeamAAlgeria ? teamB : teamA;

    const algeriaStaffs = buildStaffsForTeam(algeriaTeam);
    const opponentStaffs = buildStaffsForTeam(opponentTeam);

    return {
      teamA: {
        name: teamA.teamName,
        staffs: isTeamAAlgeria ? algeriaStaffs : opponentStaffs,
      },
      teamB: {
        name: teamB.teamName,
        staffs: isTeamAAlgeria ? opponentStaffs : algeriaStaffs,
      },
    };
  });

  readonly officials = computed(() => {
    return (this.details()?.officials ?? [])
      .map((o) => ({
        role: o.role || 'Officiel',
        name: o.personName || o.nameText || null,
        nationality: o.nationality || null,
      }))
      .filter((official) => this.isNonEmptyString(official.name));
  });

  ngOnInit(): void {
    const externalMatchNoParam = this.route.snapshot.paramMap.get('externalMatchNo');
    const externalMatchNo = externalMatchNoParam ? Number(externalMatchNoParam) : NaN;

    if (Number.isNaN(externalMatchNo) || externalMatchNo <= 0) {
      this.error.set('Identifiant de match invalide.');
      this.isLoading.set(false);
      return;
    }

    this.service.getMatchScoresheetDetails(externalMatchNo).subscribe({
      next: (response) => {
        this.details.set(response);
        this.isLoading.set(false);
      },
      error: () => {
        this.error.set('Impossible de charger la fiche technique.');
        this.isLoading.set(false);
      },
    });
  }

  private buildLineupForTeam(teamName: string | null | undefined): { title: string; iso2: string; players: Array<{ role: string; name: string; number: string; captain?: boolean | null }> } {
    const fixture = this.details()?.fixture;
    const normalizedTeamName = (teamName ?? '').toLowerCase();
    const teamIso2 = fixture?.teamA?.teamName?.toLowerCase() === normalizedTeamName
      ? fixture.teamA?.teamIso2
      : fixture?.teamB?.teamName?.toLowerCase() === normalizedTeamName
        ? fixture.teamB?.teamIso2
        : null;

    const players = (this.details()?.lineups ?? [])
      .filter((p) => p.teamName === teamName)
      .sort((a, b) => (a.sortOrder ?? 999) - (b.sortOrder ?? 999));

    return {
      title: `${teamName ?? 'Équipe'}`,
      iso2: (teamIso2 ?? '').toLowerCase(),
      players: players.map((p: MatchLineupItem) => ({
        role: this.toRoleTag(p.positionName),
        name: p.playerName || p.playerNameText || 'Joueur',
        number: `#${p.shirtNumber ?? '?'}`,
        captain: p.isCaptain,
      })),
    };
  }

  private toRoleTag(positionName: string | null): string {
    const v = (positionName ?? '').toLowerCase();
    if (v.includes('goal')) return 'GK';
    if (v.includes('def')) return 'DF';
    if (v.includes('mid')) return 'MF';
    if (v.includes('for') || v.includes('att')) return 'FW';
    return 'PL';
  }

  private findCaptain(teamName: string | null | undefined): string | null {
    const captain = (this.details()?.lineups ?? []).find((p) => p.teamName === teamName && p.isCaptain);
    return captain?.playerName || captain?.playerNameText || null;
  }

  private isAlgeriaTeam(teamIso2: string | null | undefined, teamName: string | null | undefined): boolean {
    const iso2 = (teamIso2 ?? '').toLowerCase();
    const name = (teamName ?? '').toLowerCase();
    return iso2 === 'dz' || name.includes('algérie') || name.includes('algerie');
  }

  private isNonEmptyString(value: string | null | undefined): value is string {
    return typeof value === 'string' && value.trim().length > 0;
  }
}
