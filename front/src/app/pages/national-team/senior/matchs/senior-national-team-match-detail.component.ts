import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ResultComponent } from 'src/app/components/result/result.component';
import { MatchResult } from 'src/app/services/result.service';

@Component({
  selector: 'app-senior-national-team-match-detail',
  imports: [RouterLink, ResultComponent],
  templateUrl: './senior-national-team-match-detail.component.html',
  styleUrl: './senior-national-team-match-detail.component.scss',
})
export class SeniorNationalTeamMatchDetailComponent {
  readonly dummyResult: MatchResult = {
    fixtureId: 678,
    externalMatchNo: 678,
    countryA: 'Algérie',
    countryB: 'Nigeria',
    countryCodeA: 'dz',
    countryCodeB: 'ng',
    editions: ['CAN 2025'],
    stages: ['1/4 de finale'],
    competitions: [],
    scoreA: 0,
    scoreB: 2,
    categoryA: 'Senior',
    categoryB: 'Senior',
    date: '2026-01-10T20:00:00Z',
    season: '2025/2026',
    isOfficial: true,
    played: true,
    city: 'Marrakech',
    stadium: 'Grand Stade',
    countryStadiumName: 'Maroc',
    notes: 'Match intense avec domination adverse en seconde période.',
    competitionLabel: '1/4 de finale CAN 2025 au Maroc',
  };

  readonly matchMeta = [
    { label: 'N° du match', value: '678' },
    { label: 'Compétition', value: 'CAN' },
    { label: 'Édition', value: 'CAN 2025 au Maroc' },
    { label: 'Saison', value: '2025/2026' },
    { label: 'Stage', value: '1/4 de finale' },
    { label: 'Stade', value: 'Grand Stade' },
    { label: 'Ville - Stade', value: 'Marrakech' },
    { label: 'Pays - Stade', value: 'Maroc' },
    { label: 'Date', value: '10 janv. 2026 · 20:00' },
    { label: 'Catégorie A', value: 'Senior' },
    { label: 'Pays A', value: 'Algérie' },
    { label: 'Score', value: '0 - 2' },
    { label: 'Pays B', value: 'Nigeria' },
    { label: 'Catégorie B', value: 'Senior' },
  ];

  readonly timelineEvents = [
    {
      minute: 15,
      tag: 'GOAL',
      title: 'R. Mahrez',
      subtitle: 'Assisted by I. Bennacer',
      score: '1 - 0',
      type: 'goal',
    },
    {
      minute: 46,
      tag: 'SUBSTITUTION',
      title: 'Tactical Change (Algérie)',
      subtitle: 'A. Mandi ↔ Y. Atal',
      score: '',
      type: 'sub',
    },
    {
      minute: 60,
      tag: 'GOAL',
      title: 'I. Slimani',
      subtitle: 'Header from corner',
      score: '2 - 0',
      type: 'goal',
    },
    {
      minute: 82,
      tag: 'GOAL',
      title: 'V. Osimhen',
      subtitle: 'Penalty kick',
      score: '2 - 1',
      type: 'card',
    },
  ] as const;

  readonly lineupAlgeria = {
    title: 'Algérie (4-3-3)',
    players: [
      { role: 'GK', name: 'Anthony Mandrea', number: '#1' },
      { role: 'DF', name: 'Youcef Atal', number: '#20' },
      { role: 'DF', name: 'Ramy Bensebaini', number: '#21' },
      { role: 'MF', name: 'Ismaël Bennacer', number: '#22' },
      { role: 'MF', name: 'Riyad Mahrez (C)', number: '#7', captain: true },
      { role: 'FW', name: 'Islam Slimani', number: '#13' },
    ],
  };

  readonly lineupOpponent = {
    title: 'Nigeria (4-2-3-1)',
    players: [
      { role: 'GK', name: 'Stanley Nwabali', number: '#23' },
      { role: 'DF', name: 'Ola Aina', number: '#2' },
      { role: 'DF', name: 'William Troost-Ekong', number: '#5' },
      { role: 'MF', name: 'Alex Iwobi', number: '#17' },
      { role: 'MF', name: 'Ademola Lookman', number: '#18' },
      { role: 'FW', name: 'Victor Osimhen', number: '#9' },
    ],
  };

  readonly notes = [
    'Weather: 24°C, ciel clair, faible humidité (40%).',
    'Observations tactiques: l’adversaire a dominé la possession (62%).',
    'Indice qualité match: 8.8/10 · Intensité élevée.',
  ];

  readonly staffs = [
    { role: 'Entraîneur', name: 'Vladimir Petković', nation: 'Suisse', flag: '🇨🇭' },
    { role: 'Entraîneur adverse', name: 'José Peseiro', nation: 'Portugal', flag: '🇵🇹' },
    {
      role: 'entraineur_adv_assistant',
      name: 'Vladimir Petković',
      nation: 'Afrique du Sud',
      flag: '🇿🇦',
    },
    { role: 'assistant1_dz', name: 'Madjid Bougherra', nation: 'Algérie', flag: '🇩🇿' },
    { role: 'assistant2_dz', name: 'Nabil Neghiz', nation: 'Algérie', flag: '🇩🇿' },
    { role: 'capitaine_dz', name: 'Riyad Mahrez', nation: 'Algérie', flag: '🇩🇿' },
    { role: 'capitaine_adv', name: 'William Troost-Ekong', nation: 'Nigeria', flag: '🇳🇬' },
  ];

  readonly officials = [
    { role: 'arbitre_principal', name: 'Victor Gomes', nationality: 'Afrique du Sud' },
    { role: 'Arbitre assistant 1', name: 'Nomer Roran', nationality: 'France' },
    { role: 'Arbitre assistant 2', name: 'Goono Soime', nationality: 'France' },
    { role: '4ème Arbitre', name: 'John Vollan', nationality: 'Nigeria' },
    { role: 'nationalite_arbitre', name: 'Victor Gomes', nationality: 'Afrique du Sud' },
    {
      role: 'Nationalité arbitre assistant 1',
      name: 'Nomer Roran',
      nationality: 'France',
    },
    {
      role: 'Nationalité arbitre assistant 2',
      name: 'Goono Soime',
      nationality: 'France',
    },
    { role: 'Nationalité 4ème arbitre', name: 'John Vollan', nationality: 'Nigeria' },
  ];

  readonly algeriaChanges = [
    "46' A. Mandi remplace Y. Atal",
    "67' S. Benrahma remplace H. Aouar",
    "75' A. Gouiri remplace B. Bounedjah",
  ];

  readonly opponentChanges = [
    "58' B. Onyeka remplace A. Iwobi",
    "70' M. Simon remplace A. Lookman",
    "84' C. Dessers remplace V. Osimhen",
  ];
}
