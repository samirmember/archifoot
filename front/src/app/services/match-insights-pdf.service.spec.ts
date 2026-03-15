import {
  buildMatchInsightsReportHtml,
  buildMatchInsightsReportPayload,
  buildMatchInsightsScopeLabel,
  formatSignedValue,
  getMatchInsightsOfficialNote,
} from './match-insights-pdf.service';
import { MatchResultsSummary } from './result.service';

describe('match-insights-pdf helpers', () => {
  const summary: MatchResultsSummary = {
    totalMatches: 677,
    wins: 301,
    draws: 178,
    losses: 198,
    winRate: 44,
    goalsFor: 999,
    goalsAgainst: 712,
    goalDifference: 287,
    cleanSheets: 254,
    uniqueOpponents: 104,
    uniqueHostCountries: 77,
    officialMatches: 392,
    officialRate: 58,
  };

  it('builds the export payload with labeled filters', () => {
    const payload = buildMatchInsightsReportPayload({
      countryName: 'Ouganda',
      year: 2024,
      competitionName: 'Qualifications CAN',
      generatedAt: new Date('2026-03-15T09:00:00Z'),
      summary,
    });

    expect(payload.filters).toEqual([
      { label: 'Pays adversaire', value: 'Ouganda' },
      { label: 'Année', value: '2024' },
      { label: 'Type du match', value: 'Qualifications CAN' },
    ]);
    expect(payload.scopeLabel).toBe('Ouganda • 2024 • Qualifications CAN');
  });

  it('falls back to Toutes les rencontres when no filter is active', () => {
    const payload = buildMatchInsightsReportPayload({
      summary,
      generatedAt: new Date('2026-03-15T09:00:00Z'),
    });

    expect(payload.filters).toEqual([]);
    expect(payload.scopeLabel).toBe('Toutes les rencontres');
    expect(buildMatchInsightsScopeLabel([])).toBe('Toutes les rencontres');
  });

  it('renders the visible statistics and signed goal difference', () => {
    const payload = buildMatchInsightsReportPayload({
      countryName: 'Ouganda',
      year: 2024,
      competitionName: 'Qualifications CAN',
      scopeLabel: 'Qualifications CAN • 2024 • Ouganda',
      officialNote: getMatchInsightsOfficialNote(summary.totalMatches),
      generatedAt: new Date('2026-03-15T09:00:00Z'),
      summary,
    });

    const html = buildMatchInsightsReportHtml(payload, {
      baseUrl: 'https://archifoot.test/app/',
    });

    expect(html).toContain('Nombre de matchs');
    expect(html).toContain('677');
    expect(html).toContain('Buts marqués');
    expect(html).toContain('999');
    expect(html).toContain('Buts encaissés');
    expect(html).toContain('712');
    expect(html).toContain('Adversaires distincts');
    expect(html).toContain('104');
    expect(html).toContain('Pays hôtes');
    expect(html).toContain('77');
    expect(html).toContain('Cages inviolées');
    expect(html).toContain('254');
    expect(html).toContain('Diff. de buts');
    expect(html).toContain('+287');
    expect(formatSignedValue(summary.goalDifference)).toBe('+287');
  });

  it('renders the empty-state note when there is no match', () => {
    const payload = buildMatchInsightsReportPayload({
      generatedAt: new Date('2026-03-15T09:00:00Z'),
      summary: {
        ...summary,
        totalMatches: 0,
        wins: 0,
        draws: 0,
        losses: 0,
        goalsFor: 0,
        goalsAgainst: 0,
        goalDifference: 0,
        cleanSheets: 0,
        officialMatches: 0,
        officialRate: 0,
      },
    });

    const html = buildMatchInsightsReportHtml(payload, {
      baseUrl: 'https://archifoot.test/app/',
    });

    expect(payload.officialNote).toBe('Aucune rencontre ne correspond aux filtres actuels.');
    expect(html).toContain('Aucune rencontre ne correspond aux filtres actuels.');
  });

  it('includes the header, footer, disclaimer and absolute assets', () => {
    const payload = buildMatchInsightsReportPayload({
      countryName: 'Ouganda',
      year: 2024,
      competitionName: 'Qualifications CAN',
      generatedAt: new Date('2026-03-15T09:00:00Z'),
      summary,
    });

    const html = buildMatchInsightsReportHtml(payload, {
      baseUrl: 'https://archifoot.test/app/',
    });

    expect(html).toContain("Rapport de football basé sur des données d'archives nationales");
    expect(html).toMatch(/Généré le .* à \d{2}:\d{2}:\d{2}/);
    expect(html).toContain('Bilan statistique des rencontres');
    expect(html).toContain('Page 01 sur 01');
    expect(html).toContain('Disclaimer :');
    expect(html).toContain('https://archifoot.test/app/assets/img/logo.png');
  });
});
