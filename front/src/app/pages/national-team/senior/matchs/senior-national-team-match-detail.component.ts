import { DatePipe } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ResultComponent } from 'src/app/components/result/result.component';
import { MatchScoresheetDetailsResponse, MatchLineupItem } from 'src/app/models/match-scoresheet.model';
import { MatchResult } from 'src/app/services/result.service';
import { MatchScoresheetService } from 'src/app/services/match-scoresheet.service';

@Component({
  selector: 'app-senior-national-team-match-detail',
  imports: [RouterLink, ResultComponent, DatePipe],
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
      notes: fixture?.notes ?? 'Match intense avec domination adverse en seconde période.',
      competitionLabel: `${this.fallbackStage} ${this.fallbackCompetition} ${this.fallbackEdition}`,
    };
  });

  readonly matchMeta = computed(() => {
    const fixture = this.details()?.fixture;
    return [
      { label: 'N° du match', value: String(fixture?.externalMatchNo ?? 678) },
      { label: 'Compétition', value: this.fallbackCompetition },
      { label: 'Édition', value: this.fallbackEdition },
      { label: 'Saison', value: fixture?.seasonName ?? '2025/2026' },
      { label: 'Stage', value: this.fallbackStage },
      { label: 'Stade', value: fixture?.stadiumName ?? 'Grand Stade' },
      { label: 'Ville - Stade', value: fixture?.cityName ?? 'Marrakech' },
      { label: 'Pays - Stade', value: fixture?.countryStadiumName ?? 'Maroc' },
      {
        label: 'Date',
        value: fixture?.matchDate ? new Date(fixture.matchDate).toLocaleString('fr-FR') : '10 janv. 2026 · 20:00',
      },
      { label: 'Catégorie A', value: fixture?.teamA?.categoryName ?? 'Senior' },
      { label: 'Pays A', value: fixture?.teamA?.teamName ?? 'Algérie' },
      { label: 'Score', value: `${fixture?.teamA?.score ?? 0} - ${fixture?.teamB?.score ?? 2}` },
      { label: 'Pays B', value: fixture?.teamB?.teamName ?? 'Nigeria' },
      { label: 'Catégorie B', value: fixture?.teamB?.categoryName ?? 'Senior' },
    ];
  });

  readonly lineupAlgeria = computed(() => this.buildLineupForTeam(this.details()?.fixture?.teamA?.teamName));
  readonly lineupOpponent = computed(() => this.buildLineupForTeam(this.details()?.fixture?.teamB?.teamName));

  readonly algeriaChanges = computed(() => {
    const teamName = this.details()?.fixture?.teamA?.teamName;
    const fromApi = (this.details()?.substitutions ?? [])
      .filter((sub) => sub.teamName === teamName)
      .map((sub) => `${sub.minute ?? '?'}' ${sub.playerOutName || sub.playerOutText || '?'} ↔ ${sub.playerInName || sub.playerInText || '?'}`);

    return fromApi.length > 0
      ? fromApi
      : ["46' A. Mandi remplace Y. Atal", "67' S. Benrahma remplace H. Aouar", "75' A. Gouiri remplace B. Bounedjah"];
  });

  readonly opponentChanges = computed(() => {
    const teamName = this.details()?.fixture?.teamB?.teamName;
    const fromApi = (this.details()?.substitutions ?? [])
      .filter((sub) => sub.teamName === teamName)
      .map((sub) => `${sub.minute ?? '?'}' ${sub.playerOutName || sub.playerOutText || '?'} ↔ ${sub.playerInName || sub.playerInText || '?'}`);

    return fromApi.length > 0
      ? fromApi
      : ["58' B. Onyeka remplace A. Iwobi", "70' M. Simon remplace A. Lookman", "84' C. Dessers remplace V. Osimhen"];
  });

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

    return [
      { minute: 15, tag: 'GOAL', title: 'R. Mahrez', subtitle: 'Assisted by I. Bennacer', score: '1 - 0', type: 'goal' },
      { minute: 46, tag: 'SUBSTITUTION', title: 'Tactical Change (Algérie)', subtitle: 'A. Mandi ↔ Y. Atal', score: '', type: 'sub' },
      { minute: 60, tag: 'GOAL', title: 'I. Slimani', subtitle: 'Header from corner', score: '2 - 0', type: 'goal' },
      { minute: 82, tag: 'GOAL', title: 'V. Osimhen', subtitle: 'Penalty kick', score: '2 - 1', type: 'card' },
    ];
  });

  readonly staffs = computed(() => {
    const coach = this.details()?.scoresheet?.coachName;
    return [
      { role: 'Entraîneur', name: coach || 'Vladimir Petković', nation: 'Algérie', flag: '🇩🇿' },
      { role: 'Entraîneur adverse', name: 'José Peseiro', nation: 'Portugal', flag: '🇵🇹' },
      { role: 'entraineur_adv_assistant', name: 'Assistant adverse', nation: 'Nigeria', flag: '🇳🇬' },
      { role: 'assistant1_dz', name: 'Madjid Bougherra', nation: 'Algérie', flag: '🇩🇿' },
      { role: 'assistant2_dz', name: 'Nabil Neghiz', nation: 'Algérie', flag: '🇩🇿' },
      { role: 'capitaine_dz', name: this.findCaptain(this.details()?.fixture?.teamA?.teamName) || 'Riyad Mahrez', nation: 'Algérie', flag: '🇩🇿' },
      { role: 'capitaine_adv', name: this.findCaptain(this.details()?.fixture?.teamB?.teamName) || 'William Troost-Ekong', nation: 'Nigeria', flag: '🇳🇬' },
    ];
  });

  readonly officials = computed(() => {
    const fromApi = this.details()?.officials ?? [];
    if (fromApi.length > 0) {
      return fromApi.map((o) => ({
        role: o.role || 'Officiel',
        name: o.personName || o.nameText || 'N/A',
        nationality: 'N/A', // non fourni dans cette API
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

  private buildLineupForTeam(teamName: string | null | undefined): { title: string; players: Array<{ role: string; name: string; number: string; captain?: boolean | null }> } {
    const players = (this.details()?.lineups ?? [])
      .filter((p) => p.teamName === teamName)
      .sort((a, b) => (a.sortOrder ?? 999) - (b.sortOrder ?? 999));

    if (players.length === 0) {
      return {
        title: `${teamName ?? 'Équipe'} (4-3-3)`,
        players: [
          { role: 'GK', name: 'Anthony Mandrea', number: '#1' },
          { role: 'DF', name: 'Youcef Atal', number: '#20' },
          { role: 'DF', name: 'Ramy Bensebaini', number: '#21' },
          { role: 'MF', name: 'Ismaël Bennacer', number: '#22' },
          { role: 'MF', name: 'Riyad Mahrez (C)', number: '#7', captain: true },
          { role: 'FW', name: 'Islam Slimani', number: '#13' },
        ],
      };
    }

    return {
      title: `${teamName ?? 'Équipe'} (lineup)`,
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
}
