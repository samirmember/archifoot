import { DatePipe } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatchScoresheetDetailsResponse, MatchLineupItem } from 'src/app/models/match-scoresheet.model';
import { MatchResult } from 'src/app/services/result.service';
import { MatchScoresheetService } from 'src/app/services/match-scoresheet.service';
import { FlagComponent } from 'src/app/layouts/flag/flag.component';
import { StaffCardComponent } from 'src/app/components/staff-card/staff-card.component';

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

  // Fallbacks statiques pour les champs absents de l'API scoresheet actuelle
  readonly fallbackCompetition = 'CAN';
  readonly fallbackEdition = 'CAN 2025 au Maroc';
  readonly fallbackStage = '1/4 de finale';
  readonly fallbackNotes = [
    'Weather: 24°C, ciel clair, faible humidité (40%).',
    'Observations tactiques: l’adversaire a dominé la possession (62%).',
    'Indice qualité match: 8.8/10 · Intensité élevée.',
  ];

  readonly headerResult = computed<MatchResult>(() => {
    const data = this.details();
    const fixture = data?.fixture;
    return {
      fixtureId: fixture?.id ?? 678,
      externalMatchNo: fixture?.externalMatchNo ?? 678,
      countryA: fixture?.teamA?.teamName ?? 'Algérie',
      countryB: fixture?.teamB?.teamName ?? 'Nigeria',
      countryCodeA: fixture?.teamA?.teamIso2 ?? 'dz',
      countryCodeB: fixture?.teamB?.teamIso2 ?? 'ng',
      editions: [this.fallbackEdition],
      stages: [this.fallbackStage],
      competitions: [],
      scoreA: fixture?.teamA?.score ?? 0,
      scoreB: fixture?.teamB?.score ?? 2,
      categoryA: fixture?.teamA?.categoryName ?? 'Senior',
      categoryB: fixture?.teamB?.categoryName ?? 'Senior',
      date: fixture?.matchDate ?? '2026-01-10T20:00:00Z',
      season: fixture?.seasonName ?? '2025/2026',
      isOfficial: fixture?.isOfficial ?? true,
      played: fixture?.played ?? true,
      city: fixture?.cityName ?? 'Marrakech',
      stadium: fixture?.stadiumName ?? 'Grand Stade',
      countryStadiumName: fixture?.countryStadiumName ?? 'Maroc',
      notes: fixture?.notes ?? null,
      competitionLabel: `${this.fallbackStage} ${this.fallbackCompetition} ${this.fallbackEdition}`,
    };
  });


  readonly iso2A = computed(() => (this.headerResult().countryCodeA ?? '').toLowerCase());
  readonly iso2B = computed(() => (this.headerResult().countryCodeB ?? '').toLowerCase());

  readonly matchMeta = computed(() => {
    const fixture = this.details()?.fixture;
    return [
      { label: 'N° du match', value: String(fixture?.externalMatchNo ?? 678) },
      { label: 'Compétition', value: this.fallbackCompetition },
      { label: 'Édition', value: this.fallbackEdition },
      { label: 'Saison', value: fixture?.seasonName ?? null },
      { label: 'Stage', value: this.fallbackStage },
      { label: 'Stade', value: fixture?.stadiumName ?? null },
      { label: 'Ville - Stade', value: fixture?.cityName ?? null },
      { label: 'Pays - Stade', value: fixture?.countryStadiumName ?? null },
      {
        label: 'Date',
        value: fixture?.matchDate ? new Date(fixture.matchDate).toLocaleString('fr-FR') : null,
      },
      { label: 'Catégorie A', value: fixture?.teamA?.categoryName ?? null },
      { label: 'Pays A', value: fixture?.teamA?.teamName ?? null },
      { label: 'Score', value: `${fixture?.teamA?.score ?? null} - ${fixture?.teamB?.score ?? null}` },
      { label: 'Pays B', value: fixture?.teamB?.teamName ?? null },
      { label: 'Catégorie B', value: fixture?.teamB?.categoryName ?? null },
    ];
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
        minute: Number(goal.minute ?? 0),
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
    const coach = this.details()?.scoresheet?.coachName;
    const fixture = this.details()?.fixture;
    const teamA = fixture?.teamA;
    const teamB = fixture?.teamB;
    const teamAName = teamA?.teamName || 'Algérie';
    const teamBName = teamB?.teamName || 'Adversaire';

    const isTeamAAlgeria = this.isTeamAAlgeria();

    const algeriaStaffs = [
      { role: 'Entraîneur', name: coach || 'Vladimir Petković', nation: 'Algérie', iso2: 'dz' },
      { role: 'Assistant 1', name: 'Madjid Bougherra', nation: 'Algérie', iso2: 'dz' },
      { role: 'Assistant 2', name: 'Nabil Neghiz', nation: 'Algérie', iso2: 'dz' },
      {
        role: 'Capitaine',
        name: this.findCaptain(isTeamAAlgeria ? teamAName : teamBName) || 'Riyad Mahrez',
        nation: 'Algérie',
        iso2: 'dz',
      },
    ];

    const opponentName = isTeamAAlgeria ? teamBName : teamAName;
    const opponentIso2 = ((isTeamAAlgeria ? teamB?.teamIso2 : teamA?.teamIso2) ?? '').toLowerCase() || 'ng';
    const opponentStaffs = [
      { role: 'Entraîneur', name: 'José Peseiro', nation: 'Portugal', iso2: 'pt' },
      { role: 'Assistant', name: 'Assistant adverse', nation: opponentName, iso2: opponentIso2 },
      {
        role: 'Capitaine',
        name: this.findCaptain(opponentName) || 'William Troost-Ekong',
        nation: opponentName,
        iso2: opponentIso2,
      },
    ];

    return {
      teamA: {
        name: teamAName,
        staffs: isTeamAAlgeria ? algeriaStaffs : opponentStaffs,
      },
      teamB: {
        name: teamBName,
        staffs: isTeamAAlgeria ? opponentStaffs : algeriaStaffs,
      },
    };
  });

  readonly officials = computed(() => {
    const fromApi = this.details()?.officials ?? [];
    if (fromApi.length > 0) {
      return fromApi.map((o) => ({
        role: o.role || 'Officiel',
        name: o.personName || o.nameText || 'N/A',
        nationality: o.nationality || 'N/A',
      }));
    }

    return [
      { role: 'arbitre_principal', name: 'Victor Gomes', nationality: 'Afrique du Sud' },
      { role: 'Arbitre assistant 1', name: 'Nomer Roran', nationality: 'France' },
      { role: 'Arbitre assistant 2', name: 'Goono Soime', nationality: 'France' },
      { role: '4ème Arbitre', name: 'John Vollan', nationality: 'Nigeria' },
    ];
  });

  readonly notes = computed(() => {
    const report = this.details()?.scoresheet?.report;
    const reservations = this.details()?.scoresheet?.reservations;
    const notes = this.details()?.fixture?.notes;
    const dynamic = [notes, report, reservations].filter((v): v is string => Boolean(v && v.trim()));
    return dynamic.length > 0 ? dynamic : this.fallbackNotes;
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
        this.error.set('Impossible de charger la fiche technique. Affichage des données de démonstration.');
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
}
