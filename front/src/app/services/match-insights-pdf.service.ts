import { DOCUMENT } from '@angular/common';
import { Inject, Injectable } from '@angular/core';
import { MatchResultsSummary } from './result.service';

export interface MatchInsightsReportFilter {
  label: string;
  value: string;
}

export interface MatchInsightsReportPayload {
  generatedAt: Date;
  filters: MatchInsightsReportFilter[];
  scopeLabel: string;
  officialNote: string;
  summary: MatchResultsSummary;
}

export interface BuildMatchInsightsReportPayloadInput {
  generatedAt?: Date;
  countryName?: string | null;
  year?: number | null;
  competitionName?: string | null;
  scopeLabel?: string | null;
  officialNote?: string | null;
  summary: MatchResultsSummary;
}

interface MatchInsightsReportAssets {
  logoUrl: string;
  spaceCssUrl: string;
  urbanistCssUrl: string;
}

interface BuildMatchInsightsReportHtmlOptions {
  baseUrl: string;
}

interface MatchInsightsPrintTarget {
  printWindow: Window;
  cleanup: () => void;
}

const DEFAULT_SCOPE_LABEL = 'Toutes les rencontres';
const DEFAULT_EMPTY_NOTE = 'Aucune rencontre ne correspond aux filtres actuels.';
const DEFAULT_OFFICIAL_NOTE = "Part des matchs officiels dans l'échantillon actuellement affiché.";

export function buildMatchInsightsFilters(
  input: Pick<BuildMatchInsightsReportPayloadInput, 'countryName' | 'year' | 'competitionName'>,
): MatchInsightsReportFilter[] {
  const filters: MatchInsightsReportFilter[] = [];

  const countryName = normalizeText(input.countryName);
  if (countryName) {
    filters.push({ label: 'Pays adversaire', value: countryName });
  }

  if (typeof input.year === 'number' && Number.isFinite(input.year)) {
    filters.push({ label: 'Année', value: String(input.year) });
  }

  const competitionName = normalizeText(input.competitionName);
  if (competitionName) {
    filters.push({ label: 'Type du match', value: competitionName });
  }

  return filters;
}

export function buildMatchInsightsScopeLabel(filters: MatchInsightsReportFilter[]): string {
  return filters.length > 0
    ? filters.map((filter) => filter.value).join(' • ')
    : DEFAULT_SCOPE_LABEL;
}

export function getMatchInsightsOfficialNote(totalMatches: number): string {
  return totalMatches === 0 ? DEFAULT_EMPTY_NOTE : DEFAULT_OFFICIAL_NOTE;
}

export function formatSignedValue(value: number): string {
  return value > 0 ? `+${value}` : `${value}`;
}

export function buildMatchInsightsReportPayload(
  input: BuildMatchInsightsReportPayloadInput,
): MatchInsightsReportPayload {
  const filters = buildMatchInsightsFilters(input);

  return {
    generatedAt: input.generatedAt ?? new Date(),
    filters,
    scopeLabel: normalizeText(input.scopeLabel) ?? buildMatchInsightsScopeLabel(filters),
    officialNote:
      normalizeText(input.officialNote) ?? getMatchInsightsOfficialNote(input.summary.totalMatches),
    summary: input.summary,
  };
}

export function buildMatchInsightsReportHtml(
  payload: MatchInsightsReportPayload,
  options: BuildMatchInsightsReportHtmlOptions,
): string {
  const assets = resolveAssets(options.baseUrl);
  const generatedDateLabel = formatGeneratedDate(payload.generatedAt);
  const reportYear = payload.generatedAt.getFullYear();
  const filtersHtml =
    payload.filters.length > 0
      ? payload.filters
          .map(
            (filter) =>
              `<span class="report-badge"><strong>${escapeHtml(filter.label)}:</strong> ${escapeHtml(filter.value)}</span>`,
          )
          .join('')
      : `<span class="report-badge report-badge--default">${escapeHtml(payload.scopeLabel)}</span>`;

  const summary = payload.summary;
  const totalMatches = summary.totalMatches;
  const goalDifference = formatSignedValue(summary.goalDifference);
  const officialProgressWidth = clampPercentage(summary.officialRate);
  const officialMatchesLabel =
    totalMatches === 0
      ? 'Aucun match officiel dans cet échantillon.'
      : `${summary.officialMatches} match${summary.officialMatches === 1 ? '' : 's'} officiel${summary.officialMatches === 1 ? '' : 's'} sur ${totalMatches}.`;

  return `<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <title>Rapport ArchiFoot - Analyses des matchs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="${escapeHtml(assets.spaceCssUrl)}" />
    <link rel="stylesheet" href="${escapeHtml(assets.urbanistCssUrl)}" />
    <style>
      @page {
        size: A4 portrait;
        margin: 12mm;
      }

      :root {
        color-scheme: light;
        --navy: #0d2348;
        --navy-soft: #1a2945;
        --green: #056e37;
        --green-soft: #eef8f1;
        --green-border: #b9e6ca;
        --gray-50: #f6f8fb;
        --gray-100: #eef2f7;
        --gray-200: #dde6f1;
        --gray-400: #8fa1bb;
        --gray-600: #506886;
        --danger-soft: #fff2f1;
        --danger-border: #f3d4cf;
        --danger-text: #d64235;
        --ink: #102446;
      }

      * {
        box-sizing: border-box;
      }

      html,
      body {
        margin: 0;
        padding: 0;
        background: #ffffff;
        color: var(--ink);
      }

      body {
        font-family: 'Urbanist', 'Segoe UI', sans-serif;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .report-shell {
        min-height: 100%;
      }

      .report-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        padding-bottom: 14px;
        border-bottom: 2px solid #0b7b49;
      }

      .brand {
        display: flex;
        align-items: center;
        gap: 14px;
      }

      .brand img {
        display: block;
        width: 172px;
        height: auto;
      }

      .brand-copy {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }

      .brand-copy strong {
        font-family: 'Space Grotesk', 'Arial Narrow', sans-serif;
        font-size: 15px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--green);
      }

      .brand-copy span {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #5e7594;
      }

      .report-meta {
        text-align: right;
        max-width: 280px;
      }

      .report-meta strong {
        display: block;
        font-family: 'Space Grotesk', 'Arial Narrow', sans-serif;
        font-size: 15px;
        line-height: 1.2;
        text-transform: uppercase;
        color: var(--navy);
      }

      .report-meta span {
        display: block;
        margin-top: 6px;
        color: #6d82a0;
        font-size: 12px;
      }

      .report-title {
        margin: 24px 0 8px;
        padding-left: 14px;
        border-left: 4px solid var(--green);
      }

      .report-title h1 {
        margin: 0;
        font-family: 'Space Grotesk', 'Arial Narrow', sans-serif;
        font-size: 27px;
        line-height: 1.08;
        text-transform: uppercase;
        color: var(--navy);
      }

      .report-title p {
        margin: 8px 0 0;
        font-size: 14px;
        font-style: italic;
        color: #536a88;
      }

      .report-filters {
        margin-top: 18px;
        padding: 14px 16px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        border: 1px solid var(--green-border);
        border-radius: 16px;
        background: linear-gradient(135deg, #f6fffa 0%, #edf7f1 100%);
      }

      .report-filters__label {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6f87a6;
      }

      .report-badge {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        border: 1px solid #a9dcbf;
        background: #ffffff;
        font-size: 12px;
        font-weight: 700;
        color: #0f5d37;
      }

      .report-badge strong {
        margin-right: 4px;
      }

      .report-badge--default {
        border-color: var(--gray-200);
        color: #4b6282;
      }

      .primary-grid {
        margin-top: 18px;
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
      }

      .primary-card {
        min-height: 90px;
        padding: 14px 16px;
        border-radius: 18px;
        border: 1px solid #d9e4ef;
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(16, 36, 70, 0.06);
      }

      .primary-card--highlight {
        background: var(--green);
        border-color: var(--green);
      }

      .primary-card--danger {
        background: var(--danger-soft);
        border-color: var(--danger-border);
      }

      .primary-card--neutral {
        background: var(--gray-50);
      }

      .primary-card--navy {
        background: var(--navy-soft);
        border-color: var(--navy-soft);
      }

      .primary-card__label {
        display: block;
        margin-bottom: 12px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6d82a0;
      }

      .primary-card__value {
        margin: 0;
        font-family: 'Space Grotesk', 'Arial Narrow', sans-serif;
        font-size: 22px;
        line-height: 1;
        color: var(--navy);
      }

      .primary-card__subvalue {
        display: inline-block;
        margin-top: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #5f7797;
      }

      .primary-card--highlight .primary-card__label,
      .primary-card--highlight .primary-card__value,
      .primary-card--highlight .primary-card__subvalue,
      .primary-card--navy .primary-card__label,
      .primary-card--navy .primary-card__value,
      .primary-card--navy .primary-card__subvalue {
        color: #ffffff;
      }

      .secondary-grid {
        margin-top: 16px;
        display: grid;
        grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr);
        gap: 14px;
      }

      .metric-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
      }

      .metric-card {
        min-height: 88px;
        padding: 14px 16px;
        border-radius: 18px;
        border: 1px solid #dbe5ef;
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(16, 36, 70, 0.05);
      }

      .metric-card--goal-diff {
        background: var(--green);
        border-color: var(--green);
      }

      .metric-card--clean-sheets {
        background: var(--navy-soft);
        border-color: var(--navy-soft);
      }

      .metric-card__label {
        display: block;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6d82a0;
      }

      .metric-card__value {
        display: block;
        margin-top: 12px;
        font-family: 'Space Grotesk', 'Arial Narrow', sans-serif;
        font-size: 22px;
        line-height: 1;
        color: var(--navy);
      }

      .metric-card__subvalue {
        display: block;
        margin-top: 8px;
        font-size: 13px;
        color: var(--gray-600);
      }

      .metric-card--goal-diff .metric-card__label,
      .metric-card--goal-diff .metric-card__value,
      .metric-card--goal-diff .metric-card__subvalue,
      .metric-card--clean-sheets .metric-card__label,
      .metric-card--clean-sheets .metric-card__value,
      .metric-card--clean-sheets .metric-card__subvalue {
        color: #ffffff;
      }

      .context-card {
        min-height: 188px;
        padding: 16px;
        border-radius: 18px;
        border: 1px solid var(--gray-200);
        background: linear-gradient(180deg, #f8fbfd 0%, #f3f7fb 100%);
      }

      .context-card__eyebrow {
        display: block;
        margin-bottom: 14px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--navy);
      }

      .context-card__scope {
        margin: 0 0 16px;
        font-size: 17px;
        font-weight: 800;
        color: var(--navy);
      }

      .context-card__metric {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
      }

      .context-card__metric span {
        font-size: 14px;
        color: #314969;
      }

      .context-card__metric strong {
        font-family: 'Space Grotesk', 'Arial Narrow', sans-serif;
        font-size: 18px;
        color: var(--navy);
      }

      .context-card__progress {
        width: 100%;
        height: 10px;
        border-radius: 999px;
        overflow: hidden;
        background: #dde6f1;
      }

      .context-card__progress span {
        display: block;
        width: ${officialProgressWidth}%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #0b8b55 0%, #056e37 100%);
      }

      .context-card__subvalue {
        margin: 12px 0 8px;
        font-size: 13px;
        font-weight: 700;
        color: #4d6483;
      }

      .context-card__note {
        margin: 0;
        font-size: 12px;
        line-height: 1.45;
        color: #7388a5;
      }

      .report-footer {
        margin-top: 18px;
        padding-top: 12px;
        border-top: 1px solid var(--gray-200);
      }

      .report-footer__top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
        font-size: 11px;
        color: #5d7392;
      }

      .report-footer__disclaimer {
        margin: 0;
        font-size: 10px;
        line-height: 1.45;
        color: #6a7f9d;
      }

      .report-footer__disclaimer strong {
        color: var(--navy);
      }

      @media print {
        body {
          margin: 0;
        }
      }
    </style>
  </head>
  <body>
    <div class="report-shell">
      <header class="report-header">
        <div class="brand">
          <img src="${escapeHtml(assets.logoUrl)}" alt="Logo ArchiFoot" />
          <div class="brand-copy">
            <span>Archives nationales</span>
          </div>
        </div>

        <div class="report-meta">
          <strong>Rapport de football basé sur des données d'archives nationales</strong>
          <span>Généré le ${escapeHtml(generatedDateLabel)}</span>
        </div>
      </header>

      <section class="report-title">
        <h1>Bilan statistique des rencontres</h1>
        <p>Analyse approfondie des performances historiques de l'équipe nationale d'Algérie.</p>
      </section>

      <section class="report-filters">
        <span class="report-filters__label">Filtres actifs :</span>
        ${filtersHtml}
      </section>

      <section class="primary-grid">
        ${buildPrimaryCard('Nombre de matchs', String(totalMatches), '', 'primary-card--highlight')}
        ${buildPrimaryCard('Victoires', String(summary.wins), 'Gagnés')}
        ${buildPrimaryCard('Nuls', String(summary.draws), 'Partagés', 'primary-card--neutral')}
        ${buildPrimaryCard('Défaites', String(summary.losses), 'Perdus', 'primary-card--danger')}
        ${buildPrimaryCard('Taux de victoire', `${summary.winRate}%`, 'Succès', 'primary-card--navy')}
      </section>

      <section class="secondary-grid">
        <div class="metric-grid">
          ${buildMetricCard('Buts marqués', String(summary.goalsFor))}
          ${buildMetricCard('Adversaires distincts', String(summary.uniqueOpponents))}
          ${buildMetricCard('Buts encaissés', String(summary.goalsAgainst))}
          ${buildMetricCard('Pays hôtes', String(summary.uniqueHostCountries))}
          ${buildMetricCard('Diff. de buts', goalDifference, undefined, 'metric-card--goal-diff')}
          ${buildMetricCard('Cages inviolées', String(summary.cleanSheets), undefined, 'metric-card--clean-sheets')}
        </div>

        <aside class="context-card">
          <span class="context-card__eyebrow">Contexte</span>
          <p class="context-card__scope">${escapeHtml(payload.scopeLabel)}</p>

          <div class="context-card__metric">
            <span>Matchs officiels</span>
            <strong>${summary.officialRate}%</strong>
          </div>

          <div class="context-card__progress" aria-hidden="true">
            <span></span>
          </div>

          <p class="context-card__subvalue">${escapeHtml(officialMatchesLabel)}</p>
          <p class="context-card__note">${escapeHtml(payload.officialNote)}</p>
        </aside>
      </section>

      <footer class="report-footer">
        <div class="report-footer__top">
          <span>© ${reportYear} ArchiFoot Analysis - Projet de préservation du patrimoine sportif algérien.</span>
          <span>Page 01 sur 01</span>
        </div>
        <p class="report-footer__disclaimer"><strong>Disclaimer :</strong> Ce rapport est généré à titre informatif à partir des données historiques collectées par ArchiFoot. Les statistiques reprises reflètent uniquement les filtres actuellement appliqués au bloc match-insights et peuvent évoluer selon les mises à jour des sources officielles.</p>
      </footer>
    </div>
    // <script>
    //   window.addEventListener('afterprint', function () {
    //     window.close();
    //   });
    // </script>
  </body>
</html>`;
}

@Injectable({ providedIn: 'root' })
export class MatchInsightsPdfService {
  constructor(@Inject(DOCUMENT) private readonly document: Document) {}

  async export(payload: MatchInsightsReportPayload): Promise<void> {
    const browserWindow = this.document.defaultView;
    if (!browserWindow) {
      throw new Error('No browser window available for PDF export.');
    }

    const target = this.openPrintTarget(browserWindow);
    const { printWindow } = target;

    const html = buildMatchInsightsReportHtml(payload, { baseUrl: this.document.baseURI });

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();

    await waitForPrintWindow(printWindow);

    printWindow.focus();
    printWindow.print();
    schedulePrintTargetCleanup(target);

    await delay(150);
  }

  private openPrintTarget(browserWindow: Window): MatchInsightsPrintTarget {
    const popupWindow = browserWindow.open('', '_blank', 'width=1024,height=1320');
    if (popupWindow) {
      return {
        printWindow: popupWindow,
        cleanup: () => undefined,
      };
    }

    const iframe = this.document.createElement('iframe');
    iframe.setAttribute('title', 'ArchiFoot PDF print preview');
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.position = 'fixed';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.opacity = '0';
    iframe.style.pointerEvents = 'none';
    iframe.style.border = '0';

    (this.document.body ?? this.document.documentElement).appendChild(iframe);

    const iframeWindow = iframe.contentWindow;
    if (!iframeWindow) {
      iframe.remove();
      throw new Error('Unable to open the print preview window.');
    }

    return {
      printWindow: iframeWindow,
      cleanup: () => {
        if (iframe.isConnected) {
          iframe.remove();
        }
      },
    };
  }
}

function buildPrimaryCard(
  label: string,
  value: string,
  subvalue: string,
  variantClass = '',
): string {
  return `<article class="primary-card ${variantClass}">
    <span class="primary-card__label">${escapeHtml(label)}</span>
    <p class="primary-card__value">${escapeHtml(value)}</p>
    ${subvalue ? `<span class="primary-card__subvalue">${escapeHtml(subvalue)}</span>` : ''}
  </article>`;
}

function buildMetricCard(
  label: string,
  value: string,
  subvalue?: string,
  variantClass = '',
): string {
  return `<article class="metric-card ${variantClass}">
    <span class="metric-card__label">${escapeHtml(label)}</span>
    <span class="metric-card__value">${escapeHtml(value)}</span>
    ${subvalue ? `<span class="metric-card__subvalue">${escapeHtml(subvalue)}</span>` : ''}
  </article>`;
}

function formatGeneratedDate(value: Date): string {
  const dateLabel = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
    .formatToParts(value)
    .map((part) => (part.type === 'month' ? capitalize(part.value) : part.value))
    .join('');

  const timeLabel = new Intl.DateTimeFormat('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  }).format(value);

  return `${dateLabel} à ${timeLabel}`;
}

function capitalize(value: string): string {
  return value.length > 0 ? value.charAt(0).toUpperCase() + value.slice(1) : value;
}

function normalizeText(value: string | null | undefined): string | null {
  if (typeof value !== 'string') {
    return null;
  }

  const normalizedValue = value.trim();
  return normalizedValue === '' ? null : normalizedValue;
}

function resolveAssets(baseUrl: string): MatchInsightsReportAssets {
  return {
    logoUrl: new URL('assets/img/logo.png', baseUrl).href,
    spaceCssUrl: new URL('assets/sandbox/fonts/space/space.css', baseUrl).href,
    urbanistCssUrl: new URL('assets/sandbox/fonts/urbanist/urbanist.css', baseUrl).href,
  };
}

function escapeHtml(value: string): string {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

function clampPercentage(value: number): number {
  if (!Number.isFinite(value)) {
    return 0;
  }

  return Math.min(100, Math.max(0, value));
}

async function waitForPrintWindow(printWindow: Window): Promise<void> {
  if (printWindow.document.readyState === 'complete') {
    await delay(120);
    return;
  }

  await new Promise<void>((resolve) => {
    const onLoad = () => {
      cleanup();
      window.setTimeout(resolve, 120);
    };

    const cleanup = () => {
      printWindow.removeEventListener('load', onLoad);
    };

    printWindow.addEventListener('load', onLoad, { once: true });
    window.setTimeout(() => {
      cleanup();
      resolve();
    }, 1200);
  });
}

function delay(timeoutMs: number): Promise<void> {
  return new Promise((resolve) => window.setTimeout(resolve, timeoutMs));
}

function schedulePrintTargetCleanup(target: MatchInsightsPrintTarget): void {
  let cleanedUp = false;

  const cleanup = () => {
    if (cleanedUp) {
      return;
    }

    cleanedUp = true;
    target.cleanup();
  };

  target.printWindow.addEventListener('afterprint', cleanup, { once: true });
  window.setTimeout(cleanup, 2000);
}
