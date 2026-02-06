import { Component, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AutoCompleteCompleteEvent, AutoCompleteModule } from 'primeng/autocomplete';
import { finalize } from 'rxjs';
import { Country } from '../../../../models/country.model';
import { CountryService } from '../../../../services/country.service';
import { NumberService } from '../../../../../shared/number.service';
import { CountryInputComponent } from 'src/app/layouts/input/country-input.component';

@Component({
  selector: 'app-senior-national-team-matchs',
  imports: [AutoCompleteModule, FormsModule, CountryInputComponent],
  templateUrl: './senior-national-team-matchs.component.html',
  styleUrl: './senior-national-team-matchs.component.scss',
})
export class SeniorNationalTeamMatchsComponent {
  private numberService = inject(NumberService);
  selectedCountry: Country | null = null;
  years = this.numberService.generateAllYears();
}
