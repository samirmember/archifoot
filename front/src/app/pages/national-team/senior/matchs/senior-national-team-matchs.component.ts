import { Component, computed, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { catchError, of } from 'rxjs';
import { Country } from '../../../../models/country.model';
import { NumberService } from '../../../../../shared/number.service';
import { CountryInputComponent } from 'src/app/layouts/input/country-input.component';
import { CompetitionService } from 'src/app/services/competition.service';
import { MatchResult, ResultService } from 'src/app/services/result.service';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [AutoCompleteModule, FormsModule, CountryInputComponent],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent {
  private numberService = inject(NumberService);
  private competitionService = inject(CompetitionService);
  private resultService = inject(ResultService);

  selectedCountry: Country | null = null;
  selectedYear: number | null = null;
  selectedCompetitionId: number | null = null;

  years = this.numberService.generateAllYears();
  competitions = toSignal(
    this.competitionService.getCompetitions().pipe(catchError(() => of([]))),
    {
      initialValue: [],
    },
  );
  results = toSignal(this.resultService.getResults().pipe(catchError(() => of([]))), {
    initialValue: [],
  });

  filteredResults = computed(() => {
    const currentResults = this.results();

    return currentResults.filter((result: MatchResult) => {
      const yearIsMatching = this.selectedYear
        ? this.getYearFromDate(result.date) === this.selectedYear
        : true;

      const competitionIsMatching = this.selectedCompetitionId
        ? this.extractCompetitionIdFromResult(result, this.selectedCompetitionId)
        : true;

      return yearIsMatching && competitionIsMatching;
    });
  });

  private getYearFromDate(date: string | null): number | null {
    if (!date) {
      return null;
    }

    const parsedDate = new Date(date);
    return Number.isNaN(parsedDate.getTime()) ? null : parsedDate.getFullYear();
  }

  private extractCompetitionIdFromResult(
    result: MatchResult,
    selectedCompetitionId: number,
  ): boolean {
    const competition = this.competitions().find((item) => item.name === result.competition);
    return competition?.id === selectedCompetitionId;
  }
}
