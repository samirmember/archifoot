import { Component, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { Country } from '../../../../models/country.model';
import { NumberService } from '../../../../../shared/number.service';
import { CountryInputComponent } from 'src/app/layouts/input/country-input.component';
import { CompetitionService } from 'src/app/services/competition.service';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [AutoCompleteModule, FormsModule, CountryInputComponent],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent {
  private numberService = inject(NumberService);
  private competitionService = inject(CompetitionService);

  selectedCountry: Country | null = null;
  years = this.numberService.generateAllYears();
  competitions = toSignal(this.competitionService.getCompetitions(), { initialValue: [] });
}
